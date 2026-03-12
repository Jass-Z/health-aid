<?php
// get_live_data.php
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    echo json_encode(['success' => false, 'error' => 'No patient ID provided']);
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get latest vitals for selected patient
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
    $vitals_stmt->execute([$patient_id]);
    $patient_vitals = $vitals_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($patient_vitals) {
        echo json_encode([
            'success' => true,
            'data' => $patient_vitals
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => [
                'heart_rate' => null,
                'spo2' => null,
                'body_temperature' => null,
                'blood_pressure' => null,
                'seconds_ago' => null,
                'battery_level' => null,
                'fall_detected' => null,
                'alert_stage' => null
            ]
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>