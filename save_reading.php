<?php
// save_reading.php - API endpoint for ESP32 data
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

// Enable CORS for ESP32
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For POST requests with JSON data
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    // For GET requests (ESP32 sends GET)
    $input = $_GET;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Extract data from ESP32 - match Arduino parameter names
    $device_id = $input['device_id'] ?? null;
    $tempC = floatval($input['body_temperature'] ?? 0);
    $spo2v = intval($input['spo2'] ?? 0);
    $bpm = intval($input['heart_rate'] ?? 0);
    $fall = intval($input['fall_detected'] ?? 0);
    $battPct = intval($input['battery_level'] ?? 0);
    $currentStage = intval($input['alert_stage'] ?? 1);
    $lat = floatval($input['location_lat'] ?? 0);
    $lng = floatval($input['location_lng'] ?? 0);
    
    if (!$device_id) {
        throw new Exception("Device ID is required");
    }
    
    // Find user by device ID
    $user_stmt = $pdo->prepare("SELECT id FROM users WHERE device_id = ?");
    $user_stmt->execute([$device_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("User not found for device ID: " . $device_id);
    }
    
    $user_id = $user['id'];
    
    // Insert health record with correct column names
    $insert_stmt = $pdo->prepare("
        INSERT INTO health_records 
        (user_id, heart_rate, spo2, body_temperature, fall_detected, battery_level, alert_stage, location_lat, location_lng, recorded_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $insert_stmt->execute([
        $user_id,
        $heart_rate,
        $spo2,
        $body_temperature,
        $fall_detected,
        $battery_level,
        $alert_stage,
        $location_lat ?: null,
        $location_lng ?: null
    ]);
    
    // Handle emergency alerts based on stage
    if ($alert_stage == 3) { // CRITICAL stage
        handleCriticalAlert($pdo, $user_id, $heart_rate, $spo2, $body_temperature, $fall_detected, $location_lat, $location_lng);
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Data saved successfully',
        'record_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

function handleCriticalAlert($pdo, $user_id, $heart_rate, $spo2, $temp, $fall, $lat, $lng) {
    // Get emergency contacts
    $contacts_stmt = $pdo->prepare("
        SELECT * FROM emergency_contacts 
        WHERE user_id = ? AND share_location = 1
    ");
    $contacts_stmt->execute([$user_id]);
    $contacts = $contacts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get patient info
    $user_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $patient = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create alert message
    $message = "CRITICAL HEALTH ALERT\n";
    $message .= "Patient: " . $patient['full_name'] . "\n";
    $message .= "Heart Rate: " . $heart_rate . " bpm\n";
    $message .= "SpO2: " . $spo2 . "%\n";
    $message .= "Temperature: " . $temp . "°C\n";
    
    if ($fall) {
        $message .= "FALL DETECTED!\n";
    }
    
    if ($lat && $lng) {
        $message .= "Location: https://maps.google.com/?q=" . $lat . "," . $lng . "\n";
    }
    
    $message .= "\nImmediate attention required!";
    
    // In a real implementation, you would send SMS/email alerts here
    // For now, we'll log the alert
    error_log("CRITICAL ALERT: " . $message);
    
    // You can integrate with SMS APIs like Twilio here
    foreach ($contacts as $contact) {
        // sendSMS($contact['phone'], $message);
        error_log("Would send SMS to: " . $contact['phone']);
    }
}
?>