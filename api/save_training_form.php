<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

// Logging setup
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
$logFile = $logDir . '/save_training_form.log';

try {
    // Accept form-data or JSON
    $data = $_POST;
    $raw = file_get_contents('php://input');
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] HEADERS: ".json_encode($headers)."\n", FILE_APPEND);
    if (!empty($raw)) {
        @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] RAW: ".$raw."\n", FILE_APPEND);
    }
    if (empty($data)) {
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $json;
        }
    }
    @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] DATA: ".json_encode($data)."\n", FILE_APPEND);

    // Basic required fields check (email + minimal identity)
    $email = isset($data['email']) ? trim($data['email']) : null;
    if (!$email) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email is required']);
        @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] ERROR: Missing email\n", FILE_APPEND);
        exit;
    }

    // Normalize training areas from form-data (areas[])
    $areas = [];
    if (isset($data['training_areas']) && is_array($data['training_areas'])) {
        $areas = $data['training_areas'];
    } elseif (isset($data['areas']) && is_array($data['areas'])) {
        $areas = $data['areas'];
    } elseif (isset($_POST['areas']) && is_array($_POST['areas'])) {
        $areas = $_POST['areas'];
    } elseif (isset($_POST['training_areas']) && is_array($_POST['training_areas'])) {
        $areas = $_POST['training_areas'];
    } elseif (isset($data['training_areas']) && is_string($data['training_areas'])) {
        // If sent as comma-separated string
        $areas = array_filter(array_map('trim', explode(',', $data['training_areas'])));
    }
    @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] AREAS: ".json_encode($areas)."\n", FILE_APPEND);

    $stmt = $pdo->prepare("INSERT INTO training_forms (
        client_id,
        full_name,
        date_of_birth,
        age,
        gender,
        contact_address,
        phone_number,
        email,
        next_of_kin,
        highest_qualification,
        institution_attended,
        year_of_graduation,
        relevant_certifications,
        training_start_date,
        expected_completion_date,
        training_areas,
        other_area,
        candidate_name,
        candidate_signature,
        candidate_date,
        trainer_name,
        trainer_position,
        remarks,
        trainer_signature,
        trainer_date
    ) VALUES (
        NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )");

    $params = [
        $data['fullName'] ?? null,
        $data['dob'] ?? null,
        $data['age'] ?? null,
        $data['gender'] ?? null,
        $data['address'] ?? null,
        $data['phone'] ?? null,
        $email,
        $data['nextOfKin'] ?? null,
        $data['qualification'] ?? null,
        $data['institution'] ?? null,
        $data['graduationYear'] ?? null,
        $data['certifications'] ?? null,
        $data['startDate'] ?? null,
        $data['completionDate'] ?? null,
        !empty($areas) ? json_encode($areas, JSON_UNESCAPED_UNICODE) : null,
        $data['otherArea'] ?? null,
        $data['candidateName'] ?? null,
        $data['candidateSignature'] ?? null,
        $data['candidateDate'] ?? null,
        $data['trainerName'] ?? null,
        $data['trainerPosition'] ?? null,
        $data['remarks'] ?? null,
        $data['trainerSignature'] ?? null,
        $data['trainerDate'] ?? null,
    ];
    @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] PARAMS: ".json_encode($params)."\n", FILE_APPEND);

    $stmt->execute($params);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] PDOException: code=".$e->getCode()." message=".$e->getMessage()."\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] Exception: ".$e->getMessage()."\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
