<?php
// agendar.php

$caminhoJSON = __DIR__ . '/data/agendamentos.json';

$erro = '';
$sucesso = '';

function esc($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function gerarIdUnico() {
    return bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $data = trim($_POST['data'] ?? '');
    $hora = trim($_POST['hora'] ?? '');
    $parte_corpo = trim($_POST['parte_corpo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if (!$nome || !$telefone || !$data || !$hora || !$parte_corpo) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        $agendamentos = [];
        if (file_exists($caminhoJSON)) {
            $agendamentos = json_decode(file_get_contents($caminhoJSON), true);
            if (!is_array($agendamentos)) $agendamentos = [];
        }

        // Lida com upload das imagens de referência
        $imagens = [];
        if (!empty($_FILES['imagens_referencia']['name'][0])) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            foreach ($_FILES['imagens_referencia']['tmp_name'] as $index => $tmpName) {
                if ($_FILES['imagens_referencia']['error'][$index] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['imagens_referencia']['name'][$index], PATHINFO_EXTENSION);
                    $novoNome = uniqid('imgref_', true) . '.' . $ext;
                    $destino = $uploadDir . $novoNome;
                    if (move_uploaded_file($tmpName, $destino)) {
                        $imagens[] = 'uploads/' . $novoNome;
                    }
                }
            }
        }

        $novoAgendamento = [
            'id' => gerarIdUnico(),
            'nome' => $nome,
            'telefone' => $telefone,
            'data' => $data,
            'hora' => $hora,
            'parte_corpo' => $parte_corpo,
            'descricao' => $descricao,
            'imagens' => $imagens,
        ];

        $agendamentos[] = $novoAgendamento;

        if (file_put_contents($caminhoJSON, json_encode($agendamentos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            $sucesso = "Agendamento criado com sucesso.";
            // Limpa os campos após sucesso
            $nome = $telefone = $data = $hora = $parte_corpo = $descricao = '';
            $imagens = [];
        } else {
            $erro = "Erro ao salvar o agendamento.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Novo Agendamento - Estúdio</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
  body { background: #0f1720; color: #f8fafc; }
  .card { background: linear-gradient(180deg,#111827 0%, #0b1220 100%); border-radius: 12px; border: 1px solid rgba(255,255,255,0.04); color: inherit; padding: 20px; margin-top: 20px; }
  label { color: #f1f5f9; }
  .btn { color: #f8fafc; }
</style>
</head>
<body class="container py-4">
  <h1>Novo Agendamento</h1>

  <?php if ($erro): ?>
    <div class="alert alert-danger"><?= esc($erro) ?></div>
  <?php elseif ($sucesso): ?>
    <div class="alert alert-success"><?= esc($sucesso) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST" enctype="multipart/form-data" novalidate>
      <div class="mb-3">
        <label class="form-label">Nome *</label>
        <input type="text" name="nome" class="form-control" required value="<?= esc($nome ?? '') ?>" />
      </div>

      <div class="mb-3">
        <label class="form-label">Telefone / WhatsApp *</label>
        <input type="text" name="telefone" class="form-control" required value="<?= esc($telefone ?? '') ?>" />
      </div>

      <div class="row g-3">
        <div class="col-md-6 mb-3">
          <label class="form-label">Data *</label>
          <input type="date" name="data" class="form-control" required value="<?= esc($data ?? '') ?>" />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Hora *</label>
          <input type="time" name="hora" class="form-control" required value="<?= esc($hora ?? '') ?>" />
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Parte do Corpo *</label>
        <input type="text" name="parte_corpo" class="form-control" required value="<?= esc($parte_corpo ?? '') ?>" />
      </div>

      <div class="mb-3">
        <label class="form-label">Descrição / Observações</label>
        <textarea name="descricao" class="form-control" rows="4"><?= esc($descricao ?? '') ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Imagens de Referência</label>
        <input type="file" name="imagens_referencia[]" multiple accept="image/*" class="form-control" />
      </div>

      <div class="d-flex justify-content-between">
        <a href="listar_agendamentos.php" class="btn btn-secondary">Voltar</a>
        <button type="submit" class="btn btn-primary">Salvar Agendamento</button>
      </div>
    </form>
  </div>

</body>
</html>
	