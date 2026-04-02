<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = strtolower(trim($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    $name = $data['name'] ?? '';
    $role = $data['role'] ?? 'lojista';
    
    if (empty($email) || empty($password) || empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados obrigatorios faltando']);
        exit;
    }
    
    try {
        $conn = getConnection();
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Email ja cadastrado']);
            exit;
        }
        
        $hash = hashPassword($password);
        
        if ($role === 'lojista') {
            $stmt = $conn->prepare("
                INSERT INTO users (email, password_hash, name, role, cnpj, address, responsible, phone, created_at) 
                VALUES (?, ?, ?, 'lojista', ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $email, $hash, $name,
                $data['cnpj'] ?? null,
                $data['address'] ?? null,
                $data['responsible'] ?? null,
                $data['phone'] ?? null
            ]);
        } else if ($role === 'arquiteto') {
            $office_id = !empty($data['office_id']) ? $data['office_id'] : null;
            
            $stmt = $conn->prepare("
                INSERT INTO users (email, password_hash, name, role, cpf, birth_date, address, phone, office_id, created_at) 
                VALUES (?, ?, ?, 'arquiteto', ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $email, $hash, $name,
                $data['cpf'] ?? null,
                $data['birth_date'] ?? null,
                $data['address'] ?? null,
                $data['phone'] ?? null,
                $office_id
            ]);
        }
        
        $userId = $conn->lastInsertId();
        
        echo json_encode([
            'id' => $userId,
            'email' => $email,
            'name' => $name,
            'role' => $role
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao cadastrar: ' . $e->getMessage()]);
    }
}
?>
