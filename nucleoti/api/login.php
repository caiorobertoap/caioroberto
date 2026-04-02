<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = strtolower(trim($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email e senha sao obrigatorios']);
        exit;
    }
    
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuario nao encontrado']);
            exit;
        }
        
        if (!verifyPassword($password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Senha incorreta']);
            exit;
        }
        
        unset($user['password_hash']);
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        
        echo json_encode($user);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro no servidor: ' . $e->getMessage()]);
    }
}
?>
