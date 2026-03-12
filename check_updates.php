<?php
// check_updates.php
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

$patient_id = $_GET['patient_id'] ?? null;
$last_update = intval($_GET['last_update'] ?? 0);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as new_count 
        FROM health_records 
        WHERE user_id = ? AND UNIX_TIMESTAMP(recorded_at) > ?
    ");
    $stmt->execute([$patient_id, $last_update]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'has_new_data' => $result['new_count'] > 0
    ]);
    
} catch (Exception $e) {
    echo json_encode(['has_new_data' => false]);
}
?>