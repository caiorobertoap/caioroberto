<?php
// testar_conexao.php - Teste se o banco está funcionando

require_once 'config.php';

echo "<h2>Teste de Conexão</h2>";

try {
    $conn = getConnection();
    echo "<p style='color: green;'>✅ Conexão com banco de dados OK!</p>";
    
    // Verificar se a tabela users existe
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Tabela 'users' existe!</p>";
        
        // Contar usuários
        $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
        $result = $stmt->fetch();
        echo "<p>Total de usuários cadastrados: <strong>{$result['total']}</strong></p>";
        
        // Verificar admin
        $stmt = $conn->query("SELECT email, name, role FROM users WHERE role = 'admin'");
        $admins = $stmt->fetchAll();
        
        if (count($admins) > 0) {
            echo "<p style='color: green;'>✅ Usuário admin encontrado:</p>";
            foreach ($admins as $admin) {
                echo "<ul>";
                echo "<li>Email: {$admin['email']}</li>";
                echo "<li>Nome: {$admin['name']}</li>";
                echo "<li>Role: {$admin['role']}</li>";
                echo "</ul>";
            }
        } else {
            echo "<p style='color: red;'>❌ Nenhum admin encontrado!</p>";
            echo "<p>Execute o SQL de criação do admin.</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Tabela 'users' NÃO existe!</p>";
        echo "<p>Execute o arquivo criar_tabelas.sql no phpMyAdmin</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='gerar_hash.php'>Gerar novo hash de senha</a></p>";
echo "<p><a href='index.html'>Voltar para o sistema</a></p>";
?>
