<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

try {
    if (!isset($_GET['client_id'])) {
        throw new Exception('Client ID is required');
    }
    
    $clientId = $_GET['client_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM training_forms WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($form) {
        echo json_encode(['success' => true, 'form' => $form]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Training form not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
