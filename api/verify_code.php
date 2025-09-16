<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['code']) || !isset($input['purpose'])) {
        throw new Exception('Missing required parameters');
    }
    
    $code = $input['code'];
    $purpose = $input['purpose'];
    
    // Check if code exists and is active
    $stmt = $pdo->prepare("SELECT id FROM admin_codes WHERE code = ? AND purpose = ? AND is_active = 1");
    $stmt->execute([$code, $purpose]);
    $result = $stmt->fetch();
    
    if ($result) {
        // Mark code as used (inactive) for one-time usage
        $updateStmt = $pdo->prepare("UPDATE admin_codes SET is_active = 0, used_at = NOW() WHERE id = ?");
        $updateStmt->execute([$result['id']]);
        
        echo json_encode(['valid' => true]);
    } else {
        echo json_encode(['valid' => false]);
    }
    
} catch (Exception $e) {
    echo json_encode(['valid' => false, 'error' => $e->getMessage()]);
}
?>
