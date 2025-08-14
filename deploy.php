<?php
// deploy.php

// Segredo simples para segurança
$secret = "tattoo*123";

$input = file_get_contents('php://input');
$headers = getallheaders();

if (!isset($headers['X-Hub-Signature'])) {
    http_response_code(403);
    die('No signature');
}

// Verifica assinatura (opcional, mas recomendado)
$hash = 'sha1=' . hash_hmac('sha1', $input, $secret);
if (!hash_equals($hash, $headers['X-Hub-Signature'])) {
    http_response_code(403);
    die('Invalid signature');
}

// Aqui o deploy acontece
exec('cd C:\xampp\htdocs\site && git pull origin main 2>&1', $output);
echo implode("\n", $output);
