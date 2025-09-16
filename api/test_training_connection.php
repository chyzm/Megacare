<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$response = [
    'success' => false,
    'db' => [
        'host' => isset($host) ? $host : null,
        'name' => isset($dbname) ? $dbname : null,
        'user' => isset($username) ? $username : null,
    ],
    'connection' => 'unknown',
    'training_forms' => [
        'exists' => null,
        'rowCount' => null,
        'columns' => null,
        'error' => null,
    ],
];

try {
    // Basic connection test
    $pdo->query('SELECT 1');
    $response['connection'] = 'ok';

    // Check table exists via information_schema, then try COUNT(*)
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'training_forms'");
    $checkStmt->execute([$dbname]);
    $exists = $checkStmt->fetchColumn() > 0;
    $response['training_forms']['exists'] = $exists;

    if ($exists) {
        // Row count
        $cnt = $pdo->query('SELECT COUNT(*) FROM training_forms')->fetchColumn();
        $response['training_forms']['rowCount'] = (int)$cnt;
        
        // Columns
        $cols = $pdo->query('DESCRIBE training_forms')->fetchAll(PDO::FETCH_ASSOC);
        $response['training_forms']['columns'] = array_map(function($c){ return $c['Field']; }, $cols);
    } else {
        $response['training_forms']['error'] = 'Table training_forms does not exist in this database.';
    }

    $response['success'] = true;
} catch (Throwable $e) {
    $response['success'] = false;
    $response['connection'] = 'error';
    $response['training_forms']['error'] = $e->getMessage();
}

echo json_encode($response);
