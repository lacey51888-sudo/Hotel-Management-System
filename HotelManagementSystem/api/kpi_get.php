<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('query');

try {
    // Single batch aggregation to avoid N+1 queries:
    // - Pre-aggregate total/completed counts for each staff_id from Order
    // - Pre-aggregate total/completed counts for each cleaner from Cleaning_task
    $sql = "
        SELECT
            u.user_id,
            u.user_name,
            u.user_phone,
            u.user_email,
            u.user_type,
            COALESCE(o.total_orders, 0) AS total_orders,
            COALESCE(o.completed_orders, 0) AS completed_orders,
            COALESCE(c.total_tasks, 0) AS total_tasks,
            COALESCE(c.completed_tasks, 0) AS completed_tasks
        FROM User u
        LEFT JOIN (
            SELECT staff_id, COUNT(*) AS total_orders,
                   SUM(order_status IN ('Completed', 'Checked In')) AS completed_orders
            FROM `Order`
            WHERE staff_id IS NOT NULL
            GROUP BY staff_id
        ) o ON u.user_id = o.staff_id
        LEFT JOIN (
            SELECT cleaner_id, COUNT(*) AS total_tasks,
                   SUM(task_status = 'completed') AS completed_tasks
            FROM Cleaning_task
            WHERE cleaner_id IS NOT NULL
            GROUP BY cleaner_id
        ) c ON u.user_id = c.cleaner_id
        WHERE u.user_type IN ('staff', 'cleaner', 'manager')
    ";

    $stmt = $conn->query($sql);
    $result = [];

    while ($row = $stmt->fetch_assoc()) {
        $isCleaner = $row['user_type'] === 'cleaner';

        $total = $isCleaner ? (int)$row['total_tasks'] : (int)$row['total_orders'];
        $completed = $isCleaner ? (int)$row['completed_tasks'] : (int)$row['completed_orders'];

        $rate = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

        $status = 'unqualified';
        if ($rate >= 90) {
            $status = 'excellent';
        } else if ($rate >= 80) {
            $status = 'qualified';
        }

        $result[] = [
            'user_id' => $row['user_id'],
            'user_name' => $row['user_name'],
            'user_phone' => $row['user_phone'],
            'user_email' => $row['user_email'],
            'user_type' => $row['user_type'],
            'position' => $row['user_type'] === 'staff' ? 'reception' : $row['user_type'],
            'total' => $total,
            'completed' => $completed,
            'rate' => $rate,
            'status' => $status
        ];
    }
    
    $queryTime = perf_stop('query');
    
    echo json_encode([
        'ok' => true,
        'data' => $result,
        'summary' => [
            'total_employees' => count($result),
            'average_rate' => count($result) > 0 ? round(array_sum(array_column($result, 'rate')) / count($result), 2) : 0,
            'excellent_count' => count(array_filter($result, fn($item) => $item['status'] === 'excellent')),
            'qualified_count' => count(array_filter($result, fn($item) => $item['status'] === 'qualified')),
            'unqualified_count' => count(array_filter($result, fn($item) => $item['status'] === 'unqualified'))
        ],
        'performance' => ['query_time'=>$queryTime,'formatted'=>number_format($queryTime * 1000, 2) . ' ms']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
