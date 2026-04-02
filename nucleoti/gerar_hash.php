<?php
// gerar_hash.php - Use este arquivo para gerar o hash correto da senha

$senha = "Admin@2025";
$hash = password_hash($senha, PASSWORD_BCRYPT);

echo "<h2>Hash gerado para a senha: $senha</h2>";
echo "<p><strong>Hash:</strong> $hash</p>";
echo "<hr>";
echo "<h3>Execute este SQL no phpMyAdmin:</h3>";
echo "<pre>";
echo "UPDATE users SET password_hash = '$hash' WHERE email = 'admin@nucleodesign.com';";
echo "</pre>";
echo "<hr>";
echo "<p>Depois de executar o SQL, delete este arquivo por segurança!</p>";
?>
