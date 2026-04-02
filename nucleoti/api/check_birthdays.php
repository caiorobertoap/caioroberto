<?php
require_once '../config.php';

$conn = getConnection();
$today = date('m-d');
$current_year = date('Y');

// Buscar aniversariantes do dia
$stmt = $conn->prepare("
    SELECT id, name, email, birth_date 
    FROM users 
    WHERE DATE_FORMAT(birth_date, '%m-%d') = ?
    AND role = 'arquiteto'
");
$stmt->execute([$today]);
$aniversariantes = $stmt->fetchAll();

$mensagens_enviadas = [];

// Verificar se tabela birthday_messages existe
try {
    foreach ($aniversariantes as $pessoa) {
        $stmt = $conn->prepare("SELECT id FROM birthday_messages WHERE user_id = ? AND year = ?");
        $stmt->execute([$pessoa['id'], $current_year]);
        
        if ($stmt->rowCount() == 0) {
            $birth_year = date('Y', strtotime($pessoa['birth_date']));
            $idade = $current_year - $birth_year;
            
            $mensagem = "Feliz Aniversario, {$pessoa['name']}!\n\n";
            $mensagem .= "A equipe Nosso Nucleo Design deseja um dia maravilhoso!\n";
            $mensagem .= "Que este novo ano de vida seja repleto de conquistas e sucesso!\n\n";
            $mensagem .= "Parabens pelos seus $idade anos!";
            
            $stmt = $conn->prepare("INSERT INTO birthday_messages (user_id, message, sent_date, year) VALUES (?, ?, CURDATE(), ?)");
            $stmt->execute([$pessoa['id'], $mensagem, $current_year]);
            
            $mensagens_enviadas[] = [
                'user_id' => $pessoa['id'],
                'name' => $pessoa['name'],
                'email' => $pessoa['email'],
                'idade' => $idade,
                'mensagem' => $mensagem
            ];
        }
    }
} catch (Exception $e) {
    // Se tabela nao existir, apenas listar aniversariantes
}

echo json_encode([
    'data' => date('Y-m-d'),
    'aniversariantes_hoje' => count($aniversariantes),
    'aniversariantes' => array_map(function($a) {
        return ['name' => $a['name'], 'email' => $a['email']];
    }, $aniversariantes),
    'mensagens_enviadas' => $mensagens_enviadas
]);
?>
