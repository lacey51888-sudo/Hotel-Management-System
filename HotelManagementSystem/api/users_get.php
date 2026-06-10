<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('query');

$user_type = isset($_GET['user_type']) ? $_GET['user_type'] : '';

if ($user_type) {
    $stmt = $conn->prepare("SELECT user_id, user_name, user_phone, user_email, user_type FROM User WHERE user_type = ? ORDER BY user_id");
    $stmt->bind_param('s', $user_type);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query("SELECT user_id, user_name, user_phone, user_email, user_type FROM User ORDER BY user_id");
}

$list = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $list[] = $row;
    }
}

$queryTime = perf_stop('query');

echo json_encode(['ok'=>true, 'data'=>$list, 'performance'=>['query_time'=>$queryTime,'formatted'=>number_format($queryTime * 1000, 2) . ' ms']]);
