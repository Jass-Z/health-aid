<?php
// dashboard.php
require_once __DIR__ . '/includes/config.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_patient_id = $_GET['patient_id'] ?? null;

// Initialize variables with default values
$patients = [];
$selected_patient = null;
$patient_vitals = null;
$realtime_data = null;
$is_live_data = false;
$error = null;

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all patients (users with role = 'patient')
    $patients_stmt = $pdo->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM health_records WHERE user_id = u.id) as reading_count,
               (SELECT recorded_at FROM health_records WHERE user_id = u.id ORDER BY recorded_at DESC LIMIT 1) as last_reading
        FROM users u 
        WHERE u.role = 'patient' 
        ORDER BY u.full_name
    ");
    $patients_stmt->execute();
    $patients = $patients_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get selected patient data
    if ($selected_patient_id) {
        $patient_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'patient'");
        $patient_stmt->execute([$selected_patient_id]);
        $selected_patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($selected_patient) {
            // Get latest vitals for selected patient with real-time data
            $vitals_stmt = $pdo->prepare("
                SELECT hr.*, 
                       TIMESTAMPDIFF(SECOND, hr.recorded_at, NOW()) as seconds_ago,
                       hr.fall_detected,
                       hr.battery_level,
                       hr.alert_stage
                FROM health_records hr 
                WHERE hr.user_id = ? 
                ORDER BY hr.recorded_at DESC 
                LIMIT 1
            ");
            $vitals_stmt->execute([$selected_patient_id]);
            $patient_vitals = $vitals_stmt->fetch(PDO::FETCH_ASSOC);
            $realtime_data = $patient_vitals;
            
            // Check if data is recent (less than 1 minute old)
            $is_live_data = $realtime_data && $realtime_data['seconds_ago'] < 60;
        }
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Calculate BMI for selected patient
$bmi = null;
if ($selected_patient && $selected_patient['height'] && $selected_patient['weight']) {
    $height_in_meters = $selected_patient['height'] / 100;
    $bmi = round($selected_patient['weight'] / ($height_in_meters * $height_in_meters), 1);
}

$current_user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Health Aid</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #6A2C70;
            --primary-light: #8B4E91;
            --primary-dark: #4D1E53;
            --secondary-color: #2E8B57;
            --accent-color: #FF6B6B;
            --text-primary: #2D3748;
            --text-secondary: #4A5568;
            --bg-primary: #FFFFFF;
            --bg-secondary: #F7FAFC;
            --bg-card: #FFFFFF;
            --border-color: #E2E8F0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        [data-theme="dark"] {
            --primary-color: #8B4E91;
            --primary-light: #9C5FA3;
            --primary-dark: #6A2C70;
            --text-primary: #F7FAFC;
            --text-secondary: #E2E8F0;
            --bg-primary: #1A202C;
            --bg-secondary: #2D3748;
            --bg-card: #2D3748;
            --border-color: #4A5568;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; transition: background-color 0.3s, color 0.3s, border-color 0.3s; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-primary); color: var(--text-primary); line-height: 1.6; }

        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .logo { font-size: 24px; font-weight: 700; color: var(--primary-color); text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-links { display: flex; gap: 30px; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text-primary); font-weight: 500; transition: color 0.3s; padding: 8px 16px; border-radius: 8px; }
        .nav-links a:hover { color: var(--primary-color); background: var(--bg-secondary); }
        .nav-links a.active { color: var(--primary-color); background: var(--bg-secondary); }

        .user-info { display: flex; align-items: center; gap: 15px; }

        .profile-pic { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color); cursor: pointer; transition: transform 0.3s; }
        .profile-pic:hover { transform: scale(1.05); }

        .theme-toggle { background: none; border: none; color: var(--text-primary); font-size: 1.2rem; cursor: pointer; padding: 8px; border-radius: 8px; transition: background 0.3s; }
        .theme-toggle:hover { background: var(--bg-secondary); }

        .dashboard-container { display: grid; grid-template-columns: 350px 1fr; gap: 30px; padding: 30px 0; min-height: calc(100vh - 80px); }

        .patients-sidebar { 
            background: var(--bg-card); 
            border-radius: 12px; 
            padding: 25px; 
            box-shadow: var(--shadow); 
            border: 1px solid var(--border-color); 
            max-height: calc(100vh - 140px); 
            overflow-y: auto; 
        }
        .patients-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 25px; 
            padding-bottom: 15px; 
            border-bottom: 2px solid var(--border-color); 
        }
        .patient-card { 
            background: var(--bg-secondary); 
            border-radius: 10px; 
            padding: 20px; 
            margin-bottom: 15px; 
            cursor: pointer; 
            transition: all 0.3s; 
            border: 2px solid transparent; 
        }
        .patient-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); border-color: var(--primary-light); }
        .patient-card.active { border-color: var(--primary-color); }
        .patient-name { font-size: 1.1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 8px; }
        .patient-info { display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-secondary); }
        .patient-status { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }

        .status-normal { background: #C6F6D5; color: #22543D; }
        .status-warning { background: #FEFCBF; color: #744210; }
        .status-critical { background: #FED7D7; color: #742A2A; }
        .status-offline { background: #E2E8F0; color: #4A5568; }

        .main-content { display: flex; flex-direction: column; gap: 25px; }
        .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }

        .page-title { font-size: 2rem; font-weight: 700;color: var(--text-primary); }

        .patient-overview { background: var(--bg-card); border-radius: 12px; padding: 30px; box-shadow: var(--shadow); border: 1px solid var(--border-color); }
        .overview-header { display: flex; align-items: center; gap: 20px; margin-bottom: 25px; }
        .patient-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-color); }
        .patient-details h2 { font-size: 1.8rem; margin-bottom: 5px; color: var(--text-primary); }
        .patient-meta { color: var(--text-secondary); font-size: 0.9rem; }

        /* Live Status Indicator */
        .live-status { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            margin-bottom: 20px; 
            padding: 10px 15px; 
            background: var(--bg-secondary); 
            border-radius: 8px; 
            border-left: 4px solid var(--border-color); 
        }

        .status-indicator { display: flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-indicator.live { background: #27ae60; color: white; border-left: 4px solid #27ae60; }
        .status-indicator.offline { background: #e74c3c; color: white; border-left: 4px solid #e74c3c; }
        .status-indicator i { font-size: 0.6rem; }

        .device-info { display: flex; gap: 20px; font-size: 0.85rem; color: var(--text-secondary); }
        .device-info span { display: flex; align-items: center; gap: 5px; }

        .vitals-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 25px; }
        .vital-card { background: var(--bg-secondary); border-radius: 10px; padding: 20px; text-align: center; border: 1px solid var(--border-color); }
        .vital-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .vital-title { font-size: 0.9rem; color: var(--text-secondary); font-weight: 500; }
        .vital-icon { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; }
        .vital-value { font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-bottom: 5px; }
        .vital-status { 
            font-size: 0.8rem; 
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 12px;
        }

        .status-normal { color: #27AE60; }
        .status-warning { color: #F39C12; }
        .status-critical { color: #E74C3C; }

        .personal-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 25px; }
        .info-card { background: var(--bg-secondary); border-radius: 10px; padding: 20px; text-align: center; border: 1px solid var(--border-color); }
        .info-value { font-size: 1.5rem;  font-weight: 700; color: var(--primary-color); margin-top: 20px; }
        .info-label { font-size: 0.9rem; color: var(--text-secondary); }

        .bmi-display {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        .bmi-value { font-size: 2rem; font-weight: 700; margin-bottom: 5px; }
        .bmi-category { font-size: 1rem; opacity: 0.9; }

        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 25px; }
        .action-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .action-btn:hover { background: var(--primary-dark); transform: translateY(-2px); }

        .no-patient { text-align: center; padding: 60px 20px; color: var(--text-secondary); }
        .no-patient i { font-size: 4rem; margin-bottom: 20px; opacity: 0.5; }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer; 
            border: none;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }

        /* Alert Stage Badges */
        .alert-stage { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; margin-left: 10px; }
        .stage-normal { background: #27ae60; color: white; }
        .stage-moderate { background: #f39c12; color: white; }
        .stage-critical { background: #e74c3c; color: white; }

        /* Scrollbar Styling */
        .patients-sidebar::-webkit-scrollbar { width: 6px; }
        .patients-sidebar::-webkit-scrollbar-track { background: var(--bg-secondary); border-radius: 3px; }
        .patients-sidebar::-webkit-scrollbar-thumb { background: var(--primary-light); border-radius: 3px; }
        .patients-sidebar::-webkit-scrollbar-thumb:hover { background: var(--primary-color); }

        @media (max-width: 1024px) { .dashboard-container { grid-template-columns: 1fr; } .patients-sidebar { max-height: 300px; } }

    /* Tablet and smaller desktop */
    @media (max-width: 1024px) { 
        .dashboard-container { 
            grid-template-columns: 1fr; 
            gap: 20px;
        } 

        .patients-sidebar { 
            max-height: 300px; 
            order: 2;
        }
            
         .main-content {
            order: 1;
            }
        } 

    /* Mobile devices (phones, 768px and down) */
    @media (max-width: 768px) {
        .container { 
            padding: 0 15px;
            } 
               
    .dashboard-container {
        padding: 15px 0;
        gap: 15px;
    }
    
    /* Navigation adjustments */
    .nav-links {
        gap: 15px;
    }
    
    .nav-links a {
        padding: 6px 12px;
        font-size: 0.9rem;
    }
    
    /* Patient sidebar */
    .patients-sidebar {
        padding: 15px;
        max-height: 250px;
    }
    
    .patients-header {
        margin-bottom: 15px;
        padding-bottom: 10px;
    }
    
    .patient-card {
        padding: 15px;
        margin-bottom: 10px;
    }
    
    .patient-name {
        font-size: 1rem;
    }
    
    .patient-info {
        flex-direction: column;
        gap: 5px;
    }
    
    /* Patient overview */
    .patient-overview {
        padding: 20px;
    }
    
    .overview-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .patient-details h2 {
        font-size: 1.5rem;
    }
    
    .patient-meta {
        font-size: 0.85rem;
    }
    
    /* Live status */
    .live-status {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        padding: 15px;
    }
    
    .device-info {
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }
    
    /* Grid layouts */
    .vitals-grid {
        grid-template-columns: 1fr;
        gap: 15px;
        margin-top: 20px;
    }
    
    .personal-info-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 20px;
    }
    
    .bmi-display {
        grid-column: 1 / -1;
    }
    
    .vital-card,
    .info-card {
        padding: 15px;
    }
    
    .vital-value {
        font-size: 1.5rem;
    }
    
    .info-value {
        font-size: 1.3rem;
    }
    
    .bmi-value {
        font-size: 1.8rem;
    }
    
    /* Quick actions */
    .quick-actions {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 20px;
    }
    
    .action-btn {
        padding: 10px 15px;
        font-size: 0.85rem;
    }
    
    /* Page title */
    .page-title {
        font-size: 1.5rem;
    }
}

/* Small mobile devices (480px and down) */
@media (max-width: 480px) {
    .container {
        padding: 0 10px;
    }
    
    .dashboard-container {
        padding: 10px 0;
    }
    
    .patients-sidebar {
        padding: 12px;
        max-height: 200px;
    }
    
    .patient-overview {
        padding: 15px;
    }
    
    .patient-avatar {
        width: 60px;
        height: 60px;
    }
    
    .patient-details h2 {
        font-size: 1.3rem;
    }
    
    .personal-info-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .vital-value {
        font-size: 1.3rem;
    }
    
    .info-value {
        font-size: 1.2rem;
    }
    
    .bmi-value {
        font-size: 1.5rem;
    }
    
    /* Alert stage badges */
    .alert-stage {
        display: block;
        margin-left: 0;
        margin-top: 5px;
        width: fit-content;
    }
    
    /* No patient state */
    .no-patient {
        padding: 40px 15px;
    }
    
    .no-patient i {
        font-size: 3rem;
    }
    
    .no-patient h2 {
        font-size: 1.3rem;
    }
}

/* Very small devices (360px and down) */
@media (max-width: 360px) {
    .nav-links {
        gap: 10px;
    }
    
    .nav-links a {
        padding: 5px 8px;
        font-size: 0.8rem;
    }
    
    .user-info {
        gap: 10px;
    }
    
    .profile-pic {
        width: 35px;
        height: 35px;
    }
    
    .vital-card,
    .info-card {
        padding: 12px;
    }
    
    .action-btn {
        padding: 8px 12px;
        font-size: 0.8rem;
    }
}
    </style>
</head>

<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <div class="dashboard-container">
            <!-- Patients Sidebar -->
            <div class="patients-sidebar">
                <div class="patients-header">
                    <h2 style="color: var(--text-primary);">Patients</h2>
                    <span style="color: var(--text-secondary); font-size: 0.9rem;"><?php echo count($patients); ?> patients</span>
                </div>
                
                <?php if (empty($patients)): ?>
                    <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary);">
                        <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>No patients found in the system</p>
                        <p style="font-size: 0.9rem; margin-top: 10px; color: var(--text-secondary);">
                            Patients need to register with the 'patient' role to appear here.
                        </p>
                    </div>

                <?php else: ?>
                    <?php foreach ($patients as $patient): 
                        // Determine patient status based on recent data
                        $last_reading_time = $patient['last_reading'] ? strtotime($patient['last_reading']) : 0;
                        $minutes_ago = $last_reading_time ? floor((time() - $last_reading_time) / 60) : null;
                        
                        if (!$last_reading_time) {
                            $status = 'offline';
                            $status_text = 'No Data';
                        } elseif ($minutes_ago > 5) {
                            $status = 'offline';
                            $status_text = 'Offline';
                        } else {
                            $status = 'normal';
                            $status_text = 'Online';
                        }
                    ?>

                        <div class="patient-card <?php echo $selected_patient_id == $patient['id'] ? 'active' : ''; ?>" 
                             onclick="window.location.href='dashboard.php?patient_id=<?php echo $patient['id']; ?>'">
                            <div class="patient-name">
                                <?php echo htmlspecialchars($patient['full_name']); ?>
                                <span class="patient-status status-<?php echo $status; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </div>

                            <div class="patient-info">
                                <span><?php echo $patient['reading_count']; ?> readings</span>
                                <span>
                                    <?php if ($patient['last_reading']): ?>
                                        <?php echo date('M j, g:i A', strtotime($patient['last_reading'])); ?>
                                    <?php else: ?>
                                        No data
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Main Dashboard -->
            <div class="main-content">
                <?php if ($selected_patient): ?>

                    <!-- Patient Overview -->
                    <div class="patient-overview">
                        <div class="overview-header">
                            <img src="<?php echo !empty($selected_patient['profile_picture']) ? 'uploads/' . $selected_patient['profile_picture'] : './uploads/profile_placeholder_img.png'; ?>" 
                                 alt="Patient" class="patient-avatar">
                            <div class="patient-details">
                                <h2>
                                    <?php echo htmlspecialchars($selected_patient['full_name']); ?>
                                    <?php if ($realtime_data && $realtime_data['alert_stage']): ?>
                                        <span class="alert-stage stage-<?php 
                                            echo $realtime_data['alert_stage'] == 1 ? 'normal' : 
                                                 ($realtime_data['alert_stage'] == 2 ? 'moderate' : 'critical'); 
                                        ?>">
                                            <?php echo $realtime_data['alert_stage'] == 1 ? 'Normal' : 
                                                  ($realtime_data['alert_stage'] == 2 ? 'Moderate' : 'Critical'); ?>
                                        </span>
                                    <?php endif; ?>
                                </h2>

                                <div class="patient-meta">
                                    <span>Patient ID: #<?php echo $selected_patient['id']; ?></span> 
                                    <span>Age: <?php echo $selected_patient['birth_date'] ? floor((time() - strtotime($selected_patient['birth_date'])) / 31556926) : 'N/A'; ?></span> • 
                                    <span>Gender: <?php echo ucfirst($selected_patient['gender'] ?? 'Not specified'); ?></span>
                                    <?php if ($selected_patient['device_id']): ?>
                                    <span>Device: <?php echo htmlspecialchars($selected_patient['device_id']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

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
                                <?php else: ?>
                                    <span>No recent data from device</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div class="personal-info-grid">
                            <?php if ($bmi): ?>
                                <div class="bmi-display">
                                    <div class="bmi-value"><?php echo $bmi; ?></div>
                                    <div class="bmi-category">
                                        <?php
                                        if ($bmi < 18.5) { echo 'Underweight'; } 
                                        elseif ($bmi < 25) { echo 'Normal weight'; } 
                                        elseif ($bmi < 30) { echo 'Overweight'; } 
                                        else { echo 'Obesity'; }
                                        ?>
                                    </div>
                                    <div style="font-size: 0.8rem; margin-top: 5px; opacity: 0.8;"><b>BMI</b> - Body Mass Index </div>
                                </div>

                            <?php endif; ?>
                            <div class="info-card">
                                <div class="info-value"><?php echo $selected_patient['height'] ? $selected_patient['height'] . ' cm' : '--'; ?></div>
                                <div class="info-label">Height</div>
                            </div>
                            <div class="info-card">
                                <div class="info-value"><?php echo $selected_patient['weight'] ? $selected_patient['weight'] . ' kg' : '--'; ?></div>
                                <div class="info-label">Weight</div>
                            </div>
                            <div class="info-card">
                                <div class="info-value"><?php echo $selected_patient['gender'] ? ucfirst($selected_patient['gender']) : '--'; ?></div>
                                <div class="info-label">Gender</div>
                            </div>
                        </div>

                        <!-- Vital Signs Grid -->
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

                                <div class="vital-status <?php 
                                    if ($patient_vitals && $patient_vitals['heart_rate']) {
                                        if ($patient_vitals['heart_rate'] < 60) echo 'status-critical';
                                        elseif ($patient_vitals['heart_rate'] > 100) echo 'status-warning';
                                        else echo 'status-normal';
                                    }
                                ?>">
                                    <?php
                                    if ($patient_vitals && $patient_vitals['heart_rate']) {
                                        if ($patient_vitals['heart_rate'] < 60) { echo 'Low'; } 
                                        elseif ($patient_vitals['heart_rate'] > 100) { echo 'High'; } 
                                        else { echo 'Normal'; } } 
                                        else { echo 'No data'; }
                                    ?>
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

                                <div class="vital-status <?php 
                                    if ($patient_vitals && $patient_vitals['spo2']) {
                                        if ($patient_vitals['spo2'] < 95) echo 'status-critical';
                                        elseif ($patient_vitals['spo2'] < 97) echo 'status-warning';
                                        else echo 'status-normal';
                                    }
                                ?>">

                                    <?php
                                    if ($patient_vitals && $patient_vitals['spo2']) {
                                        if ($patient_vitals['spo2'] < 95) { echo 'Low'; } 
                                        elseif ($patient_vitals['spo2'] < 97) { echo 'Warning'; } 
                                        else { echo 'Normal'; } } 
                                        else { echo 'No data'; }
                                    ?>
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

                                <div class="vital-status <?php 
                                    if ($patient_vitals && $patient_vitals['body_temperature']) {
                                        if ($patient_vitals['body_temperature'] < 36.1) echo 'status-critical';
                                        elseif ($patient_vitals['body_temperature'] > 37.2) echo 'status-warning';
                                        else echo 'status-normal';
                                    }
                                ?>">
                                    <?php
                                    if ($patient_vitals && $patient_vitals['body_temperature']) {
                                        if ($patient_vitals['body_temperature'] < 36.1) { echo 'Low'; } 
                                        elseif ($patient_vitals['body_temperature'] > 37.2) { echo 'High'; } 
                                        else { echo 'Normal'; } } 
                                        else { echo 'No data'; }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="vital-card">
                                <div class="vital-header">
                                    <div class="vital-title">Blood Pressure</div>
                                    <div class="vital-icon" style="background-color: #5C6BC0;">
                                        <i class="fas fa-tint"></i>
                                    </div>
                                </div>

                                <div class="vital-value" style="margin-top: 25px;">
                                    <?php echo $patient_vitals ? $patient_vitals['blood_pressure'] : '--'; ?>
                                </div>
                                
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="quick-actions">
                            <a href="statistics.php?patient_id=<?php echo $selected_patient_id; ?>" class="action-btn">
                                <i class="fas fa-chart-line"></i> View Statistics
                            </a>
                            <a href="location-map.php?patient_id=<?php echo $selected_patient_id; ?>" class="action-btn">
                                <i class="fas fa-map-marker-alt"></i> Track Location
                            </a>
                            <a href="emergency-contacts.php?patient_id=<?php echo $selected_patient_id; ?>" class="action-btn">
                                <i class="fas fa-phone-alt"></i> Emergency Contacts
                            </a>
                            <a href="profile.php?patient_id=<?php echo $selected_patient_id; ?>" class="action-btn">
                                <i class="fas fa-user"></i> Patient Profile
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- No Patient Selected -->
                    <div class="no-patient">
                        <i class="fas fa-user-md"></i>
                        <h2>Welcome, Dr. <?php echo htmlspecialchars($current_user['full_name']); ?></h2>
                        <p style="margin-bottom: 20px; color: var(--text-secondary);">
                            <?php if (!empty($patients)): ?>
                                Select a patient from the sidebar to view their health dashboard and monitoring data.
                            <?php else: ?>
                                No patients are currently registered in the system.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($patients)): ?>
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                Click on any patient card to view their details
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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

        //  REAL-TIME DATA FETCHING 
        function fetchLiveData() {
            if (!<?php echo $selected_patient_id ? 'true' : 'false'; ?>) return;
            
            fetch(`get_live_data.php?patient_id=<?php echo $selected_patient_id; ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateVitalsDisplay(data.data);
                        updateStatusIndicators(data.data);
                    }
                })
                .catch(error => console.error('Error fetching live data:', error));
        }

        function updateVitalsDisplay(data) {
            // Update heart rate
            const hrElement = document.querySelector('.vital-card:nth-child(1) .vital-value');
            if (hrElement) {
                hrElement.textContent = data.heart_rate ? data.heart_rate + ' bpm' : '--';
            }
            
            // Update SpO2
            const spo2Element = document.querySelector('.vital-card:nth-child(2) .vital-value');
            if (spo2Element) {
                spo2Element.textContent = data.spo2 ? data.spo2 + '%' : '--';
            }
            
            // Update temperature
            const tempElement = document.querySelector('.vital-card:nth-child(3) .vital-value');
            if (tempElement) {
                tempElement.textContent = data.body_temperature ? data.body_temperature + '°C' : '--';
            }
            
            // Update blood pressure
            const bpElement = document.querySelector('.vital-card:nth-child(4) .vital-value');
            if (bpElement) {
                bpElement.textContent = data.blood_pressure || '--';
            }
            
            // Update status indicators
            updateVitalStatus('heart_rate', data.heart_rate);
            updateVitalStatus('spo2', data.spo2);
            updateVitalStatus('temperature', data.body_temperature);
        }

        function updateVitalStatus(type, value) {
            let statusElement, statusText, statusClass;
            
            switch(type) {
                case 'heart_rate':
                    statusElement = document.querySelector('.vital-card:nth-child(1) .vital-status');
                    if (!value) {
                        statusText = 'No data';
                        statusClass = '';
                    } else if (value < 60) {
                        statusText = 'Low';
                        statusClass = 'status-critical';
                    } else if (value > 100) {
                        statusText = 'High';
                        statusClass = 'status-warning';
                    } else {
                        statusText = 'Normal';
                        statusClass = 'status-normal';
                    } break;
                    
                case 'spo2':
                    statusElement = document.querySelector('.vital-card:nth-child(2) .vital-status');
                    if (!value) {
                        statusText = 'No data';
                        statusClass = '';
                    } else if (value < 95) {
                        statusText = 'Low';
                        statusClass = 'status-critical';
                    } else if (value < 97) {
                        statusText = 'Warning';
                        statusClass = 'status-warning';
                    } else {
                        statusText = 'Normal';
                        statusClass = 'status-normal';
                    } break;
                    
                case 'temperature':
                    statusElement = document.querySelector('.vital-card:nth-child(3) .vital-status');
                    if (!value) {
                        statusText = 'No data';
                        statusClass = '';
                    } else if (value < 36.1) {
                        statusText = 'Low';
                        statusClass = 'status-critical';
                    } else if (value > 37.2) {
                        statusText = 'High';
                        statusClass = 'status-warning';
                    } else {
                        statusText = 'Normal';
                        statusClass = 'status-normal';
                    } break; }
            
            if (statusElement) { statusElement.textContent = statusText;
                statusElement.className = 'vital-status ' + statusClass; } }

        function updateStatusIndicators(data) {
            // Update live status
            const isLiveData = data.seconds_ago && data.seconds_ago < 120;
            const statusIndicator = document.querySelector('.status-indicator');
            const deviceInfo = document.querySelector('.device-info');
            
            if (statusIndicator) {
                statusIndicator.className = `status-indicator ${isLiveData ? 'live' : 'offline'}`;
                statusIndicator.innerHTML = `<i class="fas fa-circle"></i> ${isLiveData ? 'Live Data' : 'Device Offline'}`; }
            
            if (deviceInfo) {
                let deviceInfoHTML = '';
                if (data.seconds_ago) {
                    deviceInfoHTML += `<span><i class="fas fa-clock"></i> Last update: ${data.seconds_ago} seconds ago</span>`; }
                if (data.battery_level) {
                    deviceInfoHTML += `<span><i class="fas fa-battery-half"></i> Battery: ${data.battery_level}%</span>`; }
                if (data.fall_detected) {
                    deviceInfoHTML += `<span style="color: #e74c3c;"><i class="fas fa-exclamation-triangle"></i> Fall Detected!</span>`; }
                
                deviceInfo.innerHTML = deviceInfoHTML || '<span>No recent data from device</span>'; } }

        // Fetch data every 5 seconds
        setInterval(fetchLiveData, 5000);

        // Initial fetch
        fetchLiveData();
    </script>
</body>
</html>