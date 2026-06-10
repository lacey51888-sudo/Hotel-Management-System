<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('update');

$body = json_decode(file_get_contents('php://input'), true);
$room_id = $body['room_id'] ?? '';
$check_out = $body['check_out'] ?? '';
if (!$room_id || !$check_out) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'invalid_params']);
    exit;
}
$stmt = $conn->prepare("UPDATE `Order` SET check_out=? WHERE room_id=? AND order_status IN ('Pending','Checked In')");
$stmt->bind_param('ss', $check_out, $room_id);
if ($stmt->execute()) {
    $updateTime = perf_stop('update');
    echo json_encode(['ok'=>true,'performance'=>['update_time'=>$updateTime,'formatted'=>number_format($updateTime * 1000, 2) . ' ms']]);
} else {
    http_response_code(500);
    echo json_encode(['ok'=>false]);
}
