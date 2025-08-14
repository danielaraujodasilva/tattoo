<?php
// excluir_agendamento.php

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'msg' => 'Método não permitido']);
    exit;
}

$id = $_POST['id'] ?? '';
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'msg' => 'ID do agendamento é obrigatório']);
    exit;
}

$caminhoJSON = __DIR__ . '/data/agendamentos.json';

$agendamentos = [];
if (file_exists($caminhoJSON)) {
    $agendamentos = json_decode(file_get_contents($caminhoJSON), true);
    if (!is_array($agendamentos)) $agendamentos = [];
}

$index = null;
foreach ($agendamentos as $k => $a) {
    if (($a['id'] ?? '') === $id) {
        $index = $k;
        break;
    }
}

if ($index === null) {
    http_response_code(404);
    echo json_encode(['success' => false, 'msg' => 'Agendamento não encontrado']);
    exit;
}

// Remove o agendamento
array_splice($agendamentos, $index, 1);

// Salva o JSON atualizado
if (file_put_contents($caminhoJSON, json_encode($agendamentos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Erro ao salvar dados']);
}
