<?php
require_once '../config.php';
session_start();

$conn = getConnection();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Nao autenticado']);
    exit;
}

$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user['role'] === 'admin') {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'arquiteto'");
    $arquitetos = $stmt->fetch()['count'];
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'lojista'");
    $lojistas = $stmt->fetch()['count'];
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM offices");
    $offices = $stmt->fetch()['count'];
    
    $stmt = $conn->query("SELECT COALESCE(SUM(points), 0) as total FROM points_transactions");
    $total_points = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM campaigns WHERE active = 1");
    $campanhas_ativas = $stmt->fetch()['count'];
    
    echo json_encode([
        'arquitetos' => (int)$arquitetos,
        'lojistas' => (int)$lojistas,
        'offices' => (int)$offices,
        'total_points' => (int)$total_points,
        'campanhas_ativas' => (int)$campanhas_ativas
    ]);
} else if ($user['role'] === 'lojista') {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(points), 0) as total FROM points_transactions WHERE lojista_id = ?");
    $stmt->execute([$user_id]);
    $total_points = $stmt->fetch()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT arquiteto_id) as count FROM points_transactions WHERE lojista_id = ?");
    $stmt->execute([$user_id]);
    $arquitetos_count = $stmt->fetch()['count'];
    
    echo json_encode([
        'total_points' => (int)$total_points,
        'arquitetos_count' => (int)$arquitetos_count
    ]);
} else if ($user['role'] === 'arquiteto') {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(points), 0) as total FROM points_transactions WHERE arquiteto_id = ?");
    $stmt->execute([$user_id]);
    $total_points = $stmt->fetch()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT lojista_id) as count FROM points_transactions WHERE arquiteto_id = ?");
    $stmt->execute([$user_id]);
    $lojas_count = $stmt->fetch()['count'];
    
    echo json_encode([
        'total_points' => (int)$total_points,
        'lojas_count' => (int)$lojas_count
    ]);
}
?>
