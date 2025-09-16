<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    // --- Begin debug logging (temporary) ---
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
    $logFile = $logDir . '/api_register.log';
    $rawInput = file_get_contents('php://input');
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] HEADERS: ".json_encode($headers)."\n", FILE_APPEND);
    @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] RAW: ".$rawInput."\n", FILE_APPEND);
    // --- End debug logging ---

    $data = json_decode($rawInput, true);
    @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] DATA: ".json_encode($data)."\n", FILE_APPEND);
    
    // Validate input
    if (empty($data['email'])) {
        throw new Exception('Email is required');
    }

    // Check if email already exists
    $check = $pdo->prepare("SELECT id FROM clients WHERE email = ? LIMIT 1");
    $check->execute([$data['email']]);
    
    if ($check->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode([
            'success' => false,
            'error' => 'This email is already registered'
        ]);
        exit;
    }

    // Proceed with registration
    $id = 'reg-' . uniqid();
    date_default_timezone_set('Africa/Lagos');

    $created_at = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("INSERT INTO clients 
        (id, first_name, last_name, selected_date, reason, email, mobile, job_title, company, city, country,  created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->execute([
    $id,
    $data['firstName'],
    $data['lastName'],
    $data['selectedDate'], // Added selectedDate
    $data['reason'],       // Added reason
    $data['email'],
    $data['mobile'],
    $data['jobTitle'],
    $data['company'],
    $data['city'],
    $data['country'],
   
    $created_at
]);

    // Use the generated registration ID as the client reference
    $clientId = $id;

    // Link any pre-saved training/employment forms (saved earlier via email) to this client_id
    try {
        $linkTrain = $pdo->prepare("UPDATE training_forms SET client_id = ? WHERE (client_id IS NULL OR client_id = '') AND email = ?");
        $linkTrain->execute([$clientId, $data['email']]);
        @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] LINK training_forms affected: ".$linkTrain->rowCount()."\n", FILE_APPEND);
    } catch (Throwable $e2) {
        @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] LINK training_forms error: ".$e2->getMessage()."\n", FILE_APPEND);
    }
    try {
        $linkEmp = $pdo->prepare("UPDATE employment_forms SET client_id = ? WHERE (client_id IS NULL OR client_id = '') AND email = ?");
        $linkEmp->execute([$clientId, $data['email']]);
        @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] LINK employment_forms affected: ".$linkEmp->rowCount()."\n", FILE_APPEND);
    } catch (Throwable $e3) {
        @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] LINK employment_forms error: ".$e3->getMessage()."\n", FILE_APPEND);
    }

    // Handle training form data if present
    if (!empty($data['trainingData'])) {
        $training = $data['trainingData'];
        $trainingStmt = $pdo->prepare("INSERT INTO training_forms 
            (client_id, full_name, date_of_birth, age, gender, contact_address, phone_number, email, 
             next_of_kin, highest_qualification, institution_attended, year_of_graduation, 
             relevant_certifications, training_start_date, expected_completion_date, training_areas, 
             other_area, candidate_name, candidate_signature, candidate_date, trainer_name, 
             trainer_position, remarks, trainer_signature, trainer_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $tParams = [
            $clientId,
            $training['fullName'] ?? null,
            $training['dob'] ?? null,
            $training['age'] ?? null,
            $training['gender'] ?? null,
            $training['address'] ?? null,
            $training['phone'] ?? null,
            $training['email'] ?? null,
            $training['nextOfKin'] ?? null,
            $training['qualification'] ?? null,
            $training['institution'] ?? null,
            $training['graduationYear'] ?? null,
            $training['certifications'] ?? null,
            $training['startDate'] ?? null,
            $training['completionDate'] ?? null,
            !empty($training['training_areas']) ? json_encode($training['training_areas'], JSON_UNESCAPED_UNICODE) : null,
            $training['otherArea'] ?? null,
            $training['candidateName'] ?? null,
            $training['candidateSignature'] ?? null,
            $training['candidateDate'] ?? null,
            $training['trainerName'] ?? null,
            $training['trainerPosition'] ?? null,
            $training['remarks'] ?? null,
            $training['trainerSignature'] ?? null,
            $training['trainerDate'] ?? null
        ];
        @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] TRAINING PARAMS: ".json_encode($tParams)."\n", FILE_APPEND);
        $trainingStmt->execute($tParams);

    }

    // Handle employment form data if present
    if (!empty($data['employmentData'])) {
        $employment = $data['employmentData'];
        $employmentStmt = $pdo->prepare("INSERT INTO employment_forms 
            (client_id, full_name, date_of_birth, age, gender, contact_address, phone_number, email, 
             position_applied, experience_years, previous_employer, qualifications, skills, 
             availability_date, expected_salary, references) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $employmentStmt->execute([
            $clientId,
            $employment['emp_full_name'] ?? null,
            $employment['emp_dob'] ?? null,
            $employment['emp_age'] ?? null,
            $employment['emp_gender'] ?? null,
            $employment['emp_address'] ?? null,
            $employment['emp_phone'] ?? null,
            $employment['emp_email'] ?? null,
            $employment['position_applied'] ?? null,
            $employment['experience_years'] ?? null,
            $employment['previous_employer'] ?? null,
            $employment['qualifications'] ?? null,
            $employment['skills'] ?? null,
            $employment['availability_date'] ?? null,
            $employment['expected_salary'] ?? null,
            $employment['references'] ?? null
        ]);
    }


    echo json_encode([
        'success' => true,
        'id' => $id,
        'createdAt' => $created_at
    ]);

} catch (PDOException $e) {
    @file_put_contents($logFile ?? (__DIR__.'/../logs/api_register.log'), '['.date('Y-m-d H:i:s')."] PDOException: code=".$e->getCode()." message=".$e->getMessage()."\n", FILE_APPEND);
    // Handle database errors including unique constraint violations
    if ($e->getCode() == 23000) { // MySQL duplicate key error code
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'This email is already registered'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error'
        ]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>