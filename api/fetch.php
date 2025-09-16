<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

try {
    // Validate and sanitize the ID parameter
    if (!isset($_GET['id']) || !preg_match('/^reg-(\d+)$/', $_GET['id'], $matches)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid registration ID format']);
        exit;
    }

    $registrantId = (int)$matches[1]; // Extract the numeric ID

    // Fetch registrant data from database
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$registrantId]);
    $registrant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registrant) {
        http_response_code(404);
        echo json_encode(['error' => 'Registrant not found']);
        exit;
    }

    // Format the response
    echo json_encode([
        'firstName' => $registrant['first_name'],
        'lastName' => $registrant['last_name'],
        'selectedDate' => $registrant['selected_date'],
        'reason' => $registrant['reason'],
        'email' => $registrant['email'],
        'mobile' => $registrant['mobile'],
        'jobTitle' => $registrant['job_title'],
        'company' => $registrant['company'],
        'city' => $registrant['city'],
        'country' => $registrant['country'],
        'submissionTime' => $registrant['created_at']
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>