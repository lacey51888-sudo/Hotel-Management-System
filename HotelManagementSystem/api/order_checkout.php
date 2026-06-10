<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('transaction');

$body = json_decode(file_get_contents('php://input'), true);
$room_id = $body['room_id'] ?? '';
if (!$room_id) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'invalid_params']);
    exit;
}
$conn->begin_transaction();
try {
    $stmtR = $conn->prepare("UPDATE Room SET room_status='available' WHERE room_id=?");
    $stmtR->bind_param('s', $room_id);
    $stmtR->execute();
    $stmtO = $conn->prepare("UPDATE `Order` SET order_status='Completed' WHERE room_id=? AND order_status='Checked In'");
    $stmtO->bind_param('s', $room_id);
    $stmtO->execute();
    $conn->commit();
    
    $transactionTime = perf_stop('transaction');
    echo json_encode(['ok'=>true,'performance'=>['transaction_time'=>$transactionTime,'formatted'=>number_format($transactionTime * 1000, 2) . ' ms']]);
} catch(Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'transaction_failed','debug'=>$e->getMessage()]);
}
