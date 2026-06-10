<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('query');

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'missing_user_id']);
    exit;
}

$stmt = $conn->prepare("SELECT user_id, user_name, user_phone, user_email, user_type, created_at FROM User WHERE user_id = ?");
$stmt->bind_param('s', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'user_not_found']);
    exit;
}

$queryTime = perf_stop('query');

echo json_encode([
    'ok'=>true,
    'user'=>$user,
    'performance'=>['query_time'=>$queryTime,'formatted'=>number_format($queryTime * 1000, 2) . ' ms']
]);
