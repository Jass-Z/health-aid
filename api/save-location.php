<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$latitude = $input['latitude'] ?? null;
$longitude = $input['longitude'] ?? null;

if ($latitude && $longitude) {
    try {
        require_once '../includes/config.php'; // Include the config file
        $pdo = getDBConnection(); // Use the function from config
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("
            INSERT INTO health_records (user_id, location_lat, location_lng, source) 
            VALUES (?, ?, ?, 'device')
        ");
        $stmt->execute([$_SESSION['user_id'], $latitude, $longitude]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid coordinates']);
}
?>