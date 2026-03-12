<!DOCTYPE html>
<html>
<head>
    <title>Sign Up - Health Aid</title>
    <!-- Add Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { 
            font-family: Arial; 
            background: linear-gradient(135deg, #6A2C70, #4D1E53); 
            color: white; 
            min-height: 100vh; 
            margin: 0; 
            padding: 20px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }

        .register-container { 
            background: white; 
            color: #333; 
            padding: 40px;
            border-radius: 10px; 
            box-shadow: 0 0 20px rgba(0,0,0,0.3); 
            width: 100%;
            max-width: 500px; }

        .form-group { margin-bottom: 20px; text-align: left; }
        .form-label { display: block; margin-bottom: 5px; font-weight: bold; color: #6A2C70; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; box-sizing: border-box; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        .btn { 
            background: #6A2C70; 
            color: white;
            padding: 12px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            width: 100%; 
            font-size: 16px;
             margin-top: 10px; 
            }

        .error { background: #E74C3C; color: white; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #27AE60; color: white; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        
        .login-link { text-align: center; margin-top: 20px; }

        .floating-shapes { position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: -1; }
        .shape { position: absolute; opacity: 0.1; border-radius: 50%; background: white; }
        .shape-1 { width: 200px; height: 200px; top: 10%; left: 5%; animation: float 8s ease-in-out infinite; }
        .shape-2 { width: 150px; height: 150px; bottom: 10%; right: 10%; animation: float 10s ease-in-out infinite reverse; }
        .shape-3 { width: 100px; height: 100px; top: 60%; left: 80%; animation: float 12s ease-in-out infinite; }
        .shape-4 { width: 100px; height: 100px; top: 30%; left: 5%; animation: float 12s ease-in-out infinite; }

        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-20px); } }
        .floating { animation: float 10s ease-in-out infinite; }
        
        .role-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .role-option {
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .role-option.selected {
            border-color: #6A2C70;
            background: rgba(106, 44, 112, 0.1);
        }
        
        .role-option input {
            display: none;
        }
        
        .role-icon {
            font-size: 24px;
            margin-bottom: 8px;
            color: #6A2C70;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2 style="text-align: center; color: #6A2C70; margin-bottom: 30px;">Create Health Aid Account</h2>
        
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
        </div>

        <?php
        // Database connection
        $servername = "localhost";
        $username = "J";
        $password = "J@ss789!12";
        $dbname = "healthmonitor"; 
        
        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Initialize variables
        $errors = [];
        $success = "";
        
        // Check if form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Get form data
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $role = $_POST['role'] ?? 'patient';
            $gender = $_POST['gender'] ?? '';
            $birth_date = $_POST['birth_date'] ?? '';
            $weight = $_POST['weight'] ?? null;
            $height = $_POST['height'] ?? null;
            
            // Validate inputs
            if (empty($full_name)) {
                $errors[] = "Full name is required.";
            }
            
            if (empty($email)) {
                $errors[] = "Email is required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format.";
            } else {
                // Check if email already exists
                $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $check_email->bind_param("s", $email);
                $check_email->execute();
                $check_email->store_result();
                
                if ($check_email->num_rows > 0) {
                    $errors[] = "Email already registered. Please use a different email.";
                }
                $check_email->close();
            }
            
            if (empty($password)) {
                $errors[] = "Password is required.";
            } elseif (strlen($password) < 6) {
                $errors[] = "Password must be at least 6 characters.";
            }
            
            if ($password !== $confirm_password) {
                $errors[] = "Passwords do not match.";
            }
            
            if (!in_array($role, ['patient', 'doctor'])) {
                $errors[] = "Invalid role selected.";
            }
            
            // If no errors, insert into database
            if (empty($errors)) {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Convert empty strings to NULL for database
                $gender = empty($gender) ? NULL : $gender;
                $birth_date = empty($birth_date) ? NULL : $birth_date;
                $weight = empty($weight) ? NULL : $weight;
                $height = empty($height) ? NULL : $height;
                
                // Prepare SQL statement
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, gender, birth_date, weight, height, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
                if ($stmt) {
                    $stmt->bind_param("ssssssdd", $full_name, $email, $hashed_password, $role, $gender, $birth_date, $weight, $height);
                    
                    if ($stmt->execute()) {
                        $success = "Registration successful! Your account has been created.";
                        // Clear form data after successful registration
                        $_POST = [];
                    } else {
                        $errors[] = "Error creating account. Please try again.";
                    }
                    
                    $stmt->close();
                } else {
                    $errors[] = "Database error. Please try again.";
                }
            }
        }
        
        $conn->close();
        ?>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (isset($success) && !empty($success)): ?>
            <div class="success">
                <?php echo htmlspecialchars($success); ?>
                <p style="margin-top: 10px;">
                    <a href="login.php" style="color: white; text-decoration: underline; text-align: center;">Click here to login</a>
                </p>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Account Type *</label>
                <div class="role-selection">
                    <label class="role-option <?php echo (isset($success) && empty($_POST) ? 'patient' : ($_POST['role'] ?? 'patient')) === 'patient' ? 'selected' : ''; ?>">
                        <input type="radio" name="role" value="patient" <?php echo (isset($success) && empty($_POST) ? 'patient' : ($_POST['role'] ?? 'patient')) === 'patient' ? 'checked' : ''; ?>>
                        <div class="role-icon"><i class="fas fa-user"></i></div>
                        <div>Patient</div>
                        <small>Monitor your own health</small>
                    </label>
                    <label class="role-option <?php echo (isset($success) && empty($_POST) ? 'patient' : ($_POST['role'] ?? 'patient')) === 'doctor' ? 'selected' : ''; ?>">
                        <input type="radio" name="role" value="doctor" <?php echo (isset($success) && empty($_POST) ? 'patient' : ($_POST['role'] ?? 'patient')) === 'doctor' ? 'checked' : ''; ?>>
                        <div class="role-icon"><i class="fas fa-user-md"></i></div>
                        <div>Doctor</div>
                        <small>Monitor multiple patients</small>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-control" required value="<?php echo isset($success) ? '' : htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" required value="<?php echo isset($success) ? '' : htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-control">
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo (isset($success) ? '' : ($_POST['gender'] ?? '')) === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo (isset($success) ? '' : ($_POST['gender'] ?? '')) === 'female' ? 'selected' : ''; ?>>Female</option>
                        <option value="prefer_not_to_say" <?php echo (isset($success) ? '' : ($_POST['gender'] ?? '')) === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Birth Date</label>
                    <input type="date" name="birth_date" class="form-control" value="<?php echo isset($success) ? '' : htmlspecialchars($_POST['birth_date'] ?? ''); ?>">
                </div>
            </div>

            <!-- Only show height/weight for patients -->
            <?php 
            $current_role = isset($success) ? 'patient' : ($_POST['role'] ?? 'patient');
            if ($current_role === 'patient'): 
            ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Weight (kg)</label>
                    <input type="number" name="weight" step="0.1" class="form-control" value="<?php echo isset($success) ? '' : htmlspecialchars($_POST['weight'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Height (cm)</label>
                    <input type="number" name="height" step="0.1" class="form-control" value="<?php echo isset($success) ? '' : htmlspecialchars($_POST['height'] ?? ''); ?>">
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn">Create Account</button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="login.php" style="color: #6A2C70;">Login here</a></p>
        </div>
        <p style="text-align: center; margin-top: 20px;">
            <a href="index.php" style="color: #6A2C70;">Back to Home</a>
        </p>
    </div>

    <script>
        // Role selection styling
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.role-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                this.querySelector('input').checked = true;
                
                // Show/hide height/weight fields based on role selection
                const heightWeightSection = document.querySelector('.form-row:last-of-type');
                if (heightWeightSection) {
                    if (this.querySelector('input').value === 'patient') {
                        heightWeightSection.style.display = 'grid';
                    } else {
                        heightWeightSection.style.display = 'none';
                    }
                }
            });
        });

        // Initial hide/show based on current selection
        document.addEventListener('DOMContentLoaded', function() {
            const selectedRole = document.querySelector('input[name="role"]:checked');
            const heightWeightSection = document.querySelector('.form-row:last-of-type');
            if (heightWeightSection && selectedRole) {
                if (selectedRole.value === 'patient') {
                    heightWeightSection.style.display = 'grid';
                } else {
                    heightWeightSection.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>