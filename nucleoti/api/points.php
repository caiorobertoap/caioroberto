<?php
require_once '../config.php';

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $arquiteto_id = $data['arquiteto_id'];
    $points = $data['points'];
    $lojista_id = $data['lojista_id'] ?? null;
    $date = $data['transaction_date'] ?? date('Y-m-d');
    
    // Buscar office_id do arquiteto
    $stmt = $conn->prepare("SELECT office_id FROM users WHERE id = ? AND role = 'arquiteto'");
    $stmt->execute([$arquiteto_id]);
    $arq = $stmt->fetch();
    
    if (!$arq) {
        http_response_code(404);
        echo json_encode(['error' => 'Arquiteto nao encontrado']);
        exit;
    }
    
    // Buscar campanha ativa para a data
    $stmt = $conn->prepare("
        SELECT id FROM campaigns 
        WHERE active = 1 AND start_date <= ? AND end_date >= ?
        LIMIT 1
    ");
    $stmt->execute([$date, $date]);
    $campaign = $stmt->fetch();
    
    // Inserir pontos
    $stmt = $conn->prepare("
        INSERT INTO points_transactions (lojista_id, arquiteto_id, office_id, campaign_id, points, transaction_date) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $lojista_id,
        $arquiteto_id,
        $arq['office_id'],
        $campaign['id'] ?? null,
        $points,
        $date
    ]);
    
    echo json_encode(['id' => $conn->lastInsertId(), 'points' => $points]);
}
?>
