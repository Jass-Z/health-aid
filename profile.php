<?php

require_once __DIR__ . '/includes/config.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_user = $_SESSION['user']; 
$patient_id = $_GET['patient_id'] ?? null;
$success = '';
$error = '';

// Initialize variables
$patient = null;
$profile_user = null;
$patients = [];

// Redirect patients so they can't access other patient profiles
if ($patient_id && $current_user['role'] === 'patient' && $patient_id != $user_id) {
    header("Location: profile.php");
    exit();
}

// For patients, ensure they can only view their own profile
if ($current_user['role'] === 'patient') {
    $patient_id = null; // Patients can't view other patient profiles
    $profile_user = $current_user; // Always show their own profile
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get patient info if patient_id is provided (viewing patient profile)
    if ($patient_id && $current_user['role'] === 'doctor') {
        $patient_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'patient'");
        $patient_stmt->execute([$patient_id]);
        $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);
        $profile_user = $patient; // Set profile user to patient
    } else if (!$patient_id) {
        // Viewing own profile
        $profile_user = $current_user;
    }

    // Get all patients for sidebar (only for doctors)
    if ($current_user['role'] === 'doctor') {
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
    }

    // Handle profile updates (only for own profile not the patients duh)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$patient_id) {
        if (isset($_POST['update_profile'])) {
            $full_name = $_POST['full_name'];
            $gender = $_POST['gender'];
            $birth_date = $_POST['birth_date'];

            $stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, gender = ?, birth_date = ?
                WHERE id = ?
            ");
            $stmt->execute([$full_name, $gender, $birth_date, $user_id]);
            
            // Update session
            $_SESSION['user']['full_name'] = $full_name;
            $_SESSION['user']['gender'] = $gender;
            $_SESSION['user']['birth_date'] = $birth_date;
            
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $profile_user = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user'] = $profile_user; // Update session with fresh data
        }

        // Handle password change
        if (isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (password_verify($current_password, $user['password'])) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                    $success = "Password changed successfully!";
                } else {
                    $error = "New passwords do not match";
                }
            } else {
                $error = "Current password is incorrect";
            }
        }

        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['profile_picture']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $upload_dir = 'uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filepath)) {
                    $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    $stmt->execute([$filename, $user_id]);
                    $_SESSION['user']['profile_picture'] = $filename;
                    $success = "Profile picture updated successfully!";
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $profile_user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Failed to upload profile picture";
                }
            } else {
                $error = "Only JPG, PNG, and GIF files are allowed";
            }
        }
    }

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Calculate BMI (for patients)
$bmi = null;
if ($profile_user && $profile_user['height'] && $profile_user['weight']) {
    $height_in_meters = $profile_user['height'] / 100;
    $bmi = round($profile_user['weight'] / ($height_in_meters * $height_in_meters), 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $patient_id ? 'Patient Profile' : 'Profile Settings'; ?> - Health Aid</title>
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .navbar {
            background-color: var(--bg-card);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border-color);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
            transition: color 0.3s;
            padding: 8px 16px;
            border-radius: 8px;
        }

        .nav-links a:hover {
            color: var(--primary-color);
            background: var(--bg-secondary);
        }

        .nav-links a.active {
            color: var(--primary-color);
            background: var(--bg-secondary);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-pic {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
            cursor: pointer;
            transition: transform 0.3s;
        }

        .profile-pic:hover {
            transform: scale(1.05);
        }

        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .theme-toggle:hover {
            background: var(--bg-secondary);
        }

        .profile-container {
            display: grid;
            grid-template-columns: <?php echo $current_user['role'] === 'doctor' ? '350px 1fr' : '1fr'; ?>;
            gap: 30px;
            padding: 30px 0;
            min-height: calc(100vh - 80px);
        }

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

        .patient-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            border-color: var(--primary-light);
        }

        .patient-card.active {
            border-color: var(--primary-color);
        }

        .patient-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .patient-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .profile-content {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .content-header {
            margin-bottom: 10px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .profile-main {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }

        .profile-sidebar, .profile-details {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            align-items: center;
            height: fit-content;
            top: 100px;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            margin: 0 auto 20px;
            display: block;
        }

        .profile-name {
            font-size: 24px;
            text-align: center;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .profile-email {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .profile-role {
            text-align: center;
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: block;
            margin: 0 auto 20px;
            width: auto;
            min-width: 120px;
        }

        .bmi-display {
            text-align: center;
            padding: 20px;
            background: var(--bg-secondary);
            border-radius: 10px;
            margin-top: 20px;
        }

        .bmi-value {
            font-size: 36px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn {
            background: var(--primary-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-back {
            background: var(--primary-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: 14px;
            float: right;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-full {
            width: 100%;
        }

        .success {
            background: #27AE60;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #E74C3C;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 20px;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .info-item {
            background: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .info-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .medical-info {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .medical-info h4 {
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .medical-list {
            list-style: none;
        }

        .medical-list li {
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
        }

        .medical-list li:last-child {
            border-bottom: none;
        }

        @media (max-width: 1024px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .patients-sidebar {
                max-height: 300px;
            }
            
            .profile-main {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <div class="profile-container">
            <?php if ($current_user['role'] === 'doctor'): ?>
            <!-- Patients Sidebar (Only for Doctors) -->
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
                             onclick="window.location.href='profile.php?patient_id=<?php echo $p['id']; ?>'">
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

            <!-- Profile Content -->
            <div class="profile-content">
                <?php if ($profile_user): ?>
                    <div class="content-header">
                         
                        <h1 class="page-title">
                            <?php if ($patient_id): ?>
                                Patient Profile: <?php echo htmlspecialchars($profile_user['full_name']); ?>   
                                <!-- Button so u can go back to ur own profile after choosing a patient -->
                                <a href="profile.php" class="btn-back">
                                <i class="fas fa-arrow-left"></i> Back to Profile
                                </a>
                            <?php else: ?>
                                My Profile
                            <?php endif; ?>
                        </h1>
                    </div>

                    <?php if ($success): ?>
                        <div class="success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="error"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="profile-main">
                        <!-- Profile Sidebar -->
                        <div class="profile-sidebar">
                            <img src="<?php echo !empty($profile_user['profile_picture']) ? 'uploads/' . $profile_user['profile_picture'] : './uploads/profile_placeholder_img.png'; ?>" 
                                 alt="Profile" class="profile-avatar">
                            <h2 class="profile-name"><?php echo htmlspecialchars($profile_user['full_name']); ?></h2>
                            <p class="profile-email"><?php echo htmlspecialchars($profile_user['email']); ?></p>
                            
                            <?php if ($patient_id || $profile_user['role'] === 'patient'): ?>
                            <div class="profile-role"> <span style="text-align: center;">
                                PATIENT
                            </div>
                            <?php else: ?>
                            <div class="profile-role">
                                DOCTOR
                            </span> </div>
                            <?php endif; ?>
                            
                            <?php if (($patient_id || $profile_user['role'] === 'patient') && $bmi): ?>
                            <div class="bmi-display">
                                <h3>BMI</h3>
                                <div class="bmi-value"><?php echo $bmi; ?></div>
                                <p>
                                    <?php if ($bmi < 18.5): ?>
                                        Underweight
                                    <?php elseif ($bmi < 25): ?>
                                        Normal weight
                                    <?php elseif ($bmi < 30): ?>
                                        Overweight
                                    <?php else: ?>
                                        Obesity
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php endif; ?>

                            <?php if ($patient_id): ?>
                            <div class="medical-info">
                                <h4>Medical Summary</h4>
                                <ul class="medical-list">
                                    <li>
                                        <span>Last Reading:</span>
                                        <span>
                                            <?php 
                                            $last_reading = null;
                                            foreach ($patients as $p) {
                                                if ($p['id'] == $patient_id) {
                                                    $last_reading = $p['last_reading'];
                                                    break;
                                                }
                                            }
                                            echo $last_reading ? date('M j, g:i A', strtotime($last_reading)) : 'No data';
                                            ?>
                                        </span>
                                    </li>
                                    <li>
                                        <span>Total Readings:</span>
                                        <span>
                                            <?php 
                                            $reading_count = 0;
                                            foreach ($patients as $p) {
                                                if ($p['id'] == $patient_id) {
                                                    $reading_count = $p['reading_count'];
                                                    break;
                                                }
                                            }
                                            echo $reading_count;
                                            ?>
                                        </span>
                                    </li>
                                    <li>
                                        <span>Status:</span>
                                        <span style="color: #27AE60;">Active</span>
                                    </li>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Profile Details -->
                        <div class="profile-details">
                            <?php if ($patient_id): ?>
                                <!-- Patient Profile View (Read-only) -->
               
                                <h2 class="section-title">Patient Information</h2>
                                
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="info-label">Full Name</div>
                                        <div class="info-value"><?php echo htmlspecialchars($profile_user['full_name']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Email</div>
                                        <div class="info-value"><?php echo htmlspecialchars($profile_user['email']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Gender</div>
                                        <div class="info-value"><?php echo ucfirst($profile_user['gender'] ?? 'Not specified'); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Birth Date</div>
                                        <div class="info-value"><?php echo $profile_user['birth_date'] ? date('M j, Y', strtotime($profile_user['birth_date'])) : 'Not specified'; ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Weight (kg)</div>
                                        <div class="info-value"><?php echo ucfirst($profile_user['weight'] ?? 'Not specified'); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Height (cm)</div>
                                        <div class="info-value"><?php echo ucfirst($profile_user['height'] ?? 'Not specified'); ?></div>
                                    </div>
                                    <?php if ($profile_user['role'] === 'patient'): ?>
<?php endif; ?>

                                <div style="display: flex; gap: 15px; margin-top: 25px;">
                                    <a href="dashboard.php?patient_id=<?php echo $patient_id; ?>" class="btn">
                                        <i class="fas fa-tachometer-alt"></i> View Dashboard
                                    </a>
                                    <a href="statistics.php?patient_id=<?php echo $patient_id; ?>" class="btn">
                                        <i class="fas fa-chart-bar"></i> View Statistics
                                    </a>
                                </div>

                            <?php else: ?>
                                <!-- Doctor/Patient Profile Edit Form -->
                              <form method="POST" enctype="multipart/form-data">
    <h2 class="section-title">Personal Information</h2>
    
    <div class="form-group">
        <label class="form-label">Profile Picture</label>
        <input type="file" name="profile_picture" class="form-control" accept="image/*">
        <small style="color: var(--text-secondary);">Max file size: 2MB. Allowed types: JPG, PNG, GIF</small>
    </div>

    <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($profile_user['full_name']); ?>" required>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-control">
                <option value="">Select Gender</option>
                <option value="male" <?php echo $profile_user['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                <option value="female" <?php echo $profile_user['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                <option value="prefer_not_to_say" <?php echo $profile_user['gender'] === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Birth Date</label>
            <input type="date" name="birth_date" class="form-control" value="<?php echo htmlspecialchars($profile_user['birth_date']); ?>">
        </div>
    </div>

    <!-- Only show height/weight for patients -->
    
    <button type="submit" name="update_profile" class="btn btn-full">
        <i class="fas fa-save"></i> Update Profile
    </button>
</form>
                                <!-- Change Password -->
                                <form method="POST" style="margin-top: 40px;">
                                    <h2 class="section-title">Change Password</h2>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">New Password</label>
                                            <input type="password" name="new_password" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" name="confirm_password" class="form-control" required>
                                        </div>
                                    </div>

                                    <button type="submit" name="change_password" class="btn btn-full">
                                        <i class="fas fa-key"></i> Change Password
                                    </button>
                                </form>

                                <!-- Account Deletion -->
                                <div style="margin-top: 40px; border-top: 2px solid var(--border-color); padding-top: 30px;">
                                    
                                    <div style="background: rgba(231, 76, 60, 0.1); padding: 20px; border-radius: 8px; border-left: 4px solid #E74C3C;">
                                        <h4 style="color: #E74C3C; margin-bottom: 10px;">
                                            <i class="fas fa-exclamation-triangle"></i> Delete Account
                                        </h4>
                                        <p style="color: var(--text-secondary); margin-bottom: 15px;">
                                            Once you delete your account, there is no going back. This will permanently delete your account and all associated data.
                                        </p>
                                        <button type="button" onclick="confirmAccountDeletion()" class="btn" style="background: #E74C3C;">
                                            <i class="fas fa-trash"></i> Delete My Account
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                        <i class="fas fa-user" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;"></i>
                        <h2>Profile Not Found</h2>
                        <p>Unable to load profile information.</p>
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

        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="new_password"]');
            const confirmPassword = document.querySelector('input[name="confirm_password"]');
            
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });

        function confirmAccountDeletion() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently lost.')) {
                if (confirm('This is your final warning. Click OK to permanently delete your account.')) {
                    // Create a form to submit the deletion request
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'delete_account.php';
                    
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = 'confirm_delete';
                    csrf.value = '1';
                    form.appendChild(csrf);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        }
    </script>
</body>
</html>