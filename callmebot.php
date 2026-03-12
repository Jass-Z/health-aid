<?php
// callmebot.php - WhatsApp emergency alert functions

/* Send WhatsApp message using CallMeBot API */
function sendWhatsAppAlert($phone, $message, $apikey = null) {
    // Removes any non-digit characters from phone number
    $phone = preg_replace('/\D/', '', $phone);
    
     // Ensure it's in international format (replace zeros with country code)
    if (strlen($phone) == 10) {
        $phone = '' . $phone;
    }

    // If no API key provided, use default
    if (!$apikey) {
        $apikey = ''; // Set your default API key here, get from callmebot website, to add multiple, use comma then loop in the send Whatsapp function
    }
    
    $url = "https://api.callmebot.com/whatsapp.php?phone={$phone}&text=" . urlencode($message) . "&apikey={$apikey}";
    
    return sendApiRequest($url);
}

/* Common function to send API requests */
function sendApiRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => ($httpCode === 200),
        'http_code' => $httpCode,
        'response' => $response
    ];
}

/* Format location information for alerts */
function formatLocationInfo($health_data, $is_test = false) {
    $location_info = "";
    
    if (!empty($health_data['location_lat']) && !empty($health_data['location_lng'])) {
        $lat = $health_data['location_lat'];
        $lng = $health_data['location_lng'];
        
        $location_info = "\nLOCATION INFORMATION:\n";
        $location_info .= "Latitude: {$lat}\n";
        $location_info .= "Longitude: {$lng}\n";
        $location_info .= "Google Maps: https://maps.google.com/?q={$lat},{$lng}\n";
        
    } else {
        $location_info = "\nLocation: Not available\n";
    }
    
    return $location_info;
}

/* Format vital signs information */
function formatVitalsInfo($health_data, $is_test = false) {
    $heart_rate = $health_data['heart_rate'] ?? 'N/A';
    $spo2 = $health_data['spo2'] ?? 'N/A';
    $temperature = $health_data['body_temperature'] ?? 'N/A';
    $blood_pressure = $health_data['blood_pressure'] ?? 'N/A';
    $fall_detected = $health_data['fall_detected'] ?? false;
    
    $vitals_info = "VITAL SIGNS:\n";
    $vitals_info .= "- Heart Rate: {$heart_rate} bpm\n";
    $vitals_info .= "- SpO2: {$spo2}%\n";
    $vitals_info .= "- Temperature: {$temperature}°C\n";
    $vitals_info .= "- Blood Pressure: {$blood_pressure}\n";
    
    if ($fall_detected && !$is_test) {
        $vitals_info .= "｡°⚠︎°｡ FALL DETECTED!!\n";
    }
    
    return $vitals_info;
}

/* Determine health status and send WhatsApp alerts */
function checkAndSendHealthAlerts($pdo, $user_id, $health_data) {
    // Get emergency contacts for the user
    $stmt = $pdo->prepare("SELECT * FROM emergency_contacts WHERE user_id = ? AND share_location = 1");
    $stmt->execute([$user_id]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($contacts)) {
        return false;
    }
    
    // Get patient info
    $patient_stmt = $pdo->prepare("SELECT full_name, height, weight, gender, birth_date FROM users WHERE id = ?");
    $patient_stmt->execute([$user_id]);
    $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);
    
    $patient_name = $patient['full_name'] ?? 'Patient';
    $alert_stage = $health_data['alert_stage'] ?? 1;
    
    // Check if we need to send an alert (moderate or critical)
    if ($alert_stage >= 2) {
        $location_info = formatLocationInfo($health_data);
        $vitals_info = formatVitalsInfo($health_data);
        
        // Prepare alert message based on severity
        if ($alert_stage == 2) {
            $severity = "｡°⚠︎°｡ MODERATE HEALTH ALERT";
            $message = "{$severity}\n\n";
            $message .= "Patient: {$patient_name}\n";
            $message .= "Time: " . date('Y-m-d H:i:s') . "\n\n";
            $message .= $vitals_info . "\n";
            $message .= "Please check on the patient immediately.{$location_info}";
            
        } else { // Critical (stage 3)
            $severity = "｡°⚠︎°｡ CRITICAL HEALTH ALERT";
            $message = "{$severity}\n\n";
            $message .= "Patient: {$patient_name}\n";
            $message .= "Time: " . date('Y-m-d H:i:s') . "\n\n";
            $message .= $vitals_info . "\n";
            $message .= "｡°⚠︎°｡ IMMEDIATE MEDICAL ATTENTION REQUIRED!{$location_info}";
        }
        
        // Send WhatsApp alerts to all emergency contacts
        $results = [];
        foreach ($contacts as $contact) {
            $phone = $contact['phone'];
            
            $whatsapp_result = sendWhatsAppAlert($phone, $message);
            $results[$contact['id']] = ['method' => 'whatsapp', 'success' => $whatsapp_result['success']];
            
            // Log the alert attempt
            logAlertAttempt($pdo, $user_id, $contact['id'], $alert_stage, $message, $results[$contact['id']]);
        }
        
        return $results;
    }
    
    return false;
}

/* Send test WhatsApp alert with sample data */
function sendTestWhatsAppAlert($pdo, $user_id, $contact_id, $patient_data = null) {
    // Get contact details
    $stmt = $pdo->prepare("SELECT * FROM emergency_contacts WHERE id = ? AND user_id = ?");
    $stmt->execute([$contact_id, $user_id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contact) {
        return ['success' => false, 'error' => 'Contact not found'];
    }
    
    // Get patient info
    $patient_stmt = $pdo->prepare("SELECT full_name, height, weight, gender, birth_date FROM users WHERE id = ?");
    $patient_stmt->execute([$user_id]);
    $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);
    
    $patient_name = $patient['full_name'] ?? 'Test Patient';
    
    // Get latest health data for realistic test, or use sample data
    $health_stmt = $pdo->prepare("
        SELECT * FROM health_records 
        WHERE user_id = ? 
        ORDER BY recorded_at DESC 
        LIMIT 1
    ");
    $health_stmt->execute([$user_id]);
    $health_data = $health_stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no real data exists, create realistic sample data
    if (!$health_data) {
        $health_data = [
            'heart_rate' => 85,
            'spo2' => 98,
            'body_temperature' => 36.8,
            'blood_pressure' => '120/80',
            'location_lat' => 26.18387341814887,
            'location_lng' => 50.5191798,
            'fall_detected' => false
        ];
    }
    
    // Format location and vitals for test alert
    $location_info = formatLocationInfo($health_data, true);
    $vitals_info = formatVitalsInfo($health_data, true);
    
    // Test message for WhatsApp
    $test_message = "⚠︎ TEST ALERT - Health Aid\n\n";
    $test_message .= "Patient: {$patient_name}\n";
    $test_message .= "Time: " . date('Y-m-d H:i:s') . "\n\n";
    $test_message .= $vitals_info . "\n";
    $test_message .= "!! This is a TEST alert - no real emergency exists.\n";
    $test_message .= $location_info . "\n";
    $test_message .= "Test completed successfully.";
    
    // Send WhatsApp test alert
    $result = sendWhatsAppAlert($contact['phone'], $test_message);
    
    // Log the test alert attempt
    if ($result['success']) {
        logAlertAttempt($pdo, $user_id, $contact_id, 0, $test_message, ['method' => 'whatsapp_test', 'success' => true]);
    }
    
    return $result;
}

/* Log alert attempts in database */
function logAlertAttempt($pdo, $user_id, $contact_id, $alert_stage, $message, $result) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO alert_logs 
            (user_id, contact_id, alert_stage, message, method, success, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $user_id, 
            $contact_id, 
            $alert_stage, 
            $message, 
            $result['method'], 
            $result['success'] ? 1 : 0
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to log alert: " . $e->getMessage());
        return false;
    }
}
?>