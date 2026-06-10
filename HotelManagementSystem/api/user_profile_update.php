<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('update');

$body = json_decode(file_get_contents('php://input'), true);
$user_id = $body['user_id'] ?? '';
$user_phone = $body['user_phone'] ?? '';
$user_email = $body['user_email'] ?? '';
$new_password = $body['new_password'] ?? '';

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'missing_user_id']);
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT user_id FROM User WHERE user_id = ?");
$stmt->bind_param('s', $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'user_not_found']);
    exit;
}

// Update user information
if ($new_password) {
    // If new password provided, also update password (plaintext storage)
    if (strlen($new_password) < 6) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'password_too_short']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE User SET user_phone = ?, user_email = ?, user_password = ? WHERE user_id = ?");
    $stmt->bind_param('ssss', $user_phone, $user_email, $new_password, $user_id);
} else {
    // Only update phone and email
    $stmt = $conn->prepare("UPDATE User SET user_phone = ?, user_email = ? WHERE user_id = ?");
    $stmt->bind_param('sss', $user_phone, $user_email, $user_id);
}

if ($stmt->execute()) {
    $updateTime = perf_stop('update');
    echo json_encode(['ok'=>true,'performance'=>['update_time'=>$updateTime,'formatted'=>number_format($updateTime * 1000, 2) . ' ms']]);
} else {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'update_failed']);
}
