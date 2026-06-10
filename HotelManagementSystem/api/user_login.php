<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('query');

$body = json_decode(file_get_contents('php://input'), true);
$user_phone = $body['user_phone'] ?? '';
$password = $body['password'] ?? '';

if (!$user_phone || !$password) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'missing_credentials']);
    exit;
}

// Query user (by phone number)
$stmt = $conn->prepare("SELECT user_id, user_name, user_password, user_phone, user_email, user_type FROM User WHERE user_phone = ?");
$stmt->bind_param('s', $user_phone);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'invalid_credentials']);
    exit;
}

// Verify password (plaintext comparison)
if ($password !== $user['user_password']) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'invalid_credentials']);
    exit;
}

// Return user info (without password)
$role_id = $user['user_type'];
if ($role_id === 'staff') {
    $role_id = 'reception';
}

$queryTime = perf_stop('query');

echo json_encode([
    'ok'=>true,
    'user'=>[
        'user_id'=>$user['user_id'],
        'user_name'=>$user['user_name'],
        'user_phone'=>$user['user_phone'],
        'user_email'=>$user['user_email'],
        'user_type'=>$user['user_type'],
        'role_id'=>$role_id
    ],
    'performance'=>['query_time'=>$queryTime,'formatted'=>number_format($queryTime * 1000, 2) . ' ms']
]);
