<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('query');

// Room and Order tables don't have hotel_id field, this is a single-hotel system
// So directly query all room statistics

// Get total room count
$res = $conn->query("SELECT COUNT(*) as total_rooms FROM Room");
$total_rooms = $res ? $res->fetch_assoc()['total_rooms'] : 0;

// Detect actual status field name in database
$statusCol = 'room_status';
$probeCols = ['room_status', 'room_statue'];
foreach ($probeCols as $c) {
    $chk = $conn->query("SHOW COLUMNS FROM Room LIKE '$c'");
    if ($chk && $chk->num_rows > 0) { $statusCol = $c; break; }
}

// Get unavailable room count (room_status != 'available')
$res = $conn->query("SELECT COUNT(*) as occupied_rooms FROM Room WHERE `$statusCol` != 'available'");
$occupied_rooms = $res ? $res->fetch_assoc()['occupied_rooms'] : 0;

// Get today's booking count (check_in is today and status is Pending or Checked In)
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT COUNT(*) as today_bookings 
    FROM `Order` 
    WHERE DATE(check_in) = ? 
    AND order_status IN ('Pending', 'Checked In')
");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$today_bookings = $result->fetch_assoc()['today_bookings'];
$stmt->close();

$queryTime = perf_stop('query');

$conn->close();

echo json_encode([
    'ok' => true,
    'data' => [
        'total_rooms' => (int)$total_rooms,
        'occupied_rooms' => (int)$occupied_rooms,
        'available_rooms' => (int)($total_rooms - $occupied_rooms),
        'today_bookings' => (int)$today_bookings
    ],
    'performance'=>['query_time'=>$queryTime,'formatted'=>number_format($queryTime * 1000, 2) . ' ms']
]);
?>
