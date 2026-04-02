<?php
require_once '../config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Nao autenticado']);
    exit;
}

$conn = getConnection();
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Apenas admin pode fazer upload']);
    exit;
}

// REMOVER LOGO
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $logo_path = dirname(__DIR__) . '/assets/images/logo.png';
    if (file_exists($logo_path)) {
        unlink($logo_path);
        echo json_encode(['success' => true, 'message' => 'Logo removida com sucesso']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Nenhuma logo para remover']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['logo'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nenhum arquivo enviado']);
        exit;
    }
    
    $file = $_FILES['logo'];
    
    $allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    if (!in_array($file['type'], $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Apenas PNG, JPG ou WEBP']);
        exit;
    }
    
    if ($file['size'] > 2 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'Arquivo muito grande (max 2MB)']);
        exit;
    }
    
    $upload_dir = dirname(__DIR__) . '/assets/images/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $destination = $upload_dir . 'logo.png';
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        echo json_encode([
            'success' => true,
            'message' => 'Logo atualizada com sucesso',
            'path' => 'assets/images/logo.png'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao salvar arquivo']);
    }
}
?>
