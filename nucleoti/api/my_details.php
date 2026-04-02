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

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario nao encontrado']);
        exit;
    }

    unset($user['password_hash']);
    $response = ['user' => $user];

    // ========== ARQUITETO ==========
    if ($user['role'] === 'arquiteto') {
        // Pontos totais
        $stmt = $conn->prepare("SELECT COALESCE(SUM(points), 0) as total FROM points_transactions WHERE arquiteto_id = ?");
        $stmt->execute([$user_id]);
        $response['pontos_total'] = (int)$stmt->fetch()['total'];
        
        // Escritorio
        if ($user['office_id']) {
            $stmt = $conn->prepare("SELECT * FROM offices WHERE id = ?");
            $stmt->execute([$user['office_id']]);
            $office = $stmt->fetch();
            
            if ($office) {
                $stmt = $conn->prepare("SELECT COALESCE(SUM(points), 0) as total FROM points_transactions WHERE office_id = ?");
                $stmt->execute([$user['office_id']]);
                $office['pontos_total'] = (int)$stmt->fetch()['total'];
                $response['escritorio'] = $office;
            }
        }
        
        // Detalhes por loja
        $stmt = $conn->prepare("
            SELECT 
                l.id,
                l.name as loja_nome,
                SUM(pt.points) as pontos,
                COUNT(*) as num_transacoes,
                MIN(pt.transaction_date) as primeira_transacao,
                MAX(pt.transaction_date) as ultima_transacao
            FROM points_transactions pt
            JOIN users l ON pt.lojista_id = l.id
            WHERE pt.arquiteto_id = ?
            GROUP BY l.id, l.name
            ORDER BY pontos DESC
        ");
        $stmt->execute([$user_id]);
        $response['pontos_por_loja'] = $stmt->fetchAll();
        
        // Detalhes por campanha (com premiacoes)
        $stmt = $conn->prepare("
            SELECT 
                c.id,
                c.name as campanha_nome,
                c.start_date,
                c.end_date,
                c.active,
                c.premio_primeiro,
                c.premio_segundo,
                c.premio_terceiro,
                c.min_points_per_store,
                c.min_store_percentage,
                SUM(pt.points) as pontos,
                COUNT(DISTINCT pt.lojista_id) as lojas_pontuadas
            FROM points_transactions pt
            JOIN campaigns c ON pt.campaign_id = c.id
            WHERE pt.arquiteto_id = ?
            GROUP BY c.id, c.name, c.start_date, c.end_date, c.active, 
                     c.premio_primeiro, c.premio_segundo, c.premio_terceiro,
                     c.min_points_per_store, c.min_store_percentage
            ORDER BY c.start_date DESC
        ");
        $stmt->execute([$user_id]);
        $campanhas = $stmt->fetchAll();
        
        // Para cada campanha, calcular posicao
        foreach ($campanhas as &$camp) {
            $stmt = $conn->prepare("
                SELECT u.id, COALESCE(SUM(pt.points), 0) as pontos
                FROM users u
                LEFT JOIN points_transactions pt ON u.id = pt.arquiteto_id AND pt.campaign_id = ?
                WHERE u.role = 'arquiteto'
                GROUP BY u.id
                ORDER BY pontos DESC
            ");
            $stmt->execute([$camp['id']]);
            $ranking = $stmt->fetchAll();
            
            $posicao = 0;
            foreach ($ranking as $i => $r) {
                if ($r['id'] == $user_id) {
                    $posicao = $i + 1;
                    break;
                }
            }
            $camp['posicao_ranking'] = $posicao;
            $camp['total_arquitetos'] = count($ranking);
        }
        
        $response['pontos_por_campanha'] = $campanhas;
        
        // Posicao no ranking geral
        $stmt = $conn->query("
            SELECT u.id, COALESCE(SUM(pt.points), 0) as pontos
            FROM users u
            LEFT JOIN points_transactions pt ON u.id = pt.arquiteto_id
            WHERE u.role = 'arquiteto'
            GROUP BY u.id
            ORDER BY pontos DESC
        ");
        $ranking = $stmt->fetchAll();
        
        $posicao = 0;
        foreach ($ranking as $i => $r) {
            if ($r['id'] == $user_id) {
                $posicao = $i + 1;
                break;
            }
        }
        $response['posicao_ranking'] = $posicao;
        $response['total_arquitetos'] = count($ranking);
    }

    // ========== LOJISTA ==========
    else if ($user['role'] === 'lojista') {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(points), 0) as total FROM points_transactions WHERE lojista_id = ?");
        $stmt->execute([$user_id]);
        $response['pontos_distribuidos'] = (int)$stmt->fetch()['total'];
        
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT arquiteto_id) as total FROM points_transactions WHERE lojista_id = ?");
        $stmt->execute([$user_id]);
        $response['arquitetos_atendidos'] = (int)$stmt->fetch()['total'];
        
        // Ranking de arquitetos na campanha ativa
        $stmt = $conn->query("
            SELECT id FROM campaigns 
            WHERE active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE()
            ORDER BY start_date DESC LIMIT 1
        ");
        $campanha_ativa = $stmt->fetch();
        
        if ($campanha_ativa) {
            $stmt = $conn->prepare("
                SELECT u.id, u.name, COALESCE(SUM(pt.points), 0) as pontos_campanha
                FROM users u
                LEFT JOIN points_transactions pt ON u.id = pt.arquiteto_id AND pt.campaign_id = ?
                WHERE u.role = 'arquiteto'
                GROUP BY u.id, u.name
                ORDER BY pontos_campanha DESC
            ");
            $stmt->execute([$campanha_ativa['id']]);
            $response['ranking_arquitetos_campanha'] = $stmt->fetchAll();
        }
        
        // Historico
        $stmt = $conn->prepare("
            SELECT 
                a.name as arquiteto_nome,
                pt.points,
                pt.transaction_date,
                c.name as campanha_nome
            FROM points_transactions pt
            JOIN users a ON pt.arquiteto_id = a.id
            LEFT JOIN campaigns c ON pt.campaign_id = c.id
            WHERE pt.lojista_id = ?
            ORDER BY pt.transaction_date DESC
            LIMIT 50
        ");
        $stmt->execute([$user_id]);
        $response['historico_pontos'] = $stmt->fetchAll();
    }

    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro no servidor: ' . $e->getMessage()]);
}
?>
