<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('query');

// detect actual status column name to avoid fatal errors
$statusCol = 'room_status';
$probeCols = ['room_status', 'room_statue'];
foreach ($probeCols as $c) {
    $chk = $conn->query("SHOW COLUMNS FROM Room LIKE '$c'");
    if ($chk && $chk->num_rows > 0) { $statusCol = $c; break; }
}

$sql = "SELECT room_id, type_id, floor, room_number, `$statusCol` AS room_status FROM Room";
$res = $conn->query($sql);
$list = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $list[] = [
            'room_id'=>$row['room_id'],
            'type_id'=>$row['type_id'],
            'floor'=>$row['floor'],
            'room_number'=>$row['room_number'],
            'room_status'=>$row['room_status']
        ];
    }
}

$queryTime = perf_stop('query');

echo json_encode(['ok'=>true,'data'=>$list,'performance'=>['query_time'=>$queryTime,'formatted'=>number_format($queryTime * 1000, 2) . ' ms']]);
