<?php
require_once __DIR__ . '/includes/config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = $user;
    
    // Redirect based on role
    if ($user['role'] === 'patient') {
        header("Location: patient_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
} else {
            $error = "Invalid email or password";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Health Aid</title>
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

        .login-container { 
            background: white; 
            color: #333; 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 0 20px rgba(0,0,0,0.3); 
            width: 100%; 
            max-width: 400px; 
        }
        
        .form-group { margin-bottom: 20px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; box-sizing: border-box; }
        
        .btn { background: #6A2C70; color: white; padding: 12px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; }
        
        .error { background: #E74C3C; color: white; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        
        .floating-shapes { position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: -1; }
        .shape { position: absolute; opacity: 0.1; border-radius: 50%; background: white; }
        .shape-1 { width: 200px; height: 200px; top: 10%; left: 5%; animation: float 8s ease-in-out infinite; }
        .shape-2 { width: 150px; height: 150px; bottom: 10%; right: 10%; animation: float 10s ease-in-out infinite reverse; }
        .shape-3 { width: 100px; height: 100px; top: 60%; left: 80%; animation: float 12s ease-in-out infinite; }
        .shape-4 { width: 100px; height: 100px; top: 30%; left: 5%; animation: float 12s ease-in-out infinite; }

        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-20px); } }
        .floating { animation: float 10s ease-in-out infinite; }
        
    </style>
</head>
<body>
    <div class="login-container">
        <h2 style="text-align: center; color: #6A2C70; margin-bottom: 30px;">Login to Health Aid</h2>
         <!-- Floating background shapes -->
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="Email">
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password">
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <br>

        <div>
            <p style="text-align:center;">Don't have an account?
                <a href="signup.php" style="color: #6A2C70; text-align: center;">Register here</a></p>
        </div>
        <p style="text-align: center; margin-top: 20px;">
            <a href="index.php" style="color: #6A2C70;">Back to Home</a>
        </p>
    </div>
</body>
</html>