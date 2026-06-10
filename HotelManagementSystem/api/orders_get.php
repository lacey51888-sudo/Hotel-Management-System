<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('query');

// detect datetime column names
$checkInCol = 'check_in';
$checkOutCol = 'check_out';
$existsIn = $conn->query("SHOW COLUMNS FROM `Order` LIKE 'check_in'");
$existsOut = $conn->query("SHOW COLUMNS FROM `Order` LIKE 'check_out'");
if (!($existsIn && $existsIn->num_rows)) {
    $altIn = $conn->query("SHOW COLUMNS FROM `Order` LIKE 'start_day'");
    if ($altIn && $altIn->num_rows) $checkInCol = 'start_day';
}
if (!($existsOut && $existsOut->num_rows)) {
    $altOut = $conn->query("SHOW COLUMNS FROM `Order` LIKE 'finish_day'");
    if ($altOut && $altOut->num_rows) $checkOutCol = 'finish_day';
}

$baseSelect = "SELECT o.order_id, o.user_id, o.room_id, r.type_id, o.actual_capacity, o.`$checkInCol` AS check_in, o.`$checkOutCol` AS check_out, o.order_status, o.staff_id, o.total_amount FROM `Order` o LEFT JOIN Room r ON o.room_id = r.room_id";
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
if ($user_id) {
    $stmt = $conn->prepare($baseSelect . " WHERE o.user_id = ? ORDER BY o.order_id DESC");
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($baseSelect . " ORDER BY o.order_id DESC");
}
$list = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $list[] = [
            'order_id'=>$row['order_id'],
            'user_id'=>$row['user_id'],
            'room_id'=>$row['room_id'],
            'type_id'=>$row['type_id'],
            'actual_capacity'=>$row['actual_capacity'],
            'check_in'=>$row['check_in'],
            'check_out'=>$row['check_out'],
            'order_status'=>$row['order_status'],
            'staff_id'=>$row['staff_id'],
            'total_amount'=>$row['total_amount'] ?? 0
        ];
    }
}
$queryTime = perf_stop('query');

echo json_encode(['ok'=>true,'data'=>$list,'performance'=>['query_time'=>$queryTime,'formatted'=>number_format($queryTime * 1000, 2) . ' ms']]);
