<?php
// patient_dashboard.php
require_once __DIR__ . '/includes/config.php'; 
require_once __DIR__ . '/patientstyle.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_user = $_SESSION['user'];
$patient_id = $user_id;

// Redirect doctors to regular dashboard
if ($current_user['role'] === 'doctor') {
    header("Location: dashboard.php");
    exit();
}

// Initialize variables
$patient_vitals = null;
$realtime_data = null;
$is_live_data = false;
$emergency_contacts = [];
$health_records = [];
$stats = [];
$error = null;
$location = null;
$chart_data = [];
$filtered_records = [];

// Get period, vitals & status filter from URL
$period = $_GET['period'] ?? 'weekly';
$vitals_filter = $_GET['vitals'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

// Calculate BMI
$bmi = null;
if ($current_user['height'] && $current_user['weight']) {
    $height_in_meters = $current_user['height'] / 100;
    $bmi = round($current_user['weight'] / ($height_in_meters * $height_in_meters), 1);
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get latest vitals with real-time data
    $vitals_stmt = $pdo->prepare("
        SELECT hr.*, 
               TIMESTAMPDIFF(SECOND, hr.recorded_at, NOW()) as seconds_ago,
               hr.fall_detected,
               hr.battery_level,
               hr.alert_stage
        FROM health_records hr 
        WHERE user_id = ? 
        ORDER BY recorded_at DESC 
        LIMIT 1
    ");
    $vitals_stmt->execute([$user_id]);
    $patient_vitals = $vitals_stmt->fetch(PDO::FETCH_ASSOC);
    $realtime_data = $patient_vitals;
    
    // Check if data is recent (less than 2 minutes old)
    $is_live_data = $realtime_data && $realtime_data['seconds_ago'] < 120;
    
    // Get latest location
    $location_stmt = $pdo->prepare("
        SELECT location_lat, location_lng, recorded_at 
        FROM health_records 
        WHERE user_id = ? AND location_lat IS NOT NULL 
        ORDER BY recorded_at DESC 
        LIMIT 1
    ");
    $location_stmt->execute([$user_id]);
    $location = $location_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get emergency contacts
    $contacts_stmt = $pdo->prepare("SELECT * FROM emergency_contacts WHERE user_id = ? ORDER BY created_at DESC");
    $contacts_stmt->execute([$user_id]);
    $emergency_contacts = $contacts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
// Include CallMeBot WhatsApp functions
require_once 'callmebot.php';

// Check and send health alerts if conditions are met
if ($realtime_data && $realtime_data['alert_stage'] >= 2) {
    $alert_results = checkAndSendHealthAlerts($pdo, $user_id, $realtime_data);
    
    // Optional: You can log or display alert status
    if ($alert_results) {
        $sent_count = count(array_filter($alert_results, function($result) {
            return $result['success'];
        }));
        error_log("WhatsApp health alerts sent for user {$user_id}: {$sent_count} successful");
        
        // You can also store this in session to show to the user
        $_SESSION['alert_sent'] = true;
        $_SESSION['alert_count'] = $sent_count;
    }
}
    // Get health records for statistics based on period
    switch ($period) {
        case 'daily':
            $date_filter = "AND recorded_at >= CURDATE()";
            break;
        case 'weekly':
            $date_filter = "AND recorded_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'monthly':
            $date_filter = "AND recorded_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
        case 'yearly':
            $date_filter = "AND recorded_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            break;
        default:
            $date_filter = "AND recorded_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    }

    $records_stmt = $pdo->prepare("
        SELECT * FROM health_records 
        WHERE user_id = ? $date_filter
        ORDER BY recorded_at DESC
    ");
    $records_stmt->execute([$user_id]);
    $health_records = $records_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Apply additional filters
    $filtered_records = $health_records;
    
   // Filter by vitals type
    if ($vitals_filter !== 'all') {
        $filtered_records = array_filter($filtered_records, function($record) use ($vitals_filter) {
            switch ($vitals_filter) {
                case 'temperature':
                    return $record['body_temperature'] !== null;
                case 'heart_rate':
                    return $record['heart_rate'] !== null;
                case 'spo2':
                    return $record['spo2'] !== null;
                case 'blood_pressure':
                    return $record['blood_pressure'] !== null;
                default:
                    return true;
            }
        });
    }
    
    // Filter by status
    if ($status_filter !== 'all') {
        $filtered_records = array_filter($filtered_records, function($record) use ($status_filter) {
            $warnings = [];
            if ($record['heart_rate'] < 60 || $record['heart_rate'] > 100) $warnings[] = 'heart_rate';
            if ($record['spo2'] < 95) $warnings[] = 'spo2';
            if ($record['body_temperature'] < 36.1 || $record['body_temperature'] > 37.2) $warnings[] = 'temperature';
            
            $has_warnings = !empty($warnings);
            $is_critical = ($record['heart_rate'] < 50 || $record['heart_rate'] > 130 || $record['spo2'] < 90 || $record['body_temperature'] < 35 || $record['body_temperature'] > 39);
            
            switch ($status_filter) {
                case 'normal':
                    return !$has_warnings;
                case 'warning':
                    return $has_warnings && !$is_critical;
                case 'critical':
                    return $is_critical;
                default:
                    return true;
            }
        });
    }

    // Calculate basic statistics
    if (count($health_records) > 0) {
        $heart_rates = array_column($health_records, 'heart_rate');
        $temps = array_column($health_records, 'body_temperature');
        $spo2s = array_column($health_records, 'spo2');
        
        $stats = [
            'avg_heart_rate' => round(array_sum($heart_rates) / count($heart_rates)),
            'avg_temperature' => round(array_sum($temps) / count($temps), 1),
            'avg_spo2' => round(array_sum($spo2s) / count($spo2s)),
            'total_readings' => count($health_records),
            'min_heart_rate' => min($heart_rates),
            'max_heart_rate' => max($heart_rates),
            'min_spo2' => min($spo2s),
            'max_spo2' => max($spo2s)
        ];
    }

    // Prepare chart data
    $chart_data = [];
    $grouped_data = [];
    
    foreach ($health_records as $record) {
        $date = date('Y-m-d', strtotime($record['recorded_at']));
        if (!isset($grouped_data[$date])) {
            $grouped_data[$date] = [
                'date' => $date,
                'temps' => [],
                'heart_rates' => [],
                'spo2s' => [],
                'count' => 0
            ];
        }
        
        if ($record['body_temperature']) {
            $grouped_data[$date]['temps'][] = $record['body_temperature'];
        }
        if ($record['heart_rate']) {
            $grouped_data[$date]['heart_rates'][] = $record['heart_rate'];
        }
        if ($record['spo2']) {
            $grouped_data[$date]['spo2s'][] = $record['spo2'];
        }
        $grouped_data[$date]['count']++;
    }
    
    foreach ($grouped_data as $date => $data) {
        $chart_data[] = [
            'date' => $date,
            'avg_temp' => !empty($data['temps']) ? round(array_sum($data['temps']) / count($data['temps']), 1) : null,
            'avg_heart_rate' => !empty($data['heart_rates']) ? round(array_sum($data['heart_rates']) / count($data['heart_rates'])) : null,
            'avg_spo2' => !empty($data['spo2s']) ? round(array_sum($data['spo2s']) / count($data['spo2s'])) : null,
            'readings_count' => $data['count']
        ];
    }
    
    // Sort chart data by date
    usort($chart_data, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Default coordinates (UTB)
$default_lat = 26.18387341814887;
$default_lng = 50.5191798;

$current_lat = $location['location_lat'] ?? $default_lat;
$current_lng = $location['location_lng'] ?? $default_lng;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Health Dashboard - Health Aid</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Emergency Contacts Modal -->
    <div id="emergencyContactsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-phone-alt"></i> Emergency Contacts</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Add Contact Form -->
                <div class="add-contact-form">
                    <h3 style="color: var(--primary-color); margin-bottom: 20px;">Add Emergency Contact</h3>
                    
                    <form id="emergencyContactForm" method="POST" action="emergency-contacts.php">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" name="phone" class="form-control" required placeholder="+1234567890">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="optional@example.com">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Relationship *</label>
                            <select name="relationship" class="form-control" required>
                                <option value="">Select Relationship</option>
                                <option value="family_member">Family Member</option>
                                <option value="friend">Friend</option>
                                <option value="doctor">Doctor</option>
                                <option value="caregiver">Caregiver</option>
                                <option value="neighbor">Neighbor</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="share_location" id="share_location" checked>
                                <label for="share_location" class="form-label">
                                    Share live location in emergency alerts
                                </label>
                            </div>
                            <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                                When checked, GPS coordinates will be included in emergency SMS messages
                            </small>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                            <button type="submit" name="add_contact" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Emergency Contact
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Existing Contacts -->
                <div class="existing-contacts">
                    <h3>Existing Emergency Contacts (<?php echo count($emergency_contacts); ?>)</h3>
                    
                    <?php if (empty($emergency_contacts)): ?>
                        <div style="text-align: center; padding: 20px; color: var(--text-secondary);">
                            <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                            <p>No emergency contacts set</p>
                        </div>
                    <?php else: ?>
                        <div class="contact-list">
                            <?php foreach ($emergency_contacts as $contact): ?>
                            <div class="contact-item">
                                <div class="contact-name"><?php echo htmlspecialchars($contact['name']); ?></div>
                                <div class="contact-details">
                                    <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact['phone']); ?></div>
                                    <?php if ($contact['email']): ?>
                                    <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact['email']); ?></div>
                                    <?php endif; ?>
                                    <div><i class="fas fa-user-friends"></i> 
                                        <?php 
                                        $relationship_labels = [
                                            'family_member' => 'Family Member',
                                            'friend' => 'Friend',
                                            'doctor' => 'Doctor',
                                            'caregiver' => 'Caregiver',
                                            'neighbor' => 'Neighbor',
                                            'other' => 'Other'
                                        ];
                                        echo $relationship_labels[$contact['relationship']] ?? 'Contact';
                                        ?>
                                    </div>
                                    <div><i class="fas fa-map-marker-alt"></i> 
                                        <?php echo $contact['share_location'] ? 'Location sharing enabled' : 'Location sharing disabled'; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="dashboard-container">
            <div class="content-header">
                <h1 class="page-title">
                    My Health Dashboard
                    <?php if ($realtime_data && $realtime_data['alert_stage']): ?>
                        <span class="alert-stage stage-<?php 
                            echo $realtime_data['alert_stage'] == 1 ? 'normal' : 
                                 ($realtime_data['alert_stage'] == 2 ? 'moderate' : 'critical'); 
                        ?>">
                            <?php echo $realtime_data['alert_stage'] == 1 ? 'Normal' : 
                                  ($realtime_data['alert_stage'] == 2 ? 'Moderate' : 'Critical'); ?>
                        </span>
                    <?php endif; ?>
                </h1>
                <div class="last-updated">
                    Last updated: <?php echo $patient_vitals ? date('M j, g:i A', strtotime($patient_vitals['recorded_at'])) : 'No data'; ?>
                    <button onclick="refreshData()" class="btn btn-primary" style="margin-left: 10px;">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="error" style="background: #E74C3C; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Live Status -->
            <div class="live-status">
                <div class="status-indicator <?php echo $is_live_data ? 'live' : 'offline'; ?>">
                    <i class="fas fa-circle"></i>
                    <?php echo $is_live_data ? 'Live Data' : 'Device Offline'; ?>
                </div>
                <div class="device-info">
                    <?php if ($realtime_data): ?>
                        <span>
                            <i class="fas fa-clock"></i>
                            Last update: <?php echo $realtime_data['seconds_ago'] ?? 'N/A'; ?> seconds ago
                        </span>
                        <?php if ($realtime_data['battery_level']): ?>
                        <span>
                            <i class="fas fa-battery-half"></i>
                            Battery: <?php echo $realtime_data['battery_level']; ?>%
                        </span>
                        <?php endif; ?>
                        <?php if ($realtime_data['fall_detected']): ?>
                        <span style="color: #e74c3c;">
                            <i class="fas fa-exclamation-triangle"></i>
                            Fall Detected!
                        </span>
                        <?php endif; ?>
                        <?php if ($current_user['device_id']): ?>
                        <span>
                            <i class="fas fa-microchip"></i>
                            Device: <?php echo htmlspecialchars($current_user['device_id']); ?>
                        </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span>No recent data from device</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Current Vitals -->
                <div class="card">
                    <h3><i class="fas fa-heartbeat"></i> Current Vitals</h3>
                    <div class="vitals-grid">
                        <div class="vital-card">
                            <div class="vital-header">
                                <div class="vital-title">Heart Rate</div>
                                <div class="vital-icon" style="background-color: #FF6B6B;">
                                    <i class="fas fa-heartbeat"></i>
                                </div>
                            </div>
                            <div class="vital-value">
                                <?php echo $patient_vitals ? $patient_vitals['heart_rate'] . ' bpm' : '--'; ?>
                            </div>
                        </div>
                        
                        <div class="vital-card">
                            <div class="vital-header">
                                <div class="vital-title">SpO2</div>
                                <div class="vital-icon" style="background-color: #4ECDC4;">
                                    <i class="fas fa-lungs"></i>
                                </div>
                            </div>
                            <div class="vital-value">
                                <?php echo $patient_vitals ? $patient_vitals['spo2'] . '%' : '--'; ?>
                            </div>
                        </div>
                        
                        <div class="vital-card">
                            <div class="vital-header">
                                <div class="vital-title">Temperature</div>
                                <div class="vital-icon" style="background-color: #FFA726;">
                                    <i class="fas fa-thermometer-half"></i>
                                </div>
                            </div>
                            <div class="vital-value">
                                <?php echo $patient_vitals ? $patient_vitals['body_temperature'] . '°C' : '--'; ?>
                            </div>
                        </div>
                        
                        <div class="vital-card">
                            <div class="vital-header">
                                <div class="vital-title">Blood Pressure</div>
                                <div class="vital-icon" style="background-color: #5C6BC0;">
                                    <i class="fas fa-tint"></i>
                                </div>
                            </div>
                            <div class="vital-value">
                                <?php echo $patient_vitals ? $patient_vitals['blood_pressure'] : '--'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal Information & BMI -->
                <div class="card">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    <div class="personal-info">
                        <?php if ($current_user['role'] === 'patient'): ?>
                        <div class="info-item">
                            <div class="info-value"><?php echo $current_user['height'] ? $current_user['height'] . ' cm' : '--'; ?></div>
                            <div class="info-label">Height</div>
                        </div>
                        <div class="info-item">
                            <div class="info-value"><?php echo $current_user['weight'] ? $current_user['weight'] . ' kg' : '--'; ?></div>
                            <div class="info-label">Weight</div>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <div class="info-value"><?php echo $current_user['gender'] ? ucfirst($current_user['gender']) : '--'; ?></div>
                            <div class="info-label">Gender</div>
                        </div>
                        <div class="info-item">
                            <div class="info-value"><?php echo $current_user['birth_date'] ? floor((time() - strtotime($current_user['birth_date'])) / 31556926) : '--'; ?></div>
                            <div class="info-label">Age</div>
                        </div>
                    </div>
                    
                    <?php if ($bmi): ?>
                    <div class="bmi-display">
                        <div class="bmi-value"><?php echo $bmi; ?></div>
                        <div class="bmi-category">
                            <?php
                            if ($bmi < 18.5) {
                                echo 'Underweight';
                            } elseif ($bmi < 25) {
                                echo 'Normal weight';
                            } elseif ($bmi < 30) {
                                echo 'Overweight';
                            } else {
                                echo 'Obesity';
                            }
                            ?>
                        </div>
                        <div style="font-size: 0.8rem; margin-top: 5px; opacity: 0.8;">Body Mass Index</div>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 20px; color: var(--text-secondary);">
                        <i class="fas fa-calculator" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                        <p>Add height and weight to calculate BMI</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Emergency Contacts -->
                <div class="card">
                    <h3><i class="fas fa-phone-alt"></i> Emergency Contacts</h3>
                    <div class="contact-list">
                        <?php if (empty($emergency_contacts)): ?>
                            <div style="text-align: center; padding: 20px; color: var(--text-secondary);">
                                <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                                <p>No emergency contacts set</p>
                                <button onclick="openEmergencyContactsModal()" class="btn btn-primary" style="margin-top: 10px;">
                                    <i class="fas fa-plus"></i> Add Contacts
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($emergency_contacts as $contact): ?>
                            <div class="contact-item">
                                <div class="contact-name"><?php echo htmlspecialchars($contact['name']); ?></div>
                                <div class="contact-details">
                                    <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact['phone']); ?></div>
                                    <?php if ($contact['email']): ?>
                                    <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact['email']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div style="text-align: center; margin-top: 15px;">
                                <button onclick="openEmergencyContactsModal()" class="btn btn-primary">
                                    <i class="fas fa-cog"></i> Manage Contacts
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Location Information with Map -->
                <div class="card">
                    <h3><i class="fas fa-map-marker-alt"></i> My Location</h3>
                    <?php if ($location): ?>
                    <div class="location-map-container">
                        <div id="locationMap"></div>
                        <div class="map-controls">
                            <button class="map-btn" onclick="refreshLocation()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <button class="map-btn" id="trackingBtn" onclick="toggleLiveTracking()">
                                <i class="fas fa-play"></i> Live Track
                            </button>
                        </div>
                    </div>
                    <div class="location-details">
                        <div class="location-item">
                            <div class="location-value"><?php echo round($location['location_lat'], 6); ?></div>
                            <div class="location-label">Latitude</div>
                        </div>
                        <div class="location-item">
                            <div class="location-value"><?php echo round($location['location_lng'], 6); ?></div>
                            <div class="location-label">Longitude</div>
                        </div>
                    </div>
                    <div style="text-align: center; margin-top: 15px;">
                        <small style="color: var(--text-secondary);">
                            Last updated: <?php echo date('M j, g:i A', strtotime($location['recorded_at'])); ?>
                        </small>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 30px; color: var(--text-secondary);">
                        <i class="fas fa-map-marker-alt" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>No location data available</p>
                        <p style="font-size: 0.9rem; margin-top: 10px;">Location will be updated with your next health reading</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Health Statistics -->
                <div class="card">
                    <h3><i class="fas fa-chart-bar"></i> Average Health Readings</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['total_readings'] ?? 0; ?></div>
                            <div class="stat-label">Total Readings</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['avg_heart_rate'] ?? '--'; ?></div>
                            <div class="stat-label">Avg Heart Rate</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['avg_temperature'] ?? '--'; ?></div>
                            <div class="stat-label">Avg Temp (°C)</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['avg_spo2'] ?? '--'; ?></div>
                            <div class="stat-label">Avg SpO2 (%)</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['min_heart_rate'] ?? '--'; ?></div>
                            <div class="stat-label">Min Heart Rate</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['max_heart_rate'] ?? '--'; ?></div>
                            <div class="stat-label">Max Heart Rate</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <a href="profile.php" class="btn btn-primary">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <button onclick="openEmergencyContactsModal()" class="btn btn-primary">
                            <i class="fas fa-phone-alt"></i> Emergency Contacts
                        </button>
                        <button class="btn btn-primary" onclick="refreshData()">
                            <i class="fas fa-sync-alt"></i> Refresh Data
                        </button>
                        <button class="btn btn-danger" onclick="alert('In case of emergency, your contacts will be notified automatically based on your vital signs.')">
                            <i class="fas fa-bell"></i> Emergency Info
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Section with Filters -->
             <div class="stats-filters">
                <h3 style="color: var(--primary-color); margin-bottom: 20px;">Health Statistics & Trends</h3>
                        <form method="GET" action="patient_dashboard.php" class="filter-form">                            
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label class="filter-label">Time Period</label>
                                    <select name="period" class="filter-select">
                                        <option value="daily" <?php echo $period === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                        <option value="weekly" <?php echo $period === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                        <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                        <option value="yearly" <?php echo $period === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label class="filter-label">Vitals Type</label>
                                    <select name="vitals" class="filter-select">
                                        <option value="all" <?php echo $vitals_filter === 'all' ? 'selected' : ''; ?>>All Vitals</option>
                                        <option value="temperature" <?php echo $vitals_filter === 'temperature' ? 'selected' : ''; ?>>Temperature</option>
                                        <option value="heart_rate" <?php echo $vitals_filter === 'heart_rate' ? 'selected' : ''; ?>>Heart Rate</option>
                                        <option value="spo2" <?php echo $vitals_filter === 'spo2' ? 'selected' : ''; ?>>SpO2</option>
                                        <option value="blood_pressure" <?php echo $vitals_filter === 'blood_pressure' ? 'selected' : ''; ?>>Blood Pressure</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label class="filter-label">Health Status</label>
                                    <select name="status" class="filter-select">
                                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                        <option value="normal" <?php echo $status_filter === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                        <option value="warning" <?php echo $status_filter === 'warning' ? 'selected' : ''; ?>>Warning</option>
                                        <option value="critical" <?php echo $status_filter === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="apply-filters-btn">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>

            <!-- Charts -->
            <div class="charts-container">
                        <div class="chart-card">
                            <h3>Vitals Overview</h3>
                            <div class="chart-container">
                                <canvas id="vitalsChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-card">
                            <h3>Readings per Day</h3>
                            <div class="chart-container">
                                <canvas id="readingsChart"></canvas>
                            </div>
                        </div>
                    </div>

          <!-- Health History Table -->
                    <div class="history-table">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="margin: 0; color: #8B4E91;"><i class="fas fa-history"></i> Health History</h3>
                            <div class="last-updated">
                                Last updated: <span id="lastUpdateTime"><?php echo date('Y-m-d H:i:s'); ?></span>
                                <button onclick="refreshData()" class="btn" style="margin-left: 10px;">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            </div>
                        </div>
                        
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Temperature</th>
                                        <th>Heart Rate</th>
                                        <th>SpO2</th>
                                        <th>Blood Pressure</th>
                                        <th>Source</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($filtered_records as $record): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y H:i', strtotime($record['recorded_at'])); ?></td>
                                        <td><?php echo $record['body_temperature']; ?>°C</td>
                                        <td>
                                            <?php echo $record['heart_rate']; ?> bpm
                                            <?php 
                                            if ($record['heart_rate'] < 60) echo '<span class="status-danger">⚠</span>';
                                            elseif ($record['heart_rate'] > 100) echo '<span class="status-warning">⚠</span>';
                                            else echo '<span class="status-normal">✓</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo $record['spo2']; ?>%
                                            <?php 
                                            if ($record['spo2'] < 95) echo '<span class="status-danger">⚠</span>';
                                            else echo '<span class="status-normal">✓</span>';
                                            ?>
                                        </td>
                                        <td><?php echo $record['blood_pressure'] ?: '--'; ?></td>
                                        <td>
                                            <span style="text-transform: capitalize;"><?php echo $record['source']; ?></span>
                                            <?php if ($record['location_lat']): ?>
                                                <br><small>Location tracked</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $warnings = [];
                                            if ($record['heart_rate'] < 60 || $record['heart_rate'] > 100) $warnings[] = 'Heart rate';
                                            if ($record['spo2'] < 95) $warnings[] = 'SpO2';
                                            if ($record['body_temperature'] < 36.1 || $record['body_temperature'] > 37.2) $warnings[] = 'Temperature';
                                            
                                            if (empty($warnings)) {
                                                echo '<span class="status-normal">Normal</span>';
                                            } else {
                                                echo '<span class="status-danger">' . implode(', ', $warnings) . '</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($filtered_records)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                            No health records found for the selected filters.
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

    <!--Map details-->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script>
    // Map variables
    let locationMap;
    let patientMarker;
    let currentLat = <?php echo $current_lat; ?>;
    let currentLng = <?php echo $current_lng; ?>;
    let liveTracking = false;
    let trackingInterval;

    // Initialize location map
    function initializeLocationMap() {
        locationMap = L.map('locationMap').setView([currentLat, currentLng], 15);
        
        // Use different tile layers for light/dark mode
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        if (currentTheme === 'dark') {
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '© OpenStreetMap contributors, © CartoDB'
            }).addTo(locationMap);
        } else {
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(locationMap);
        }

        // Add patient marker
        const patientIcon = L.divIcon({
            html: '<div style="background: #6A2C70; color: white; border-radius: 50%; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);"><i class="fas fa-user" style="font-size: 12px;"></i></div>',
            className: 'patient-marker',
            iconSize: [25, 25]
        });
        
        patientMarker = L.marker([currentLat, currentLng], { icon: patientIcon })
            .addTo(locationMap)
            .bindPopup('Your Current Location')
            .openPopup();
    }

    // Refresh location
    function refreshLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                position => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    updateLocation(lat, lng);
                },
                error => {
                    console.log('Error getting location: ' + error.message);
                    // Fallback to server location
                    updateLocation(currentLat, currentLng);
                }
            );
        } else {
            console.log('Geolocation is not supported by this browser.');
            // Fallback to server location
            updateLocation(currentLat, currentLng);
        }
    }

    // Update location on map
    function updateLocation(lat, lng) {
        currentLat = lat;
        currentLng = lng;
        
        locationMap.setView([lat, lng], 15);
        patientMarker.setLatLng([lat, lng]);
        
        // Update location details display
        document.querySelectorAll('.location-value')[0].textContent = lat.toFixed(6);
        document.querySelectorAll('.location-value')[1].textContent = lng.toFixed(6);
    }

    // Toggle live tracking
    function toggleLiveTracking() {
        liveTracking = !liveTracking;
        const trackingBtn = document.getElementById('trackingBtn');
        
        if (liveTracking) {
            trackingBtn.innerHTML = '<i class="fas fa-stop"></i> Stop';
            trackingBtn.classList.add('tracking');
            
            // Start tracking
            trackingInterval = setInterval(() => {
                refreshLocation();
            }, 30000); // Update every 30 seconds
            
            console.log('Live tracking started');
        } else {
            trackingBtn.innerHTML = '<i class="fas fa-play"></i> Live Track';
            trackingBtn.classList.remove('tracking');
            
            // Stop tracking
            if (trackingInterval) {
                clearInterval(trackingInterval);
            }
            
            console.log('Live tracking stopped');
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
        if (locationMap) {
            locationMap.remove();
            initializeLocationMap();
        }
    });
    
    function updateThemeIcon(theme) {
        const icon = themeToggle.querySelector('i');
        icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
    }

    // Refresh data
    function refreshData() {
        const refreshBtn = event.target;
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
        refreshBtn.disabled = true;

        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }

    // Modal Functions
    function openEmergencyContactsModal() {
        document.getElementById('emergencyContactsModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('emergencyContactsModal').style.display = 'none';
    }

    // Close modal when clicking on X or outside
    document.querySelector('.close-modal').addEventListener('click', closeModal);
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('emergencyContactsModal');
        if (event.target === modal) {
            closeModal();
        }
    });

    // Auto-format phone number
    document.querySelector('input[name="phone"]')?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 0) {
            value = '+' + value;
        }
        e.target.value = value;
    });

    // Chart data from PHP
    const chartData = <?php echo json_encode($chart_data); ?>;
    
    // Initialize charts
    function initializeCharts() {
        if (chartData.length === 0) {
            // Show message if no chart data
            document.querySelectorAll('.chart-card canvas').forEach(canvas => {
                canvas.parentElement.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--text-secondary);"><i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i><p>No data available for the selected period</p></div>';
            });
            return;
        }

        // Combined Vitals Chart
        new Chart(document.getElementById('vitalsChart'), {
            type: 'line',
            data: {
                labels: chartData.map(item => new Date(item.date).toLocaleDateString()),
                datasets: [
                    {
                        label: 'Heart Rate (bpm)',
                        data: chartData.map(item => item.avg_heart_rate),
                        borderColor: '#6A2C70',
                        backgroundColor: 'rgba(106, 44, 112, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: false,
                        yAxisID: 'y'
                    },
                    {
                        label: 'SpO2 (%)',
                        data: chartData.map(item => item.avg_spo2),
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: false,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Temperature (°C)',
                        data: chartData.map(item => item.avg_temp),
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: false,
                        yAxisID: 'y2'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                stacked: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Heart Rate (bpm)'
                        },
                        suggestedMin: 50,
                        suggestedMax: 120
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'SpO2 (%)'
                        },
                        min: 85,
                        max: 100,
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                    y2: {
                        type: 'linear',
                        display: false,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Temperature (°C)'
                        },
                        suggestedMin: 35,
                        suggestedMax: 39,
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });

        // Readings Chart
        new Chart(document.getElementById('readingsChart'), {
            type: 'bar',
            data: {
                labels: chartData.map(item => new Date(item.date).toLocaleDateString()),
                datasets: [{
                    label: 'Readings per Day',
                    data: chartData.map(item => item.readings_count),
                    backgroundColor: '#27ae60',
                    borderColor: '#219652',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Auto-refresh every 30 seconds
    setInterval(() => {
        document.getElementById('lastUpdateTime').textContent = new Date().toLocaleString();
    }, 30000);

    // Initialize maps and charts when page loads
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($location): ?>
        initializeLocationMap();
        <?php endif; ?>
        initializeCharts();
    });
</script>
</body>
</html>