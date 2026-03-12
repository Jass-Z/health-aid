<?php
// location-map.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/style.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect patients to patient dashboard
if ($_SESSION['user']['role'] === 'patient') {
    header("Location: patient_dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$patient_id = $_GET['patient_id'] ?? null;

// Initialize variables with default values
$patient = [];
$patients = [];
$location = null;
$error = null;

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get patient info if patient_id is provided
    if ($patient_id) {
        $patient_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_admin = false");
        $patient_stmt->execute([$patient_id]);
        $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all patients for sidebar
    $patients_stmt = $pdo->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM health_records WHERE user_id = u.id) as reading_count,
               (SELECT recorded_at FROM health_records WHERE user_id = u.id ORDER BY recorded_at DESC LIMIT 1) as last_reading
        FROM users u 
        WHERE u.is_admin = false 
        ORDER BY u.full_name
    ");
    $patients_stmt->execute();
    $patients = $patients_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get latest location for the selected patient or current user
    $target_user_id = $patient_id ?: $user_id;
    
    $location_stmt = $pdo->prepare("
        SELECT location_lat, location_lng, recorded_at 
        FROM health_records 
        WHERE user_id = ? AND location_lat IS NOT NULL 
        ORDER BY recorded_at DESC 
        LIMIT 1
    ");
    $location_stmt->execute([$target_user_id]);
    $location = $location_stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $patients = [];
}

// Default coordinates (UTB)
$default_lat = 26.18387341814887;
$default_lng = 50.5191798;

$current_lat = $location['location_lat'] ?? $default_lat;
$current_lng = $location['location_lng'] ?? $default_lng;

$current_user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Location - Health Aid</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <style>
        .map-container {
            display: grid;
            grid-template-columns: <?php echo $current_user['role'] === 'doctor' ? '350px 1fr' : '1fr'; ?>;
            gap: 30px;
            padding: 30px 0;
            min-height: calc(100vh - 80px);
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <div class="map-container">
            <?php if ($current_user['role'] === 'doctor'): ?>
            <!-- Patients Sidebar -->
            <div class="patients-sidebar">
                <div class="patients-header">
                    <h2 style="color: var(--text-primary);">Patients</h2>
                    <span style="color: var(--text-secondary); font-size: 0.9rem;"><?php echo count($patients); ?> patients</span>
                </div>
                
                <?php if (empty($patients)): ?>
                    <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary);">
                        <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>No patients assigned</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($patients as $p): ?>
                        <div class="patient-card <?php echo $patient_id == $p['id'] ? 'active' : ''; ?>" 
                             onclick="window.location.href='location-map.php?patient_id=<?php echo $p['id']; ?>'">
                            <div class="patient-name">
                                <?php echo htmlspecialchars($p['full_name']); ?>
                            </div>
                            <div class="patient-info">
                                <span><?php echo $p['reading_count']; ?> readings</span>
                                <span>
                                    <?php if ($p['last_reading']): ?>
                                        <?php echo date('M j, g:i A', strtotime($p['last_reading'])); ?>
                                    <?php else: ?>
                                        No data
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Map Content -->
            <div class="map-content">
                <?php if ($patient || !$patient_id): ?>
                    <div class="content-header">
                        <h1 class="page-title">
                            <?php if ($patient): ?>
                                Location Tracking: <?php echo htmlspecialchars($patient['full_name']); ?>
                            <?php else: ?>
                                Patient Location
                            <?php endif; ?>
                        </h1>
                    </div>

                    <div class="map-wrapper">
                        <!-- Map -->
                        <div id="map"></div>
                        
                        <!-- Map Sidebar -->
                        <div class="map-sidebar">
                            <!-- Patient Info -->
                            <div class="patient-info-card">
                                <div class="patient-header">
                                    <img src="<?php echo !empty($patient['profile_picture']) ? 'uploads/' . $patient['profile_picture'] : './uploads/profile_placeholder_img.png' ?>" 
                                         alt="Patient" class="patient-avatar">
                                    <div>
                                        <h3 style="color: var(--text-primary); margin-bottom: 5px;">
                                            <?php echo !empty($patient['full_name']) ? htmlspecialchars($patient['full_name']) : 'Please Select A Patient'; ?>
                                        </h3>
                                        <!-- Error in Last Update display -->
                                        <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                            Last update: <?php echo !empty($location['recorded_at']) ? $location['recorded_at'] : 'No location data'; ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="controls">
                                    <button class="control-btn" onclick="refreshLocation()">
                                        <i class="fas fa-sync-alt"></i> Refresh Location
                                    </button>
                                    <button class="control-btn tracking" onclick="toggleLiveTracking()" id="trackingBtn">
                                        <i class="fas fa-play"></i> Start Live Track
                                    </button>
                                </div>
                                
                                <div class="location-details">
                                    <p>Latitude: <span id="currentLat"><?php echo $current_lat; ?></span></p>
                                    <p>Longitude: <span id="currentLng"><?php echo $current_lng; ?></span></p>
                                </div>
                            </div>
                            
                            <!-- Hospitals List -->
                            <div class="hospitals-list">
                                <h3 style="color: var(--text-primary); margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                                    <span>
                                        <i class="fas fa-hospital"></i> Nearby Hospitals
                                    </span>
                                    <span id="hospitalsCount" style="background: var(--primary-color); color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.85rem;">0</span>
                                </h3>
                                
                                <div id="hospitalsList">
                                    <!-- Hospitals will show here -->
                                </div>
                            </div>
                            
                            <!-- Route Info -->
                            <div class="route-info" id="routeInfo">
                                <h4 style="color: var(--text-primary); margin-bottom: 10px;">Route to Hospital</h4>
                                <div id="routeDetails"></div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                        <i class="fas fa-map-marker-alt" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;"></i>
                        <h2>Select a Patient</h2>
                        <p>Choose a patient from the sidebar to view their location and track them in real-time.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script>
        // Global variables
        let map;
        let patientMarker;
        let hospitalMarkers = [];
        let routingControl;
        let currentLat = <?php echo $current_lat; ?>;
        let currentLng = <?php echo $current_lng; ?>;
        let liveTracking = false;
        let trackingInterval;

        // Initialize map
        function initializeMap() {
            map = L.map('map').setView([currentLat, currentLng], 13);
            
            // Use different tile layers for light/dark mode
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            if (currentTheme === 'dark') {
                L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                    attribution: '© OpenStreetMap contributors, © CartoDB'
                }).addTo(map);
            } else {
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
            }

            // Add patient marker
            const patientIcon = L.divIcon({
                html: '<div style="background: #6A2C70; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);"><i class="fas fa-user" style="font-size: 14px;"></i></div>',
                className: 'patient-marker',
                iconSize: [30, 30]
            });
            
            patientMarker = L.marker([currentLat, currentLng], { icon: patientIcon })
                .addTo(map)
                .bindPopup('Patient: <?php echo !empty($patient['full_name']) ? htmlspecialchars($patient['full_name']) : 'Unknown'; ?>')
                .openPopup();

            // Find nearby hospitals
            findNearbyHospitals(currentLat, currentLng);
        }

        // Enhanced hospital finding with multiple data sources bc it sucked last time yay
        async function findNearbyHospitals(lat, lng) {
            try {
                // Clear existing markers
                hospitalMarkers.forEach(marker => map.removeLayer(marker));
                hospitalMarkers = [];
                
                // Try multiple hospital data sources
                const hospitals = await Promise.any([
                    fetchOSMHospitals(lat, lng),
                    fetchOverpassHospitals(lat, lng),
                    getSampleHospitals(lat, lng)
                ]);
                
                displayHospitals(hospitals, lat, lng);
                
            } catch (error) {
                console.error('All hospital APIs failed:', error);
                const sampleHospitals = getSampleHospitals(lat, lng);
                displayHospitals(sampleHospitals, lat, lng);
            }
        }

        // OpenStreetMap Nominatim API
        async function fetchOSMHospitals(lat, lng) {
            const radius = 5000; // 5km
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=hospital&lat=${lat}&lon=${lng}&radius=${radius}&limit=10`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            return data.map(hospital => ({
                name: hospital.display_name.split(',')[0],
                lat: parseFloat(hospital.lat),
                lng: parseFloat(hospital.lon),
                distance: calculateDistance(lat, lng, parseFloat(hospital.lat), parseFloat(hospital.lon)),
                type: 'hospital',
                source: 'OSM'
            }));
        }

        // Overpass API for more comprehensive data
        async function fetchOverpassHospitals(lat, lng) {
            const overpassQuery = `
                [out:json][timeout:25];
                (
                    node["amenity"="hospital"](around:5000,${lat},${lng});
                    way["amenity"="hospital"](around:5000,${lat},${lng});
                    relation["amenity"="hospital"](around:5000,${lat},${lng});
                );
                out center;
            `;
            
            const response = await fetch('https://overpass-api.de/api/interpreter', {
                method: 'POST',
                body: 'data=' + encodeURIComponent(overpassQuery)
            });
            
            const data = await response.json();
            
            return data.elements.map(element => {
                const hospitalLat = element.lat || element.center.lat;
                const hospitalLng = element.lon || element.center.lon;
                
                return {
                    name: element.tags?.name || 'Hospital',
                    lat: hospitalLat,
                    lng: hospitalLng,
                    distance: calculateDistance(lat, lng, hospitalLat, hospitalLng),
                    phone: element.tags?.phone,
                    address: element.tags?.['addr:street'],
                    type: 'hospital',
                    source: 'Overpass'
                };
            });
        }

        
        // Fallback sample hospitals, kept incase of testing even tho I doubt I need it but whatever
        function getSampleHospitals(lat, lng) {
            const samples = [];
            const hospitalNames = [
                "Salmaniya Medical Complex",
                "Metropolitan Medical Center",
                "Community Health Hospital",
                "University Medical Center",
                "Regional Hospital",
                "Emergency Care Clinic"
            ];
            
            for (let i = 0; i < 6; i++) {
                const angle = (i / 6) * 2 * Math.PI;
                const distance = 1 + Math.random() * 3; // 1-4 km
                const hospitalLat = lat + (Math.cos(angle) * distance * 0.01);
                const hospitalLng = lng + (Math.sin(angle) * distance * 0.01);
                
                samples.push({
                    name: hospitalNames[i],
                    lat: hospitalLat,
                    lng: hospitalLng,
                    distance: calculateDistance(lat, lng, hospitalLat, hospitalLng),
                    address: `${1000 + i * 100} Medical Center Dr`,
                    type: 'hospital',
                    source: 'Sample'
                });
            }
            return samples;
        }


        // Display hospitals in sidebar and on map
        function displayHospitals(hospitals, userLat, userLng) {
            const hospitalsList = document.getElementById('hospitalsList');
            const hospitalsCount = document.getElementById('hospitalsCount');
            
            // Sort by distance
            hospitals.sort((a, b) => a.distance - b.distance);
            
            hospitalsCount.textContent = hospitals.length;
            
            // Display in sidebar
            hospitalsList.innerHTML = hospitals.map((hospital, index) => `
                <div class="hospital-item" onclick="selectHospital(${hospital.lat}, ${hospital.lng}, '${hospital.name.replace(/'/g, "\\'")}')">
                    <div class="hospital-name">${hospital.name}</div>
                    <div class="hospital-details">
                        <div>${hospital.distance.toFixed(1)} km away</div>
                        ${hospital.address ? `<div>${hospital.address}</div>` : ''}
                        ${hospital.phone ? `<div><i class="fas fa-phone"></i> ${hospital.phone}</div>` : ''}
                    </div>
                    <div class="hospital-actions">
                        <button class="btn btn-primary" onclick="event.stopPropagation(); showRoute(${hospital.lat}, ${hospital.lng})">
                            <i class="fas fa-route"></i> Route
                        </button>
                        <button class="btn btn-outline" onclick="event.stopPropagation(); openGoogleMaps(${hospital.lat}, ${hospital.lng})">
                            <i class="fas fa-external-link-alt"></i> Maps
                        </button>
                        ${hospital.phone ? `
                        <button class="btn btn-outline" onclick="event.stopPropagation(); callHospital('${hospital.phone}')">
                            <i class="fas fa-phone"></i> Call
                        </button>
                        ` : ''}
                    </div>
                </div>
            `).join('');
            
            // Add to map
            hospitals.forEach(hospital => {
                const hospitalIcon = L.divIcon({
                    html: '<div style="background: #E74C3C; color: white; border-radius: 50%; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);"><i class="fas fa-hospital" style="font-size: 12px;"></i></div>',
                    className: 'hospital-marker',
                    iconSize: [25, 25]
                });
                
                const marker = L.marker([hospital.lat, hospital.lng], { icon: hospitalIcon })
                    .addTo(map)
                    .bindPopup(`
                        <strong>${hospital.name}</strong><br>
                        Distance: ${hospital.distance.toFixed(1)} km<br>
                        ${hospital.address ? hospital.address + '<br>' : ''}
                        ${hospital.phone ? `<i class="fas fa-phone"></i> ${hospital.phone}` : ''}
                    `);
                
                hospitalMarkers.push(marker);
            });
            
            // Auto-select and route to nearest hospital
            if (hospitals.length > 0) {
                const nearest = hospitals[0];
                selectHospital(nearest.lat, nearest.lng, nearest.name);
                showRoute(nearest.lat, nearest.lng);
            }
        }

        // Show route to hospital
        function showRoute(hospitalLat, hospitalLng) {
            // Remove existing route
            if (routingControl) {
                map.removeControl(routingControl);
            }
            
            // Create new route
            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(currentLat, currentLng),
                    L.latLng(hospitalLat, hospitalLng)
                ],
                routeWhileDragging: false,
                showAlternatives: false,
                lineOptions: {
                    styles: [{color: '#6A2C70', weight: 6}]
                },
                createMarker: function() { return null; } // Remove default markers
            }).addTo(map);
            
            // Show route info
            const routeInfo = document.getElementById('routeInfo');
            const routeDetails = document.getElementById('routeDetails');
            routeInfo.classList.add('active');
            
            // Listen for route found event
            routingControl.on('routesfound', function(e) {
                const routes = e.routes;
                const route = routes[0];
                const distance = (route.summary.totalDistance / 1000).toFixed(1);
                const time = Math.ceil(route.summary.totalTime / 60);
                
                routeDetails.innerHTML = `
                    <div>Distance: <strong>${distance} km</strong></div>
                    <div>Estimated time: <strong>${time} minutes</strong></div>
                    <div style="margin-top: 10px; font-size: 0.8rem; color: var(--text-secondary);">
                        <i class="fas fa-info-circle"></i> Route calculated using OpenStreetMap
                    </div>
                `;
            });
        }

        // Utility functions
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
                    Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        function selectHospital(lat, lng, name) {
            map.setView([lat, lng], 15);
            L.popup()
                .setLatLng([lat, lng])
                .setContent(`<strong>${name}</strong>`)
                .openOn(map);
        }

        function openGoogleMaps(lat, lng) {
            const url = `https://www.google.com/maps/dir/${currentLat},${currentLng}/${lat},${lng}`;
            window.open(url, '_blank');
        }

        function callHospital(phone) {
            if (phone) {
                window.open('tel:' + phone);
            }
        }

        function refreshLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    position => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        updateLocation(lat, lng, position.coords.accuracy);
                    },
                    error => {
                        alert('Error getting location: ' + error.message);
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }

        function updateLocation(lat, lng, accuracy = null) {
            currentLat = lat;
            currentLng = lng;
            
            map.setView([lat, lng], 13);
            patientMarker.setLatLng([lat, lng]);
            
            document.getElementById('currentLat').textContent = lat.toFixed(6);
            document.getElementById('currentLng').textContent = lng.toFixed(6);
            // Update hospitals and route
            findNearbyHospitals(lat, lng);
        }

        function toggleLiveTracking() {
            liveTracking = !liveTracking;
            const trackingBtn = document.getElementById('trackingBtn');
            
            if (liveTracking) {
                trackingBtn.innerHTML = '<i class="fas fa-stop"></i> Stop Tracking';
                trackingBtn.style.background = '#E74C3C';
                
                // Start tracking
                trackingInterval = setInterval(() => {
                    refreshLocation();
                }, 30000); // Update every 30 seconds
                
                alert('Live tracking started - location will update every 30 seconds');
            } else {
                trackingBtn.innerHTML = '<i class="fas fa-play"></i> Start Live Track';
                trackingBtn.style.background = '';
                
                // Stop tracking
                if (trackingInterval) {
                    clearInterval(trackingInterval);
                }
                
                alert('Live tracking stopped');
            }
        }

        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateThemeIcon(currentTheme);
        
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
            
            // Reload map with new theme
            if (map) {
                map.remove();
                initializeMap();
            }
        });
        
        function updateThemeIcon(theme) {
            const icon = themeToggle.querySelector('i');
            icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }

        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', initializeMap);
    </script>
</body>
</html>