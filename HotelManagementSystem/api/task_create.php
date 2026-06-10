<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('insert');

$body = json_decode(file_get_contents('php://input'), true);
$room_id = $body['room_id'] ?? '';
$customer_id = $body['customer_id'] ?? null;
$cleaner_id = $body['cleaner_id'] ?? '';
$wish_time = $body['wish_time'] ?? '';
$notes = $body['notes'] ?? '';

// Customer creates: has customer_id, auto-assign cleaner_id
// Front_desk creates: has cleaner_id, customer_id is null

// Handle customer_id
if ($customer_id === '' || $customer_id === 'null') {
    $customer_id = null;
}

// Handle cleaner_id: if not specified, auto-assign
if (!$cleaner_id || $cleaner_id === '' || $cleaner_id === 'null') {
    // Query all cleaner type users
    $cleanerRes = $conn->query("SELECT user_id FROM User WHERE user_type = 'cleaner' ORDER BY user_id");
    $cleaners = [];
    if ($cleanerRes) {
        while ($row = $cleanerRes->fetch_assoc()) {
            $cleaners[] = $row['user_id'];
        }
    }
    
    if (count($cleaners) > 0) {
        // Query each cleaner's task count, assign to the one with fewest tasks
        $taskCounts = [];
        foreach ($cleaners as $cid) {
            $countRes = $conn->query("SELECT COUNT(*) as cnt FROM Cleaning_task WHERE cleaner_id = '$cid' AND task_status != 'completed'");
            $count = $countRes ? $countRes->fetch_assoc()['cnt'] : 0;
            $taskCounts[$cid] = $count;
        }
        // Find cleaner with minimum tasks
        asort($taskCounts);
        $cleaner_id = array_key_first($taskCounts);
    } else {
        $cleaner_id = null;
    }
}

if (!$room_id || !$wish_time) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'invalid_params']);
    exit;
}
$status = 'pending';
$stmt = $conn->prepare("INSERT INTO `Cleaning_task`(room_id, customer_id, cleaner_id, task_status, wish_time, notes) VALUES(?,?,?,?,?,?)");
$stmt->bind_param('ssssss', $room_id, $customer_id, $cleaner_id, $status, $wish_time, $notes);
if ($stmt->execute()) {
    $task_id = $conn->insert_id;
    $insertTime = perf_stop('insert');
    
    echo json_encode(['ok'=>true,'task_id'=>$task_id,'data'=>[
        'task_id'=>$task_id,
        'room_id'=>$room_id,
        'customer_id'=>$customer_id,
        'cleaner_id'=>$cleaner_id,
        'task_status'=>$status,
        'wish_time'=>$wish_time,
        'notes'=>$notes
    ],'performance'=>['insert_time'=>$insertTime,'formatted'=>number_format($insertTime * 1000, 2) . ' ms']]);
} else {
    http_response_code(500);
    echo json_encode(['ok'=>false]);
}
