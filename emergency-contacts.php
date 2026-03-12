<?php
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
$success = '';
$error = '';

// Initialize variables
$patient = null;
$contacts = [];

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

    // Get contacts for the selected patient or current user
    $target_user_id = $patient_id ?: $user_id;
    
    $stmt = $pdo->prepare("SELECT * FROM emergency_contacts WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$target_user_id]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Include CallMeBot WhatsApp functions
    require_once 'callmebot.php';

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_contact'])) {
            $name = $_POST['name'];
            $phone = $_POST['phone'];
            $email = $_POST['email'];
            $relationship = $_POST['relationship'];
            $share_location = isset($_POST['share_location']) ? 1 : 0;

            $stmt = $pdo->prepare("
                INSERT INTO emergency_contacts (user_id, name, phone, email, relationship, share_location) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$target_user_id, $name, $phone, $email, $relationship, $share_location]);
            
            $success = "Emergency contact added successfully!";
            // Refresh contacts
            $stmt = $pdo->prepare("SELECT * FROM emergency_contacts WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$target_user_id]);
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (isset($_POST['delete_contact'])) {
            $contact_id = $_POST['contact_id'];
            
            $stmt = $pdo->prepare("DELETE FROM emergency_contacts WHERE id = ? AND user_id = ?");
            $stmt->execute([$contact_id, $target_user_id]);
            
            $success = "Emergency contact removed successfully!";
            // Refresh contacts
            $stmt = $pdo->prepare("SELECT * FROM emergency_contacts WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$target_user_id]);
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Test WhatsApp alert
        if (isset($_POST['test_alert'])) {
            $contact_id = $_POST['contact_id'];
            
            // Get the contact details first
            $stmt = $pdo->prepare("SELECT * FROM emergency_contacts WHERE id = ? AND user_id = ?");
            $stmt->execute([$contact_id, $target_user_id]);
            $contact = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($contact) {
                // Send test WhatsApp alert
                $result = sendTestWhatsAppAlert($pdo, $target_user_id, $contact_id, $patient);
                
                if ($result['success']) {
                    $success = "WhatsApp test alert sent to {$contact['name']} with vitals and location information!";
                } else {
                    $error = "Failed to send WhatsApp test alert to {$contact['name']}. Error code: {$result['http_code']}";
                    
                    // Add more specific error messages
                    if ($result['http_code'] == 400) {
                        $error .= " - Invalid phone number format";
                    } elseif ($result['http_code'] == 401) {
                        $error .= " - Invalid API key";
                    } elseif ($result['http_code'] == 429) {
                        $error .= " - Rate limit exceeded, please try again later";
                    }
                }
            } else {
                $error = "Contact not found or you don't have permission to send alerts to this contact.";
            }
        }
    }

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$current_user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Contacts - Health Aid</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .contacts-container {
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
        <div class="contacts-container">
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
                             onclick="window.location.href='emergency-contacts.php?patient_id=<?php echo $p['id']; ?>'">
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
            
            <!-- Contacts Content -->
            <div class="contacts-content">
                <?php if ($patient_id && $patient): ?>
                    <div class="content-header">
                        <h1 class="page-title">
                            Emergency Contacts: <?php echo htmlspecialchars($patient['full_name']); ?>
                        </h1>
                    </div>

                    <?php if ($success): ?>
                        <div class="success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="error"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="contacts-grid">
                        <!-- Add Contact Form -->
                        <div class="add-contact-form">
                            <h2 style="color: var(--primary-color); margin-bottom: 25px;">Add Emergency Contact</h2>
                            
                            <form method="POST">
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

                                <button type="submit" name="add_contact" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-plus"></i> Add Emergency Contact
                                </button>
                            </form>
                        </div>

                        <!-- Contacts List -->
                        <div class="contacts-list">
                            <h2 style="color: var(--primary-color); margin-bottom: 25px;">
                                Emergency Contacts (<?php echo count($contacts); ?>)
                            </h2>

                            <?php if (empty($contacts)): ?>
                                <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                    <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                                    <h3>No Emergency Contacts</h3>
                                    <p>Add contacts to receive alerts when health vitals are critical.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($contacts as $contact): ?>
                                <div class="contact-card">
                                    <div class="contact-header">
                                        <div>
                                            <div class="contact-name"><?php echo htmlspecialchars($contact['name']); ?></div>
                                            <div class="contact-relationship">
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
                                        </div>
                                    </div>

                                    <div class="contact-details">
                                        <div class="contact-detail">
                                            <i class="fas fa-phone" style="color: #27AE60;"></i>
                                            <span><?php echo htmlspecialchars($contact['phone']); ?></span>
                                        </div>
                                        <?php if ($contact['email']): ?>
                                        <div class="contact-detail">
                                            <i class="fas fa-envelope" style="color: var(--primary-color);"></i>
                                            <span><?php echo htmlspecialchars($contact['email']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="contact-detail">
                                            <i class="fas fa-map-marker-alt" style="color: <?php echo $contact['share_location'] ? '#27AE60' : '#666'; ?>;"></i>
                                            <span>
                                                <?php echo $contact['share_location'] ? 'Location sharing enabled' : 'Location sharing disabled'; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="contact-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                            <button type="submit" name="test_alert" class="btn btn-warning" 
                                                    onclick="return confirm('This will send a TEST WhatsApp message to <?php echo htmlspecialchars($contact['name']); ?>. Continue?');">
                                                <i class="fas fa-bell"></i> Test Alert
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this emergency contact?');">
                                            <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                            <button type="submit" name="delete_contact" class="btn btn-danger">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                        <i class="fas fa-phone-alt" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;"></i>
                        <h2>Select a Patient</h2>
                        <p>Choose a patient from the sidebar to view and manage their emergency contacts.</p>
                    </div>
                <?php endif; ?>
                
                <!-- Alert Settings -->
                    <div class="alert-settings">
                        <h2 style="color: var(--primary-color); margin-bottom: 25px;">Emergency Alert Settings</h2>
                        
                        <div class="settings-grid">
                            <div class="setting-item">
                                <h4><i class="fas fa-heartbeat" style="color: #E74C3C;"></i> Heart Rate Alerts</h4>
                                <p>Send emergency alerts when heart rate is:</p>
                                <ul>
                                    <li>Below 50 bpm (Bradycardia)</li>
                                    <li>Above 120 bpm (Tachycardia)</li>
                                </ul>
                            </div>
                            
                            <div class="setting-item">
                                <h4><i class="fas fa-lungs" style="color: #F39C12;"></i> SpO2 Alerts</h4>
                                <p>Send emergency alerts when blood oxygen is:</p>
                                <ul>
                                    <li>Below 90% (Hypoxemia)</li>
                                </ul>
                            </div>
                            
                            <div class="setting-item">
                                <h4><i class="fas fa-thermometer-half" style="color: #e74c3c;"></i> Temperature Alerts</h4>
                                <p>Send emergency alerts when body temperature is:</p>
                                <ul>
                                    <li>Below 35°C (Hypothermia)</li>
                                    <li>Above 39°C (High Fever)</li>
                                </ul>
                            </div>
                            
                            <div class="setting-item">
                                <h4><i class="fas fa-map-marker-alt" style="color: var(--primary-color);"></i> Location Information</h4>
                                <p>Emergency SMS will include:</p>
                                <ul>
                                    <li>Patient's name and vital signs</li>
                                    <li>GPS coordinates (if enabled)</li>
                                    <li>Google Maps link to location</li>
                                    <li>Time of the alert</li>
                                </ul>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>

    <script>
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
            
        });
        
        function updateThemeIcon(theme) {
            const icon = themeToggle.querySelector('i');
            icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }

        // Auto-format phone number
        document.querySelector('input[name="phone"]')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = '+' + value;
            }
            e.target.value = value;
        });
    </script>
</body>
</html>