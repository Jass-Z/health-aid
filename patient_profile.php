<?php
// profile.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/patientstyle.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_user = $_SESSION['user'];

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $full_name = $_POST['full_name'] ?? $current_user['full_name'];
        $email = $_POST['email'] ?? $current_user['email'];
        $phone = $_POST['phone'] ?? $current_user['phone'];
        $birth_date = $_POST['birth_date'] ?? $current_user['birth_date'];
        $gender = $_POST['gender'] ?? $current_user['gender'];
        $height = $_POST['height'] ?? $current_user['height'];
        $weight = $_POST['weight'] ?? $current_user['weight'];
        $medical_conditions = $_POST['medical_conditions'] ?? $current_user['medical_conditions'];
        $allergies = $_POST['allergies'] ?? $current_user['allergies'];
        $medications = $_POST['medications'] ?? $current_user['medications'];
        $device_id = $_POST['device_id'] ?? $current_user['device_id'];
        
        // Handle profile picture upload
        $profile_picture = $current_user['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $destination = $upload_dir . $filename;
            
            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array(strtolower($file_extension), $allowed_types)) {
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
                    // Delete old profile picture if it exists and isn't the default
                    if ($profile_picture && $profile_picture !== 'profile_placeholder_img.png') {
                        $old_file = $upload_dir . $profile_picture;
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                    $profile_picture = $filename;
                }
            }
        }
        
        // Update user in database
        $update_stmt = $pdo->prepare("
            UPDATE users SET 
            full_name = ?, email = ?, phone = ?, birth_date = ?, gender = ?, 
            height = ?, weight = ?, medical_conditions = ?, allergies = ?, 
            medications = ?, profile_picture = ?, device_id = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $update_stmt->execute([
            $full_name, $email, $phone, $birth_date, $gender,
            $height, $weight, $medical_conditions, $allergies,
            $medications, $profile_picture, $device_id, $user_id
        ]);
        
        // Update session
        $_SESSION['user'] = array_merge($current_user, [
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'birth_date' => $birth_date,
            'gender' => $gender,
            'height' => $height,
            'weight' => $weight,
            'medical_conditions' => $medical_conditions,
            'allergies' => $allergies,
            'medications' => $medications,
            'profile_picture' => $profile_picture,
            'device_id' => $device_id
        ]);
        
        $current_user = $_SESSION['user'];
        $success = 'Profile updated successfully!';
        
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Calculate BMI
$bmi = null;
if ($current_user['height'] && $current_user['weight']) {
    $height_in_meters = $current_user['height'] / 100;
    $bmi = round($current_user['weight'] / ($height_in_meters * $height_in_meters), 1);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Health Aid</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <div class="profile-container">
            <div class="content-header">
                <h1 class="page-title">My Profile</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="profile-content">
                <!-- Profile Sidebar -->
                <div class="profile-sidebar">
                    <img src="<?php echo !empty($current_user['profile_picture']) ? 'uploads/' . $current_user['profile_picture'] : './uploads/profile_placeholder_img.png'; ?>" 
                         alt="Profile" class="profile-avatar">
                    <h2 class="profile-name"><?php echo htmlspecialchars($current_user['full_name']); ?></h2>
                    <div class="profile-role">
                        <?php echo ucfirst($current_user['role']); ?>
                    </div>

                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $current_user['height'] ? $current_user['height'] . ' cm' : '--'; ?></div>
                            <div class="stat-label">Height</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $current_user['weight'] ? $current_user['weight'] . ' kg' : '--'; ?></div>
                            <div class="stat-label">Weight</div>
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
                    <?php endif; ?>

                    <?php if ($current_user['device_id']): ?>
                    <div class="device-info" style="margin-top: 20px;">
                        <p><strong>Connected Device:</strong></p>
                        <p><?php echo htmlspecialchars($current_user['device_id']); ?></p>
                        <small>This device ID is used to link your health monitor</small>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Profile Form -->
                <div class="profile-form-container">
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Personal Information -->
                        <div class="form-section">
                            <h3><i class="fas fa-user"></i> Personal Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="full_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($current_user['full_name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="birth_date" class="form-control" 
                                           value="<?php echo htmlspecialchars($current_user['birth_date'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo ($current_user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo ($current_user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo ($current_user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Device ID</label>
                                    <input type="text" name="device_id" class="form-control" 
                                           value="<?php echo htmlspecialchars($current_user['device_id'] ?? ''); ?>"
                                           placeholder="e.g., DEV001">
                                    <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                                        Enter the device ID from your health monitor to enable live data tracking
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Health Information -->
                        <div class="form-section">
                            <h3><i class="fas fa-heartbeat"></i> Health Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Height (cm)</label>
                                    <input type="number" name="height" class="form-control" 
                                           value="<?php echo htmlspecialchars($current_user['height'] ?? ''); ?>"
                                           min="100" max="250" step="0.1">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Weight (kg)</label>
                                    <input type="number" name="weight" class="form-control" 
                                           value="<?php echo htmlspecialchars($current_user['weight'] ?? ''); ?>"
                                           min="30" max="300" step="0.1">
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label">Medical Conditions</label>
                                    <textarea name="medical_conditions" class="form-control form-textarea"
                                              placeholder="List any medical conditions (e.g., hypertension, diabetes, asthma...)"><?php echo htmlspecialchars($current_user['medical_conditions'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label">Allergies</label>
                                    <textarea name="allergies" class="form-control form-textarea"
                                              placeholder="List any allergies (e.g., penicillin, nuts, pollen...)"><?php echo htmlspecialchars($current_user['allergies'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label">Current Medications</label>
                                    <textarea name="medications" class="form-control form-textarea"
                                              placeholder="List current medications and dosages"><?php echo htmlspecialchars($current_user['medications'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Picture -->
                        <div class="form-section">
                            <h3><i class="fas fa-camera"></i> Profile Picture</h3>
                            <div class="form-group">
                                <div class="file-upload">
                                    <input type="file" name="profile_picture" id="profile_picture" 
                                           class="file-upload-input" accept="image/*">
                                    <label for="profile_picture" class="file-upload-label">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        Click to upload profile picture
                                        <br>
                                        <small>Supported formats: JPG, PNG, GIF (Max 5MB)</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="reset" class="btn btn-outline">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
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

        // File upload preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Update the profile avatar preview
                    document.querySelector('.profile-avatar').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Calculate and display BMI when height or weight changes
        function calculateBMI() {
            const height = parseFloat(document.querySelector('input[name="height"]').value) / 100;
            const weight = parseFloat(document.querySelector('input[name="weight"]').value);
            
            if (height && weight) {
                const bmi = (weight / (height * height)).toFixed(1);
                // You could update a BMI display here if needed
                console.log('BMI:', bmi);
            }
        }

        document.querySelector('input[name="height"]').addEventListener('input', calculateBMI);
        document.querySelector('input[name="weight"]').addEventListener('input', calculateBMI);
    </script>
</body>
</html>