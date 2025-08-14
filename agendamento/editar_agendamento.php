<?php
// editar_agendamento.php

$caminhoJSON = __DIR__ . '/data/agendamentos.json';

$id = $_GET['id'] ?? '';

$agendamentos = [];
if (file_exists($caminhoJSON)) {
    $agendamentos = json_decode(file_get_contents($caminhoJSON), true);
    if (!is_array($agendamentos)) $agendamentos = [];
}

$agendamento = null;
foreach ($agendamentos as $a) {
    if (($a['id'] ?? '') === $id) {
        $agendamento = $a;
        break;
    }
}

if (!$agendamento) {
    http_response_code(404);
    echo "Agendamento não encontrado.";
    exit;
}

$mostrarSucesso = isset($_GET['sucesso']) && $_GET['sucesso'] == '1';

function esc($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Editar Agendamento - <?= esc($agendamento['nome']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
  body { background: #0f1720; color: #f8fafc; }
  .container { max-width: 700px; margin-top: 30px; }
  .card { background: linear-gradient(180deg,#111827 0%, #0b1220 100%); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); }
  label { color: #f1f5f9; }
  .form-control, .form-select {
    background-color: #1e293b;
    border: 1px solid #334155;
    color: #f8fafc;
  }
  .form-control:focus {
    background-color: #1e293b;
    color: #f8fafc;
    border-color: #3b82f6;
    box-shadow: 0 0 0 0.2rem rgba(59,130,246,.25);
  }
  .btn-primary {
    background-color: #3b82f6;
    border: none;
  }
  .btn-primary:hover {
    background-color: #2563eb;
  }
  .img-thumb {
    max-width: 100px;
    max-height: 100px;
    object-fit: cover;
    margin-right: 10px;
    border-radius: 8px;
    border: 1px solid #334155;
  }
</style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h2 class="mb-4">Editar Agendamento</h2>

      <?php if ($mostrarSucesso): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          Agendamento atualizado com sucesso!
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
      <?php endif; ?>

      <form id="formEditar" action="salvar_edicao_agendamento.php" method="POST" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="id" value="<?= esc($agendamento['id']) ?>" />

        <div class="mb-3">
          <label for="clienteNome" class="form-label">Nome Completo *</label>
          <input type="text" id="clienteNome" name="clienteNome" class="form-control" required value="<?= esc($agendamento['nome']) ?>" />
        </div>

        <div class="mb-3">
          <label for="telefone" class="form-label">Telefone / WhatsApp *</label>
          <input type="text" id="telefone" name="telefone" class="form-control" required value="<?= esc($agendamento['telefone']) ?>" />
        </div>

        <div class="mb-3">
          <label for="dataAgendamento" class="form-label">Data do Agendamento *</label>
          <input type="date" id="dataAgendamento" name="dataAgendamento" class="form-control" required value="<?= esc($agendamento['data']) ?>" />
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="horaInicio" class="form-label">Hora Início *</label>
            <input type="time" id="horaInicio" name="horaInicio" class="form-control" required value="<?= esc($agendamento['horaInicio'] ?? '') ?>" />
          </div>
          <div class="col-md-6 mb-3">
            <label for="horaFim" class="form-label">Hora Fim *</label>
            <input type="time" id="horaFim" name="horaFim" class="form-control" required value="<?= esc($agendamento['horaFim'] ?? '') ?>" />
          </div>
        </div>

        <div class="mb-3">
          <label for="tatuador" class="form-label">Tatuador *</label>
          <select id="tatuador" name="tatuador" class="form-select" required>
            <option value="">Selecione um tatuador</option>
            <option value="daniel" <?= (isset($agendamento['tatuador']) && $agendamento['tatuador'] === 'daniel') ? 'selected' : '' ?>>Daniel</option>
            <option value="theo" <?= (isset($agendamento['tatuador']) && $agendamento['tatuador'] === 'theo') ? 'selected' : '' ?>>Theo</option>
            <option value="meduri" <?= (isset($agendamento['tatuador']) && $agendamento['tatuador'] === 'meduri') ? 'selected' : '' ?>>Meduri</option>
            <option value="wesley" <?= (isset($agendamento['tatuador']) && $agendamento['tatuador'] === 'wesley') ? 'selected' : '' ?>>Wesley</option>
            <option value="will" <?= (isset($agendamento['tatuador']) && $agendamento['tatuador'] === 'will') ? 'selected' : '' ?>>Will</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="parteCorpo" class="form-label">Parte do Corpo a ser Tatuada *</label>
          <input type="text" id="parteCorpo" name="parteCorpo" class="form-control" required placeholder="Ex: braço, costas, perna..." value="<?= esc($agendamento['parte_corpo']) ?>" />
        </div>

        <div class="mb-3">
          <label for="descricao" class="form-label">Descrição da Tatuagem</label>
          <textarea id="descricao" name="descricao" class="form-control" rows="3" placeholder="Descreva a tatuagem, estilo, cores, etc"><?= esc($agendamento['descricao'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
          <label>Imagens de Referência Existentes:</label>
          <div class="d-flex flex-wrap mb-3">
            <?php if (!empty($agendamento['imagensReferencia'])): ?>
              <?php foreach ($agendamento['imagensReferencia'] as $img): ?>
                <img src="<?= esc($img) ?>" alt="Imagem de Referência" class="img-thumb" />
              <?php endforeach; ?>
            <?php else: ?>
              <p>Nenhuma imagem enviada.</p>
            <?php endif; ?>
          </div>
        </div>

        <div class="mb-3">
          <label for="imagens" class="form-label">Adicionar Novas Imagens de Referência (opcional)</label>
          <input type="file" id="imagens" name="imagens[]" class="form-control" accept="image/*" multiple />
        </div>

        <button type="submit" class="btn btn-primary w-100">Salvar Alterações</button>
      </form>

      <a href="ver_agendamento.php?id=<?= urlencode($agendamento['id']) ?>" class="btn btn-secondary mt-3">Voltar para Visualizar</a>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
