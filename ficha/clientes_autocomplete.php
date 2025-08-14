<?php
header('Content-Type: application/json; charset=utf-8');

$caminhoJSON = __DIR__ . '/data/anamneses.json';
$registros = [];

if (file_exists($caminhoJSON)) {
    $registros = json_decode(file_get_contents($caminhoJSON), true);
    if (!is_array($registros)) $registros = [];
}

$term = $_GET['term'] ?? '';

$sugestoes = [];

foreach ($registros as $r) {
    $nome = $r['nome'] ?? '';
    $cpf = $r['cpf'] ?? '';
    $telefone = $r['telefone'] ?? '';


    // Filtra pelo termo (ignora maiúsculas/minúsculas)
    if ($term === '' || stripos($nome, $term) !== false || stripos($cpf, $term) !== false || stripos($telefone, $term) !== false) {
        $label = trim("$nome — $cpf — $telefone");
        $sugestoes[] = ['label' => $label, 'value' => $nome, 'data' => $r];
    }
}

echo json_encode($sugestoes);
