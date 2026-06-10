<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('transaction');

$body = json_decode(file_get_contents('php://input'), true);
$order_id = $body['order_id'] ?? '';
if (!$order_id) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'invalid_params']);
    exit;
}
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("SELECT o.room_id, r.type_id, o.order_status FROM `Order` o JOIN Room r ON o.room_id = r.room_id WHERE o.order_id=? FOR UPDATE");
    $stmt->bind_param('s', $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if (!$row) throw new Exception('order_not_found');
    if ($row['order_status'] !== 'Pending') throw new Exception('cannot_cancel');
    $type_id = $row['type_id'];
    $room_id = $row['room_id'];
    
    // Update room status to available, trigger will automatically update Inventory's available_count
    $stmtRU = $conn->prepare("UPDATE Room SET room_status='available' WHERE room_id=?");
    $stmtRU->bind_param('s', $room_id);
    $stmtRU->execute();
    $stmtOC = $conn->prepare("UPDATE `Order` SET order_status='Cancelled' WHERE order_id=?");
    $stmtOC->bind_param('s', $order_id);
    $stmtOC->execute();
    $conn->commit();
    
    $transactionTime = perf_stop('transaction');
    echo json_encode(['ok'=>true,'performance'=>['transaction_time'=>$transactionTime,'formatted'=>number_format($transactionTime * 1000, 2) . ' ms']]);
} catch(Exception $e) {
    $conn->rollback();
    http_response_code(409);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
