<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('insert');

$body = json_decode(file_get_contents('php://input'), true);
$user_id = $body['user_id'] ?? '';
$type_id = $body['type_id'] ?? '';
$score = (int)($body['score'] ?? 0);
$text = $body['text'] ?? '';
if (!$user_id || !$type_id || !$score) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'missing_params']);
    exit;
}
// Verify if user has an order for this room type
$stmt = $conn->prepare("SELECT COUNT(*) c FROM `Order` o JOIN Room r ON o.room_id = r.room_id WHERE o.user_id=? AND r.type_id=?");
$stmt->bind_param('ss', $user_id, $type_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if (!$row || (int)$row['c'] === 0) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'no_order_for_this_room_type']);
    exit;
}
$now = date('Y-m-d H:i:s');
$stmt2 = $conn->prepare("INSERT INTO Comment(type_id, user_id, score, text, comment_time) VALUES(?,?,?,?,?)");
$stmt2->bind_param('ssiss', $type_id, $user_id, $score, $text, $now);
if ($stmt2->execute()) {
    $insertTime = perf_stop('insert');
    echo json_encode(['ok'=>true,'performance'=>['insert_time'=>$insertTime,'formatted'=>number_format($insertTime * 1000, 2) . ' ms']]);
} else {
    http_response_code(500);
    echo json_encode(['ok'=>false]);
}
