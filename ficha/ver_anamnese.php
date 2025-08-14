<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : -1;
$caminhoJSON = __DIR__ . '/data/anamneses.json';
$registros = [];
if (file_exists($caminhoJSON)) {
    $registros = json_decode(file_get_contents($caminhoJSON), true);
    if (!is_array($registros)) $registros = [];
}
$item = ($id >= 0 && isset($registros[$id])) ? $registros[$id] : null;

function h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function formatarValor($val) {
    if ($val === '' || $val === null) return '-';
    $floatVal = floatval(str_replace(',', '.', str_replace('.', '', $val)));
    return 'R$ ' . number_format($floatVal, 2, ',', '.');
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
            $vFloat = floatval(str_replace(',', '.', str_replace('.', '', $v)));
            $total += $vFloat;
        }
    }
    return $total;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<title>Ficha de Anamnese - Visualizar / Imprimir</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
  body {
    background: #0f1720;
    color: #f8fafc;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }
  .container {
    max-width: 900px;
    margin: 2rem auto 3rem;
    background: linear-gradient(135deg, #111827, #0b1220);
    padding: 2rem 2.5rem;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(50,50,50,0.8);
  }
  h1, h2, h3, h5 {
    color: #f1f5f9;
  }
  .section {
    background: rgba(255,255,255,0.07);
    border-radius: 10px;
    padding: 15px 25px;
    margin-bottom: 24px;
    box-shadow: inset 0 0 10px rgba(0,0,0,0.15);
  }
  dl.row {
    margin-bottom: 0;
  }
  dt.col-sm-4, dt.col-sm-3 {
    font-weight: 600;
    color: #cbd5e1;
  }
  dd.col-sm-8, dd.col-sm-9 {
    margin-bottom: 10px;
  }
  .assinatura-img {
    max-width: 320px;
    border: 2px solid #fff;
    border-radius: 8px;
    background: white;
    display: block;
    margin-top: 15px;
  }
  .alert-error {
    background: #f8d7da;
    color: #842029;
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 600;
    display: inline-block;
  }
  @media print {
    body {
      background: white !important;
      color: black !important;
      font-size: 10pt !important;
      line-height: 1.1 !important;
    }
    .container {
      max-width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
      box-shadow: none !important;
      background: white !important;
      border-radius: 0 !important;
    }
    .section {
      padding: 0 !important;
      margin-bottom: 0.5rem !important;
      background: transparent !important;
      box-shadow: none !important;
    }
    h1, h2, h3, h5 {
      margin-top: 0 !important;
      margin-bottom: 0.3rem !important;
      font-weight: 600 !important;
    }
    dl.row {
      margin-bottom: 0 !important;
    }
    dt.col-sm-3, dt.col-sm-4 {
      font-weight: 600 !important;
      color: black !important;
      float: left !important;
      width: 35% !important;
      padding-right: 0.3rem !important;
      margin-bottom: 0 !important;
    }
    dd.col-sm-9, dd.col-sm-8 {
      margin-left: 35% !important;
      margin-bottom: 0.2rem !important;
      padding-left: 0 !important;
    }
    ul {
      padding-left: 1.2rem !important;
      margin-bottom: 0.3rem !important;
    }
    ul li {
      margin-bottom: 0.1rem !important;
    }
    .assinatura-img {
      max-width: 250px !important;
      margin-top: 5px !important;
      border: none !important;
    }
    .btn-print {
      display: none !important;
    }
  }
  .btn-print {
    background: #2563eb;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 20px;
  }
  .btn-print:hover {
    background: #1e40af;
  }
  /* Compact layout - alguns campos lado a lado */
  .compact-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 2rem;
    margin-bottom: 0.5rem;
  }
  .compact-row > div {
    flex: 1 1 250px;
  }
</style>
</head>
<body>
  <div class="container">
    <?php if (!$item): ?>
      <div class="alert alert-danger">Ficha não encontrada. <a href="listar_anamneses.php">Voltar à lista</a></div>
    <?php else: ?>

      <button class="btn-print" onclick="window.print()">Imprimir esta ficha</button>

      <h1>Ficha de Anamnese</h1>

      <div class="section">
        <h3>Dados Pessoais</h3>
        <div class="compact-row">
          <div><strong>Nome Completo:</strong><br><?=h($item['nome'])?></div>
          <div><strong>CPF:</strong><br><?=h($item['cpf'])?></div>
          <div><strong>Telefone / WhatsApp:</strong><br><?=h($item['telefone'])?></div>
        </div>
        <div class="compact-row">
          <div><strong>Endereço:</strong><br>
            <?=h($item['rua'])?>, <?=h($item['numero'])?> - <?=h($item['bairro'])?><br>
            <?=h($item['cidade'])?> - <?=h($item['estado'])?><br>
            CEP: <?=h($item['cep'])?>
          </div>
          <div><strong>Data de Nascimento:</strong><br><?=h($item['data_nascimento'])?></div>
        </div>
      </div>

      <div class="section">
        <h3>Atendimento</h3>
        <dl class="row">
          <dt class="col-sm-4">Data do preenchimento</dt>
          <dd class="col-sm-8"><?=h($item['data_preenchimento'])?></dd>

          <dt class="col-sm-4">Unidade</dt>
          <dd class="col-sm-8"><?=h($item['unidade'])?></dd>

          <dt class="col-sm-4">Tatuador</dt>
          <dd class="col-sm-8"><?=h($item['tatuador'])?></dd>

          <dt class="col-sm-4">Valor Acordado</dt>
          <?php
            $valorNum = floatval(str_replace(',', '.', str_replace('.', '', $item['valor'])));
            $somaPag = somarPagamentos($item['pagamentos'] ?? []);
            $valorIgual = abs($valorNum - $somaPag) < 0.1;
          ?>
          <dd class="col-sm-8 <?= $valorIgual ? '' : 'alert-error' ?>"><?=formatarValor($item['valor'])?></dd>

          <dt class="col-sm-4">Formas de pagamento</dt>
          <dd class="col-sm-8">
            <?php if(!empty($item['pagamentos'])): ?>
              <ul style="padding-left: 1rem; margin-bottom:0;">
                <li>Débito: <?=formatarValor($item['pagamentos']['debito'] ?? '')?></li>
                <li>Crédito à Vista: <?=formatarValor($item['pagamentos']['credito_vista'] ?? '')?></li>
                <li>Crédito Parcelado: <?=formatarValor($item['pagamentos']['credito_parcelado_valor'] ?? '')?> em <?=h($item['pagamentos']['credito_parcelado_vezes'] ?? '')?>x</li>
                <li>Dinheiro: <?=formatarValor($item['pagamentos']['dinheiro'] ?? '')?></li>
                <li>Pix: <?=formatarValor($item['pagamentos']['pix'] ?? '')?></li>
              </ul>
            <?php else: ?>
              <span>-</span>
            <?php endif; ?>
          </dd>
        </dl>
      </div>

      <div class="section">
        <h3>Informações de Saúde</h3>
        <dl class="row">
          <dt class="col-sm-4">Medicamentos em uso</dt>
          <dd class="col-sm-8"><?=nl2br(h($item['medicamentos']))?></dd>

          <dt class="col-sm-4">Alergias</dt>
          <dd class="col-sm-8"><?=nl2br(h($item['alergias']))?></dd>

          <dt class="col-sm-4">Doenças / Condições</dt>
          <dd class="col-sm-8"><?=nl2br(h($item['saude']))?></dd>

          <dt class="col-sm-4">Alergia a anestésicos</dt>
          <dd class="col-sm-8"><?=h($item['alergia_anestesico'])?></dd>

          <dt class="col-sm-4">Usa anticoagulante</dt>
          <dd class="col-sm-8"><?=h($item['anticoagulante'])?></dd>

          <dt class="col-sm-4">Tatuagens anteriores na área</dt>
          <dd class="col-sm-8"><?=h($item['tatuagem_anterior'])?></dd>
        </dl>
      </div>

      <div class="section">
        <h3>Consentimentos</h3>
        <ul>
          <li>LGPD: <?=!empty($item['lgpd']) ? 'Aceito' : 'Não aceito'?></li>
          <li>Receber marketing: <?=!empty($item['marketing']) ? 'Sim' : 'Não'?></li>
        </ul>
      </div>

      <div class="section">
        <h3>Assinatura</h3>
        <?php if (!empty($item['assinatura_arquivo']) && file_exists(__DIR__ . '/data/assinaturas/' . $item['assinatura_arquivo'])): ?>
          <img src="data/assinaturas/<?=h($item['assinatura_arquivo'])?>" alt="Assinatura" class="assinatura-img" />
        <?php else: ?>
          <p><i>Sem assinatura registrada.</i></p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
