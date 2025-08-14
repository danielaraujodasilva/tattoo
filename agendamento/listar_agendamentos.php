<?php
// listar_agendamentos.php

$caminhoJSON = __DIR__ . '/data/agendamentos.json';

// Carrega agendamentos
$agendamentos = [];
if (file_exists($caminhoJSON)) {
    $agendamentos = json_decode(file_get_contents($caminhoJSON), true);
    if (!is_array($agendamentos)) $agendamentos = [];
}

// Tratamento de exclusão
if (isset($_GET['excluir'])) {
    $idExcluir = $_GET['excluir'];
    $agendamentos = array_filter($agendamentos, fn($a) => ($a['id'] ?? '') != $idExcluir);
    // Reindexar array
    $agendamentos = array_values($agendamentos);
    file_put_contents($caminhoJSON, json_encode($agendamentos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: listar_agendamentos.php');
    exit;
}

// Filtros simples: nome e data
$filtroNome = $_GET['filtro_nome'] ?? '';
$filtroData = $_GET['filtro_data'] ?? '';

// Filtra agendamentos
$agendamentosFiltrados = array_filter($agendamentos, function($a) use ($filtroNome, $filtroData) {
    $okNome = true;
    $okData = true;
    if ($filtroNome !== '') {
        $okNome = mb_stripos($a['nome'] ?? '', $filtroNome) !== false;
    }
    if ($filtroData !== '') {
        $okData = ($a['data'] ?? '') === $filtroData;
    }
    return $okNome && $okData;
});

// Paginação
$paginaAtual = max(1, intval($_GET['pagina'] ?? 1));
$itensPorPagina = 10;
$totalItens = count($agendamentosFiltrados);
$totalPaginas = max(1, ceil($totalItens / $itensPorPagina));
$inicio = ($paginaAtual - 1) * $itensPorPagina;
$agendamentosPagina = array_slice($agendamentosFiltrados, $inicio, $itensPorPagina);

function esc($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Listar Agendamentos - Estúdio</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background: #0f1720; color: #f8fafc; }
  .card { background: linear-gradient(180deg,#111827 0%, #0b1220 100%); border-radius: 12px; border: 1px solid rgba(255,255,255,0.04); color: inherit; padding: 20px; margin-top: 20px; }
  a, .btn { color: #f8fafc; }
  .table thead th { border-bottom: 2px solid #444; }
  .table tbody tr:hover { background-color: #1e293b; }
</style>
</head>
<body class="container py-4">
  <h1>Agendamentos</h1>

  <form method="GET" class="row g-3 align-items-center mb-3">
    <div class="col-md-5">
      <input type="text" name="filtro_nome" class="form-control" placeholder="Filtrar por nome" value="<?= esc($filtroNome) ?>" />
    </div>
    <div class="col-md-3">
      <input type="date" name="filtro_data" class="form-control" value="<?= esc($filtroData) ?>" />
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
    <div class="col-md-2 text-end">
      <a href="index.html" class="btn btn-success">Novo Agendamento</a>
    </div>
  </form>

  <div class="card table-responsive">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Data</th>
          <th>Horário</th>
          <th>Tatuador</th>
          <th>Parte do Corpo</th>
          <th>Telefone</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($agendamentosPagina)) : ?>
        <tr><td colspan="7" class="text-center">Nenhum agendamento encontrado.</td></tr>
      <?php else: ?>
        <?php foreach($agendamentosPagina as $agendamento): ?>
          <tr>
            <td><?= esc($agendamento['nome'] ?? '') ?></td>
            <td><?= esc($agendamento['data'] ?? '') ?></td>
            <td>
              <?= esc(($agendamento['horaInicio'] ?? '') . ' - ' . ($agendamento['horaFim'] ?? '')) ?>
            </td>
            <td><?= esc($agendamento['tatuador'] ?? '') ?></td>
            <td><?= esc($agendamento['parte_corpo'] ?? '') ?></td>
            <td><?= esc($agendamento['telefone'] ?? '') ?></td>
            <td>
              <a href="ver_agendamento.php?id=<?= urlencode($agendamento['id'] ?? '') ?>" class="btn btn-sm btn-info">Ver</a>
              <a href="editar_agendamento.php?id=<?= urlencode($agendamento['id'] ?? '') ?>" class="btn btn-sm btn-warning">Editar</a>
              <a href="listar_agendamentos.php?excluir=<?= urlencode($agendamento['id'] ?? '') ?>" class="btn btn-sm btn-danger" onclick="return confirm('Excluir este agendamento?');">Excluir</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginação -->
  <nav aria-label="Paginação" class="mt-3">
    <ul class="pagination justify-content-center">
      <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
        <li class="page-item <?= $p === $paginaAtual ? 'active' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $p])) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>

</body>
</html>
