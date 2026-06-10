<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('transaction');

$body = json_decode(file_get_contents('php://input'), true);
$user_id = $body['user_id'] ?? '';
$room_id = $body['room_id'] ?? '';
$type_id = $body['type_id'] ?? '';
$actual_capacity = (int)($body['actual_capacity'] ?? 1);
$check_in = $body['check_in'] ?? '';
$check_out = $body['check_out'] ?? '';
$staff_id = $body['staff_id'] ?? '';
$total_amount = floatval($body['total_amount'] ?? 0);
if (!$check_in || !$check_out) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'invalid_params']);
    exit;
}
if (!$type_id && !$room_id) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'missing_type_or_room']);
    exit;
}
$conn->begin_transaction();
try {
    // If no room_id provided, need to find available room based on type_id
    if (!$room_id) {
        if (!$type_id) {
            throw new Exception('missing_type_id');
        }
        // Check inventory
        $stmt = $conn->prepare("SELECT available_count FROM Inventory WHERE type_id=? FOR UPDATE");
        $stmt->bind_param('s', $type_id);
        $stmt->execute();
        $invRes = $stmt->get_result();
        $row = $invRes->fetch_assoc();
        if (!$row || (int)$row['available_count'] <= 0) {
            throw new Exception('sold_out');
        }
        // Find available room
        $stmtR = $conn->prepare("SELECT room_id FROM Room WHERE type_id=? AND room_status='available' LIMIT 1 FOR UPDATE");
        $stmtR->bind_param('s', $type_id);
        $stmtR->execute();
        $rRes = $stmtR->get_result();
        $rRow = $rRes->fetch_assoc();
        if (!$rRow) throw new Exception('no_room');
        $room_id = $rRow['room_id'];
    } else {
        // If room_id provided, need to get the room's type_id and check status
        $stmtC = $conn->prepare("SELECT type_id, room_status FROM Room WHERE room_id=? FOR UPDATE");
        $stmtC->bind_param('s', $room_id);
        $stmtC->execute();
        $cRes = $stmtC->get_result();
        $cRow = $cRes->fetch_assoc();
        if (!$cRow) throw new Exception('room_not_found');
        if ($cRow['room_status'] !== 'available') throw new Exception('room_unavailable');
        $type_id = $cRow['type_id']; // Get type_id from Room table
        
        // Check inventory
        $stmt = $conn->prepare("SELECT available_count FROM Inventory WHERE type_id=? FOR UPDATE");
        $stmt->bind_param('s', $type_id);
        $stmt->execute();
        $invRes = $stmt->get_result();
        $row = $invRes->fetch_assoc();
        if (!$row || (int)$row['available_count'] <= 0) {
            throw new Exception('sold_out');
        }
    }
    
    // Update room status to unavailable, trigger will automatically update Inventory's available_count
    $stmtRU = $conn->prepare("UPDATE Room SET room_status='unavailable' WHERE room_id=?");
    $stmtRU->bind_param('s', $room_id);
    $stmtRU->execute();
    $stmtO = $conn->prepare("INSERT INTO `Order`(user_id, room_id, actual_capacity, check_in, check_out, order_status, staff_id, total_amount) VALUES(?,?,?,?,?,?,?,?)");
    $status = 'Pending';
    $stmtO->bind_param('ssissssd', $user_id, $room_id, $actual_capacity, $check_in, $check_out, $status, $staff_id, $total_amount);
    $stmtO->execute();
    $order_id = $conn->insert_id;
    $conn->commit();
    
    $transactionTime = perf_stop('transaction');
    
    echo json_encode(['ok'=>true,'order_id'=>$order_id,'room_id'=>$room_id,'performance'=>['transaction_time'=>$transactionTime,'formatted'=>number_format($transactionTime * 1000, 2) . ' ms']]);
} catch(Exception $e) {
    $conn->rollback();
    http_response_code(409);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
