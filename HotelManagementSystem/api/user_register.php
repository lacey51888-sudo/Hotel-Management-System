<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('register');

$body = json_decode(file_get_contents('php://input'), true);
$user_name = $body['user_name'] ?? '';
$password = $body['password'] ?? '';
$user_phone = $body['user_phone'] ?? '';
$user_email = $body['user_email'] ?? '';
$user_type = $body['user_type'] ?? 'customer';

// Validate required fields
if (!$user_phone || !$password) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'missing_required_fields']);
    exit;
}

// Validate username (use phone as username)
if (!$user_name) {
    $user_name = $user_phone;
}

// Validate password length
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'password_too_short']);
    exit;
}

// Check if phone number is already registered
$stmt = $conn->prepare("SELECT user_id FROM User WHERE user_phone = ?");
$stmt->bind_param('s', $user_phone);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['ok'=>false,'error'=>'phone_already_registered']);
    exit;
}

// Generate user ID
$user_id = 'U' . time() . rand(100, 999);

// Insert user (plaintext password)
$stmt = $conn->prepare("INSERT INTO User (user_id, user_name, user_password, user_phone, user_email, user_type) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssssss', $user_id, $user_name, $password, $user_phone, $user_email, $user_type);

if ($stmt->execute()) {
    $registerTime = perf_stop('register');
    
    echo json_encode([
        'ok'=>true,
        'user'=>[
            'user_id'=>$user_id,
            'user_name'=>$user_name,
            'user_type'=>$user_type
        ],
        'performance'=>['register_time'=>$registerTime,'formatted'=>number_format($registerTime * 1000, 2) . ' ms']
    ]);
} else {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'registration_failed']);
}
