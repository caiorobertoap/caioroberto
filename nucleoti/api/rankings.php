<?php
require_once '../config.php';

$conn = getConnection();
$type = $_GET['type'] ?? 'arquitetos';

if ($type === 'arquitetos') {
    $stmt = $conn->query("
        SELECT u.id, u.name, u.email,
            COALESCE(SUM(pt.points), 0) as total_points
        FROM users u
        LEFT JOIN points_transactions pt ON u.id = pt.arquiteto_id
        WHERE u.role = 'arquiteto'
        GROUP BY u.id, u.name, u.email
        ORDER BY total_points DESC
    ");
} else if ($type === 'lojistas') {
    $stmt = $conn->query("
        SELECT u.id, u.name, u.email,
            COALESCE(SUM(pt.points), 0) as total_points
        FROM users u
        LEFT JOIN points_transactions pt ON u.id = pt.lojista_id
        WHERE u.role = 'lojista'
        GROUP BY u.id, u.name, u.email
        ORDER BY total_points DESC
    ");
} else if ($type === 'offices') {
    $stmt = $conn->query("
        SELECT o.id, o.name,
            COALESCE(SUM(pt.points), 0) as total_points
        FROM offices o
        LEFT JOIN points_transactions pt ON o.id = pt.office_id
        GROUP BY o.id, o.name
        ORDER BY total_points DESC
    ");
}

echo json_encode($stmt->fetchAll());
?>
