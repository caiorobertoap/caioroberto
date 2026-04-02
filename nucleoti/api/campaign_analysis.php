<?php
require_once '../config.php';

$conn = getConnection();
$campaign_id = $_GET['campaign_id'] ?? null;

if (!$campaign_id) {
    http_response_code(400);
    echo json_encode(['error' => 'campaign_id e obrigatorio']);
    exit;
}

// Buscar dados da campanha (incluindo premiacoes)
$stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    http_response_code(404);
    echo json_encode(['error' => 'Campanha nao encontrada']);
    exit;
}

// Total de lojistas
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'lojista'");
$total_lojas = (int)$stmt->fetch()['total'];

$lojas_necessarias = $total_lojas > 0 ? (int)ceil($total_lojas * ($campaign['min_store_percentage'] / 100)) : 0;
$pontos_minimos = (int)$campaign['min_points_per_store'];

// Analise de cada arquiteto
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.email, u.office_id,
        COALESCE(SUM(pt.points), 0) as pontos_total,
        COUNT(DISTINCT pt.lojista_id) as lojas_pontuadas
    FROM users u
    LEFT JOIN points_transactions pt ON u.id = pt.arquiteto_id AND pt.campaign_id = ?
    WHERE u.role = 'arquiteto'
    GROUP BY u.id, u.name, u.email, u.office_id
    ORDER BY pontos_total DESC
");
$stmt->execute([$campaign_id]);
$arquitetos = $stmt->fetchAll();

$analise_arquitetos = [];
$ganhadores = [];

foreach ($arquitetos as $arq) {
    // Calcular lojas com pontuacao minima
    $stmt = $conn->prepare("
        SELECT l.id, l.name, SUM(pt.points) as pontos
        FROM points_transactions pt
        JOIN users l ON pt.lojista_id = l.id
        WHERE pt.arquiteto_id = ? AND pt.campaign_id = ?
        GROUP BY l.id, l.name
        HAVING pontos >= ?
    ");
    $stmt->execute([$arq['id'], $campaign_id, $pontos_minimos]);
    $lojas_qualificadas = $stmt->fetchAll();
    
    $num_lojas_qualificadas = count($lojas_qualificadas);
    $qualificado = ($lojas_necessarias > 0 && $num_lojas_qualificadas >= $lojas_necessarias);
    $progresso = $lojas_necessarias > 0 ? ($num_lojas_qualificadas / $lojas_necessarias) * 100 : 0;
    
    $item = [
        'id' => $arq['id'],
        'name' => $arq['name'],
        'email' => $arq['email'],
        'office_id' => $arq['office_id'],
        'pontos_total' => (int)$arq['pontos_total'],
        'lojas_pontuadas' => (int)$arq['lojas_pontuadas'],
        'lojas_qualificadas' => $num_lojas_qualificadas,
        'lojas_necessarias' => $lojas_necessarias,
        'qualificado' => $qualificado,
        'progresso_percentual' => round($progresso, 2),
        'faltam_lojas' => max(0, $lojas_necessarias - $num_lojas_qualificadas)
    ];
    
    $analise_arquitetos[] = $item;
    
    if ($qualificado && $item['pontos_total'] > 0) {
        $ganhadores[] = $item;
    }
}

// Ordenar ganhadores por pontos
usort($ganhadores, function($a, $b) {
    return $b['pontos_total'] - $a['pontos_total'];
});

// Ranking de lojistas na campanha
$stmt = $conn->prepare("
    SELECT u.id, u.name,
        COALESCE(SUM(pt.points), 0) as pontos_distribuidos,
        COUNT(DISTINCT pt.arquiteto_id) as arquitetos_atendidos
    FROM users u
    LEFT JOIN points_transactions pt ON u.id = pt.lojista_id AND pt.campaign_id = ?
    WHERE u.role = 'lojista'
    GROUP BY u.id, u.name
    ORDER BY pontos_distribuidos DESC
");
$stmt->execute([$campaign_id]);
$lojistas = $stmt->fetchAll();

// Ranking de escritorios na campanha
$stmt = $conn->prepare("
    SELECT o.id, o.name,
        COALESCE(SUM(pt.points), 0) as pontos_total,
        COUNT(DISTINCT pt.arquiteto_id) as arquitetos_ativos
    FROM offices o
    LEFT JOIN points_transactions pt ON o.id = pt.office_id AND pt.campaign_id = ?
    GROUP BY o.id, o.name
    ORDER BY pontos_total DESC
");
$stmt->execute([$campaign_id]);
$escritorios = $stmt->fetchAll();

// Premiacoes
$premiacoes = [
    'primeiro' => $campaign['premio_primeiro'] ?? null,
    'segundo' => $campaign['premio_segundo'] ?? null,
    'terceiro' => $campaign['premio_terceiro'] ?? null
];

// Montar relatorio de ganhadores (top 3 qualificados)
$relatorio_ganhadores = [];
for ($i = 0; $i < min(3, count($ganhadores)); $i++) {
    $premio_key = ['primeiro', 'segundo', 'terceiro'][$i];
    $ganhadores[$i]['premio'] = $premiacoes[$premio_key];
    $ganhadores[$i]['colocacao'] = $i + 1;
    
    // Buscar nome do escritorio se tiver
    if ($ganhadores[$i]['office_id']) {
        $stmt = $conn->prepare("SELECT name FROM offices WHERE id = ?");
        $stmt->execute([$ganhadores[$i]['office_id']]);
        $off = $stmt->fetch();
        $ganhadores[$i]['escritorio_nome'] = $off ? $off['name'] : null;
    }
    
    $relatorio_ganhadores[] = $ganhadores[$i];
}

// Proximos de vencer (80%+ progresso mas nao qualificado)
$proximos_vencer = array_values(array_filter($analise_arquitetos, function($a) {
    return $a['progresso_percentual'] >= 80 && !$a['qualificado'];
}));

echo json_encode([
    'campaign' => $campaign,
    'premiacoes' => $premiacoes,
    'criterios' => [
        'total_lojas' => $total_lojas,
        'lojas_necessarias' => $lojas_necessarias,
        'pontos_minimos_por_loja' => $pontos_minimos,
        'percentual_lojas' => (int)$campaign['min_store_percentage']
    ],
    'ranking_arquitetos' => $analise_arquitetos,
    'ranking_lojistas' => $lojistas,
    'ranking_escritorios' => $escritorios,
    'top_3_arquitetos' => array_slice($analise_arquitetos, 0, 3),
    'ganhadores' => $relatorio_ganhadores,
    'proximos_vencer' => $proximos_vencer,
    'campanha_finalizada' => !(bool)$campaign['active']
]);
?>
