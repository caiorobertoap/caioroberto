<?php
require_once '../config.php';

$conn = getConnection();

$campaign_id = $_GET['campaign_id'] ?? null;
$lojista_id = $_GET['lojista_id'] ?? null;
$office_id = $_GET['office_id'] ?? null;
$month = $_GET['month'] ?? null;
$year = $_GET['year'] ?? null;

// Montar WHERE dinamicamente
$where = [];
$params = [];

if ($campaign_id) {
    $where[] = "pt.campaign_id = ?";
    $params[] = $campaign_id;
}
if ($lojista_id) {
    $where[] = "pt.lojista_id = ?";
    $params[] = $lojista_id;
}
if ($office_id) {
    $where[] = "pt.office_id = ?";
    $params[] = $office_id;
}
if ($month) {
    $where[] = "DATE_FORMAT(pt.transaction_date, '%Y-%m') = ?";
    $params[] = $month;
}
if ($year) {
    $where[] = "YEAR(pt.transaction_date) = ?";
    $params[] = $year;
}

$whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Quantidade de arquitetos que pontuaram
$stmt = $conn->prepare("SELECT COUNT(DISTINCT pt.arquiteto_id) as total FROM points_transactions pt $whereClause");
$stmt->execute($params);
$arquitetos_pontuaram = (int)$stmt->fetch()['total'];

// Quantidade de lojas que pontuaram
$stmt = $conn->prepare("SELECT COUNT(DISTINCT pt.lojista_id) as total FROM points_transactions pt $whereClause");
$stmt->execute($params);
$lojas_pontuaram = (int)$stmt->fetch()['total'];

// Total de pontos
$stmt = $conn->prepare("SELECT COALESCE(SUM(pt.points), 0) as total FROM points_transactions pt $whereClause");
$stmt->execute($params);
$total_pontos = (int)$stmt->fetch()['total'];

// Numero de transacoes
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM points_transactions pt $whereClause");
$stmt->execute($params);
$num_transacoes = (int)$stmt->fetch()['total'];

// Top arquitetos
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.email, SUM(pt.points) as pontos
    FROM users u
    JOIN points_transactions pt ON u.id = pt.arquiteto_id
    $whereClause
    GROUP BY u.id, u.name, u.email
    ORDER BY pontos DESC
    LIMIT 10
");
$stmt->execute($params);
$top_arquitetos = $stmt->fetchAll();

// Top lojas
$stmt = $conn->prepare("
    SELECT u.id, u.name, SUM(pt.points) as pontos
    FROM users u
    JOIN points_transactions pt ON u.id = pt.lojista_id
    $whereClause
    GROUP BY u.id, u.name
    ORDER BY pontos DESC
    LIMIT 10
");
$stmt->execute($params);
$top_lojas = $stmt->fetchAll();

// Top escritorios
$stmt = $conn->prepare("
    SELECT o.id, o.name, SUM(pt.points) as pontos
    FROM offices o
    JOIN points_transactions pt ON o.id = pt.office_id
    $whereClause
    GROUP BY o.id, o.name
    ORDER BY pontos DESC
    LIMIT 10
");
$stmt->execute($params);
$top_escritorios = $stmt->fetchAll();

// Listas para filtros
$campanhas_lista = $conn->query("SELECT id, name FROM campaigns ORDER BY start_date DESC")->fetchAll();
$lojas_lista = $conn->query("SELECT id, name FROM users WHERE role = 'lojista' ORDER BY name")->fetchAll();
$escritorios_lista = $conn->query("SELECT id, name FROM offices ORDER BY name")->fetchAll();

echo json_encode([
    'filtros_aplicados' => [
        'campaign_id' => $campaign_id,
        'lojista_id' => $lojista_id,
        'office_id' => $office_id,
        'month' => $month,
        'year' => $year
    ],
    'resumo' => [
        'arquitetos_pontuaram' => $arquitetos_pontuaram,
        'lojas_pontuaram' => $lojas_pontuaram,
        'total_pontos' => $total_pontos,
        'num_transacoes' => $num_transacoes
    ],
    'rankings' => [
        'top_arquitetos' => $top_arquitetos,
        'top_lojas' => $top_lojas,
        'top_escritorios' => $top_escritorios
    ],
    'opcoes_filtro' => [
        'campanhas' => $campanhas_lista,
        'lojas' => $lojas_lista,
        'escritorios' => $escritorios_lista
    ]
]);
?>
