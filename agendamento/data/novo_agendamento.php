<?php
// novo_agendamento.php

$dirData = __DIR__ . '/data/';
$fileJson = $dirData . 'agendamentos.json';

$errors = [];
$success = false;

$uploadDir = $dirData . 'referencias/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clienteNome = trim($_POST['clienteNome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $dataAgendamento = trim($_POST['dataAgendamento'] ?? '');
    $horaAgendamento = trim($_POST['horaAgendamento'] ?? '');
    $parteCorpo = trim($_POST['parteCorpo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if ($clienteNome === '') $errors[] = 'Nome do cliente é obrigatório.';
    if ($telefone === '') $errors[] = 'Telefone é obrigatório.';
    if ($dataAgendamento === '') $errors[] = 'Data do agendamento é obrigatória.';
    if ($horaAgendamento === '') $errors[] = 'Hora do agendamento é obrigatória.';

    $imagensReferencia = [];

    if (!empty($_FILES['imagensReferencia']['name'][0])) {
        foreach ($_FILES['imagensReferencia']['tmp_name'] as $key => $tmpName) {
            $nomeArquivo = basename($_FILES['imagensReferencia']['name'][$key]);
            $ext = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
            $permitidos = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($ext, $permitidos)) {
                $errors[] = "Arquivo {$nomeArquivo} não é uma imagem permitida.";
                continue;
            }

            $novoNome = uniqid('ref_') . '.' . $ext;
            $destino = $uploadDir . $novoNome;

            if (move_uploaded_file($tmpName, $destino)) {
                $imagensReferencia[] = 'data/referencias/' . $novoNome;
            } else {
                $errors[] = "Erro ao salvar o arquivo {$nomeArquivo}.";
            }
        }
    }

    if (empty($errors)) {
        // Carrega agendamentos existentes
        $agendamentos = [];
        if (file_exists($fileJson)) {
            $jsonContent = file_get_contents($fileJson);
            $agendamentos = json_decode($jsonContent, true);
            if (!is_array($agendamentos)) $agendamentos = [];
        }

        // Gera ID simples (timestamp + rand)
        $novoId = uniqid();

        $novoAgendamento = [
            'id' => $novoId,
            'clienteNome' => $clienteNome,
            'telefone' => $telefone,
            'dataAgendamento' => $dataAgendamento,
            'horaAgendamento' => $horaAgendamento,
            'parteCorpo' => $parteCorpo,
            'descricao' => $descricao,
            'imagensReferencia' => $imagensReferencia,
        ];

        $agendamentos[] = $novoAgendamento;

        file_put_contents($fileJson, json_encode($agendamentos, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        header('Location: listar_agendamentos.php');
        exit;
    }
}

function esc($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Novo Agendamento</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
  body { background: #0f1720; color: #f8fafc; }
  .container { margin-top: 30px; max-width: 800px; }
  .card { background: linear-gradient(180deg,#111827 0%, #0b1220 100%); border-radius: 12px; border: 1px solid rgba(255,255,255,0.04); color: inherit; padding: 20px; }
  .form-label { color: #f1f5f9; }
  .form-control, .form-select, textarea.form-control {
    background-color: #fff;
    color: #0b1220;
  }
  a { color: #f8fafc; }
</style>
</head>
<body class="container">
  <div class="card">
    <h1 class="mb-3">Novo Agendamento</h1>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?=esc($err)?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
      <div class="mb-3">
        <label class="form-label">Nome do Cliente *</label>
        <input type="text" name="clienteNome" class="form-control" value="<?=esc($_POST['clienteNome'] ?? '')?>" required />
      </div>
      <div class="mb-3">
        <label class="form-label">Telefone *</label>
        <input type="text" name="telefone" class="form-control" value="<?=esc($_POST['telefone'] ?? '')?>" required />
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label">Data do Agendamento *</label>
          <input type="date" name="dataAgendamento" class="form-control" value="<?=esc($_POST['dataAgendamento'] ?? '')?>" required />
        </div>
        <div class="col-md-6">
          <label class="form-label">Hora do Agendamento *</label>
          <input type="time" name="horaAgendamento" class="form-control" value="<?=esc($_POST['horaAgendamento'] ?? '')?>" required />
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Parte do corpo a ser tatuada</label>
        <input type="text" name="parteCorpo" class="form-control" value="<?=esc($_POST['parteCorpo'] ?? '')?>" />
      </div>
      <div class="mb-3">
        <label class="form-label">Descrição / Observações</label>
        <textarea name="descricao" class="form-control" rows="3"><?=esc($_POST['descricao'] ?? '')?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Imagens de referência</label>
        <input type="file" name="imagensReferencia[]" accept="image/*" multiple class="form-control" />
        <small class="text-muted">Envie uma ou mais imagens para referência.</small>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <a href="listar_agendamentos.php" class="btn btn-outline-light">Voltar</a>
        <button type="submit" class="btn btn-primary">Salvar Agendamento</button>
      </div>
    </form>
  </div>
</body>
</html>
