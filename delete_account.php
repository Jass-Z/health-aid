<?php
require_once __DIR__ . '/includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $user_id = $_SESSION['user_id'];
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete user's health records
        $stmt = $pdo->prepare("DELETE FROM health_records WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Delete user's emergency contacts
        $stmt = $pdo->prepare("DELETE FROM emergency_contacts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Commit transaction
        $pdo->commit();
        
        // Destroy session and redirect
        session_destroy();
        header("Location: index.php?message=account_deleted");
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to delete account: " . $e->getMessage();
        header("Location: profile.php");
        exit();
    }
} else {
    header("Location: profile.php");
    exit();
}
?>