<?php
require_once '../config.php';

$conn = getConnection();

// LISTAR
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->query("SELECT * FROM campaigns ORDER BY start_date DESC");
    $campaigns = $stmt->fetchAll();
    foreach ($campaigns as &$camp) {
        $camp['id'] = (int)$camp['id'];
        $camp['active'] = (bool)$camp['active'];
    }
    echo json_encode($campaigns);
}

// CRIAR
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $start = $data['start_date'];
    $months = (int)$data['duration_months'];
    $end = date('Y-m-d', strtotime($start . " + $months months"));
    
    $stmt = $conn->prepare("
        INSERT INTO campaigns (
            name, description, duration_months, start_date, end_date, 
            min_points_per_store, min_store_percentage,
            premio_primeiro, premio_segundo, premio_terceiro, active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([
        $data['name'],
        $data['description'] ?? null,
        $months,
        $start,
        $end,
        $data['min_points_per_store'] ?? 2000,
        $data['min_store_percentage'] ?? 60,
        $data['premio_primeiro'] ?? null,
        $data['premio_segundo'] ?? null,
        $data['premio_terceiro'] ?? null
    ]);
    
    echo json_encode(['id' => $conn->lastInsertId(), 'message' => 'Campanha criada']);
}

// ACOES: DELETAR, ZERAR, FINALIZAR, ATIVAR
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $campaign_id = $data['campaign_id'] ?? 0;
    
    if (!$campaign_id) {
        http_response_code(400);
        echo json_encode(['error' => 'campaign_id obrigatorio']);
        exit;
    }
    
    if ($action === 'delete') {
        $conn->prepare("DELETE FROM points_transactions WHERE campaign_id = ?")->execute([$campaign_id]);
        $conn->prepare("DELETE FROM campaigns WHERE id = ?")->execute([$campaign_id]);
        echo json_encode(['message' => 'Campanha excluida']);
    } else if ($action === 'reset') {
        $conn->prepare("DELETE FROM points_transactions WHERE campaign_id = ?")->execute([$campaign_id]);
        echo json_encode(['message' => 'Pontos da campanha zerados']);
    } else if ($action === 'finish') {
        $conn->prepare("UPDATE campaigns SET active = 0 WHERE id = ?")->execute([$campaign_id]);
        echo json_encode(['message' => 'Campanha finalizada']);
    } else if ($action === 'activate') {
        $conn->prepare("UPDATE campaigns SET active = 1 WHERE id = ?")->execute([$campaign_id]);
        echo json_encode(['message' => 'Campanha reativada']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Acao invalida']);
    }
}
?>
