<?php
require_once '../config.php';

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->query("SELECT * FROM offices ORDER BY name");
    echo json_encode($stmt->fetchAll());
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $conn->prepare("
        INSERT INTO offices (name, cnpj, address, responsible, contact) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['name'],
        $data['cnpj'] ?? null,
        $data['address'] ?? null,
        $data['responsible'] ?? null,
        $data['contact'] ?? null
    ]);
    
    echo json_encode(['id' => $conn->lastInsertId(), 'name' => $data['name']]);
}
?>
