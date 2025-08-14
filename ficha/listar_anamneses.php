<?php
$caminhoJSON = __DIR__ . '/data/anamneses.json';
$registros = [];
if (file_exists($caminhoJSON)) {
    $registros = json_decode(file_get_contents($caminhoJSON), true);
    if (!is_array($registros)) $registros = [];
}

function somarPagamentos($pagamentos) {
    $total = 0;
    if (!is_array($pagamentos)) return 0;
    foreach ($pagamentos as $k => $v) {
        if ($k === 'credito_parcelado_vezes') continue;
        if (is_array($v)) {
            $valorParcela = floatval(str_replace(',', '.', str_replace('.', '', $v['valor'] ?? '0')));
            $parcelas = intval($v['parcelas'] ?? 1);
            $total += $valorParcela * $parcelas;
        } else {
            $total += floatval(str_replace(',', '.', str_replace('.', '', $v)));
        }
    }
    return $total;
}

function formatarValor($val) {
    if ($val === '' || $val === null || $val == 0) return '-';
    return 'R$ ' . number_format($val, 2, ',', '.');
}
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Lista de Fichas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<style>
  /* Força texto branco em todo body e seus elementos */
  body, body * {
    color: #fff !important;
  }
  /* Mantém fundo escuro do body */
  body {
    background:#0b1220 !important;
  }
  .card {
    background: linear-gradient(180deg,#0f1720,#071024);
    border-radius:12px;
    padding:18px;
  }
  /* Força texto branco nas células e cabeçalhos da tabela */
  table.dataTable thead th,
  table.dataTable tbody td {
    color: #fff !important;
    background-color: transparent !important;
  }
  .assinatura-thumb {
    max-width:120px;
    border:1px solid #ccc;
    background:#fff;
  }
</style>
</head>
<body class="container py-4">
  <div class="card">
    <h2>Fichas de Anamnese</h2>
    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">Buscar (autocomplete)</label>
        <input id="buscarGeral" class="form-control" placeholder="Digite nome, CPF ou telefone" />
      </div>
      <div class="col-md-6 text-end align-self-end">
        <a href="index.html" class="btn btn-outline-light">Nova Ficha</a>
      </div>
    </div>

    <table id="tabelaFichas" class="table table-striped table-bordered table-dark" style="width:100%">
      <thead>
        <tr>
          <th>Data</th>
          <th>Nome</th>
          <th>CPF</th>
          <th>Telefone</th>
          <th>Unidade</th>
          <th>Tatuador</th>
          <th>Valor Total</th>
          <th>Assinatura</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($registros as $i => $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['data_preenchimento'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['nome'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['cpf'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['telefone'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['unidade'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['tatuador'] ?? '') ?></td>
            <td>
              <?php 
                $valorTotal = somarPagamentos($r['pagamentos'] ?? []);
                echo formatarValor($valorTotal);
              ?>
            </td>
            <td>
              <?php if (!empty($r['assinatura_arquivo'])): ?>
                <img class="assinatura-thumb" src="data/assinaturas/<?= htmlspecialchars($r['assinatura_arquivo']) ?>" alt="assinatura">
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td>
              <a href="ver_anamnese.php?id=<?= $i ?>" target="_blank" class="btn btn-sm btn-primary">Ver / Imprimir</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function(){
  const table = $('#tabelaFichas').DataTable({
    pageLength: 50,
    order: [[0, 'desc']],
    language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json" }
  });

  // Build autocomplete source from table data (names, cpf, phone)
  const source = [];
  $('#tabelaFichas tbody tr').each(function(){
    const row = $(this);
    const nome = row.find('td').eq(1).text().trim();
    const cpf = row.find('td').eq(2).text().trim();
    const telefone = row.find('td').eq(3).text().trim();
    source.push(nome);
    if (cpf) source.push(cpf);
    if (telefone) source.push(telefone);
  });

  $("#buscarGeral").autocomplete({
    source: Array.from(new Set(source)), // remove duplicados
    minLength: 2,
    select: function(event, ui) {
      table.search(ui.item.value).draw();
    }
  });

  $('#buscarGeral').on('input', function(){
    table.search(this.value).draw();
  });
});
</script>
</body>
</html>
