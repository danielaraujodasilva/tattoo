<?php
// ver_agendamento.php

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

function esc($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Visualizar Agendamento - <?= esc($agendamento['nome']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
  body {
    background: #0f1720;
    color: #f8fafc !important;
  }
  .container {
    max-width: 700px;
    margin: 30px auto;
  }
  .card {
    background: linear-gradient(180deg,#111827 0%, #0b1220 100%);
    padding: 20px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.1);
    color: #f8fafc !important;
  }
  .card dt, .card dd, .card p, .card h2, .card a, .card label, .card small, .card span {
    color: #f8fafc !important;
  }
  .img-thumb {
    max-width: 100px;
    max-height: 100px;
    object-fit: cover;
    margin-right: 10px;
    margin-bottom: 10px;
    border-radius: 8px;
    border: 1px solid #334155;
    cursor: pointer;
    transition: transform 0.2s ease-in-out;
  }
  .img-thumb:hover {
    transform: scale(1.05);
  }
  .img-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.85);
    justify-content: center;
    align-items: center;
    z-index: 1050;
  }
  .img-overlay img {
    max-width: 90vw;
    max-height: 90vh;
    border-radius: 12px;
    box-shadow: 0 0 15px #000;
  }
  .img-overlay:target {
    display: flex;
  }
  .img-overlay .close-btn {
    position: fixed;
    top: 20px;
    right: 30px;
    font-size: 2.5rem;
    color: white;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
    z-index: 1060;
  }
</style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h2 class="mb-4">Visualizar Agendamento</h2>

      <dl>
        <dt>Nome Completo</dt>
        <dd><?= esc($agendamento['nome']) ?></dd>

        <dt>Telefone / WhatsApp</dt>
        <dd><?= esc($agendamento['telefone']) ?></dd>

        <dt>Data do Agendamento</dt>
        <dd><?= esc($agendamento['data']) ?></dd>

        <dt>Horário</dt>
        <dd><?= esc(($agendamento['horaInicio'] ?? '') . ' - ' . ($agendamento['horaFim'] ?? '')) ?></dd>

        <dt>Tatuador</dt>
        <dd><?= esc($agendamento['tatuador'] ?? '') ?></dd>

        <dt>Parte do Corpo a ser Tatuada</dt>
        <dd><?= esc($agendamento['parte_corpo']) ?></dd>

        <dt>Descrição da Tatuagem</dt>
        <dd><?= nl2br(esc($agendamento['descricao'] ?? '')) ?></dd>

        <dt>Imagens de Referência</dt>
        <dd>
          <?php if (!empty($agendamento['imagensReferencia'])): ?>
            <div class="d-flex flex-wrap">
              <?php foreach ($agendamento['imagensReferencia'] as $idx => $img): 
                $imgEsc = esc($img);
                $overlayId = "imgOverlay$idx";
              ?>
                <a href="#<?= $overlayId ?>">
                  <img src="<?= $imgEsc ?>" alt="Imagem de Referência" class="img-thumb" />
                </a>

                <div id="<?= $overlayId ?>" class="img-overlay">
                  <a href="#" class="close-btn" title="Fechar">&times;</a>
                  <img src="<?= $imgEsc ?>" alt="Imagem Ampliada" />
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p>Nenhuma imagem enviada.</p>
          <?php endif; ?> 
        </dd>
      </dl>

      <a href="editar_agendamento.php?id=<?= urlencode($agendamento['id']) ?>" class="btn btn-primary">Editar Agendamento</a>
      <a href="listar_agendamentos.php" class="btn btn-secondary ms-2">Voltar à Lista</a>
    </div>
  </div>
</body>
</html>
