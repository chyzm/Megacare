<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

// Logging setup
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
$logFile = $logDir . '/save_employment_form.log';

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

    $email = isset($data['emp_email']) ? trim($data['emp_email']) : (isset($data['email']) ? trim($data['email']) : null);
    if (!$email) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email is required']);
        @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] ERROR: Missing email\n", FILE_APPEND);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO employment_forms (
        client_id,
        full_name,
        date_of_birth,
        age,
        gender,
        contact_address,
        phone_number,
        email,
        position_applied,
        experience_years,
        previous_employer,
        qualifications,
        skills,
        availability_date,
        expected_salary,
        references
    ) VALUES (
        NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )");

    $params = [
        $data['emp_full_name'] ?? ($data['full_name'] ?? null),
        $data['emp_dob'] ?? ($data['dob'] ?? null),
        $data['emp_age'] ?? ($data['age'] ?? null),
        $data['emp_gender'] ?? ($data['gender'] ?? null),
        $data['emp_address'] ?? ($data['address'] ?? null),
        $data['emp_phone'] ?? ($data['phone'] ?? null),
        $email,
        $data['position_applied'] ?? null,
        $data['experience_years'] ?? null,
        $data['previous_employer'] ?? null,
        $data['qualifications'] ?? null,
        $data['skills'] ?? null,
        $data['availability_date'] ?? null,
        $data['expected_salary'] ?? null,
        $data['references'] ?? null,
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
