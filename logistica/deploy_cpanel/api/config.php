<?php
/**
 * NTO LOGISTICS - Configuração do Banco de Dados
 * ALTERE AS CREDENCIAIS ABAIXO
 */

// ========== CONFIGURAÇÕES DO BANCO ==========
define('DB_HOST', 'localhost');
define('DB_NAME', 'NOME_DO_SEU_BANCO');    // Altere aqui
define('DB_USER', 'SEU_USUARIO');          // Altere aqui
define('DB_PASS', 'SUA_SENHA');            // Altere aqui

// ========== CONFIGURAÇÕES DE E-MAIL (SMTP) ==========
define('MAIL_FROM',      'noreply@labmedclin.med.br'); // E-mail remetente (deve existir no servidor)
define('MAIL_FROM_NAME', 'NTO Logística Medclin');     // Nome exibido como remetente
define('SMTP_HOST',      'mail.labmedclin.med.br');    // Servidor SMTP
define('SMTP_PORT',      587);                          // Porta SMTP (587 = STARTTLS)
define('SMTP_USER',      'SEU_EMAIL@labmedclin.med.br'); // Usuário SMTP — altere aqui
define('SMTP_PASS',      'SUA_SENHA_EMAIL');             // Senha SMTP    — altere aqui

// ========== CONFIGURAÇÕES JWT ==========
define('JWT_SECRET', 'nto-logistics-chave-secreta-2024');
define('JWT_EXPIRATION', 86400); // 24 horas

// ========== CORS ==========
define('CORS_ORIGIN', '*');

// ========== TIMEZONE ==========
date_default_timezone_set('America/Sao_Paulo');

// ========== CONEXÃO PDO ==========
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        // Forçar fuso horário de Brasília na sessão MySQL
        // Isso garante que NOW() retorne o horário local correto
        $pdo->exec("SET time_zone = '-03:00'");
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode(['error' => 'Erro de conexão: ' . $e->getMessage()]));
    }
}

// ========== HEADERS ==========
function setHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: ' . CORS_ORIGIN);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// ========== FUNÇÕES AUXILIARES ==========
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function jsonError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['detail' => $message], JSON_UNESCAPED_UNICODE);
    exit();
}

// ========== ENVIO DE E-MAIL VIA SMTP (STARTTLS / porta 587) ==========
function sendSmtpMail(string $toEmail, string $toName, string $subject, string $body): bool {
    $host    = SMTP_HOST;
    $port    = SMTP_PORT;
    $user    = SMTP_USER;
    $pass    = SMTP_PASS;
    $from    = MAIL_FROM;
    $fromName= MAIL_FROM_NAME;

    $timeout = 15;
    $sock = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$sock) return false;

    $read = function() use ($sock) {
        $line = '';
        while (!feof($sock)) {
            $line = fgets($sock, 512);
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return $line;
    };
    $send = function(string $cmd) use ($sock) { fwrite($sock, $cmd . "\r\n"); };

    $read(); // banner
    $send("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $ehlo = '';
    while (!feof($sock)) {
        $line = fgets($sock, 512);
        $ehlo .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') break;
    }

    // STARTTLS
    $send("STARTTLS");
    $read();
    $crypto = stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    if (!$crypto) { fclose($sock); return false; }

    // Re-EHLO após TLS
    $send("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    while (!feof($sock)) {
        $line = fgets($sock, 512);
        if (strlen($line) < 4 || $line[3] === ' ') break;
    }

    // AUTH LOGIN
    $send("AUTH LOGIN");
    $read();
    $send(base64_encode($user));
    $read();
    $send(base64_encode($pass));
    $authResp = $read();
    if (strpos($authResp, '235') === false) { fclose($sock); return false; }

    // Envelope
    $send("MAIL FROM:<{$from}>");
    $read();
    $send("RCPT TO:<{$toEmail}>");
    $rcpt = $read();
    if (strpos($rcpt, '250') === false) { fclose($sock); return false; }

    $send("DATA");
    $read();

    // Cabeçalhos e corpo
    $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encFrom    = '=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>';
    $encTo      = (trim($toName) ? '=?UTF-8?B?' . base64_encode($toName) . '?= <' . $toEmail . '>' : $toEmail);
    $date       = date('r');
    $msgId      = '<' . uniqid('nto', true) . '@' . $host . '>';

    $msg  = "Date: {$date}\r\n";
    $msg .= "From: {$encFrom}\r\n";
    $msg .= "To: {$encTo}\r\n";
    $msg .= "Subject: {$encSubject}\r\n";
    $msg .= "Message-ID: {$msgId}\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n";
    $msg .= "\r\n";
    $msg .= chunk_split(base64_encode($body));
    $msg .= "\r\n.";

    $send($msg);
    $dataResp = $read();

    $send("QUIT");
    $read();
    fclose($sock);

    return strpos($dataResp, '250') !== false;
}
