<?php
// calendario_agendamentos_events.php

header('Content-Type: application/json; charset=utf-8');

$caminhoJSON = __DIR__ . '/data/agendamentos.json';

$agendamentos = [];
if (file_exists($caminhoJSON)) {
    $agendamentos = json_decode(file_get_contents($caminhoJSON), true);
    if (!is_array($agendamentos)) $agendamentos = [];
}

$filtroTatuador = $_GET['tatuador'] ?? '';

$coresPorTatuador = [
    'daniel' => '#3b82f6',  // azul
    'theo' => '#10b981',    // verde
    'meduri' => '#f97316',  // laranja
    'wesley' => '#e11d48',  // vermelho
    'will' => '#8b5cf6',    // roxo
];

// Filtrar agendamentos conforme tatuador
if ($filtroTatuador !== '') {
    $agendamentos = array_filter($agendamentos, fn($a) => ($a['tatuador'] ?? '') === $filtroTatuador);
}

$eventos = [];
foreach ($agendamentos as $a) {
    // Ex: data, horaInicio, horaFim
    $data = $a['data'] ?? '';
    $horaInicio = $a['horaInicio'] ?? '';
    $horaFim = $a['horaFim'] ?? '';

    if (!$data || !$horaInicio || !$horaFim) continue;

    $start = $data . 'T' . $horaInicio;
    $end = $data . 'T' . $horaFim;

    $tatuador = $a['tatuador'] ?? '';
    $cor = $coresPorTatuador[$tatuador] ?? '#64748b'; // cinza default

    $eventos[] = [
        'id' => $a['id'] ?? uniqid(),
        'title' => ($a['nome'] ?? 'Sem nome') . ' (' . ucfirst($tatuador) . ')',
        'start' => $start,
        'end' => $end,
        'color' => $cor,
        'extendedProps' => [
            'telefone' => $a['telefone'] ?? '',
            'parte_corpo' => $a['parte_corpo'] ?? '',
        ]
    ];
}

echo json_encode(array_values($eventos), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
