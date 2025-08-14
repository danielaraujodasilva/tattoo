<?php
date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: text/html; charset=utf-8');

// Pastas
$base = __DIR__ . '/data';
$assin_dir = $base . '/assinaturas';
if (!is_dir($base)) mkdir($base, 0777, true);
if (!is_dir($assin_dir)) mkdir($assin_dir, 0777, true);

// JSON existente
$json_file = $base . '/anamneses.json';
$records = [];
if (file_exists($json_file)) {
    $records = json_decode(file_get_contents($json_file), true);
    if (!is_array($records)) $records = [];
}

// Assinatura
$assinaturaBase64 = $_POST['assinaturaImagem'] ?? '';
$assinaturaFileName = '';
if (!empty($assinaturaBase64)) {
    $assinaturaBase64 = preg_replace('#^data:image/\w+;base64,#i', '', $assinaturaBase64);
    $assinaturaBase64 = str_replace(' ', '+', $assinaturaBase64);
    $data = base64_decode($assinaturaBase64);
    if ($data !== false) {
        $assinaturaFileName = 'assinatura_' . time() . '_' . bin2hex(random_bytes(4)) . '.png';
        file_put_contents($assin_dir . '/' . $assinaturaFileName, $data);
    }
}

// Sanitiza e organiza os dados
$novo = [
    'data_preenchimento' => $_POST['data_preenchimento'] ?? date('d/m/Y H:i:s'),
    'clienteBusca' => $_POST['clienteBusca'] ?? '',
    'nome' => $_POST['nome'] ?? '',
    'cpf' => $_POST['cpf'] ?? '',
    'telefone' => $_POST['telefone'] ?? '',

    // Endereço com prefixo correto
    'rua' => $_POST['endereco_rua'] ?? '',
    'numero' => $_POST['endereco_numero'] ?? '',
    'bairro' => $_POST['endereco_bairro'] ?? '',
    'cidade' => $_POST['endereco_cidade'] ?? '',
    'estado' => $_POST['endereco_uf'] ?? '',
    'cep' => $_POST['endereco_cep'] ?? '',

    'data_nascimento' => $_POST['data_nascimento'] ?? '',
    'unidade' => $_POST['unidade'] ?? '',
    'tatuador' => $_POST['tatuador'] ?? '',
    'valor' => $_POST['valor'] ?? '',

    // Formas de pagamento
    'pagamentos' => [
        'debito' => $_POST['pag_debito'] ?? '',
        'credito_vista' => $_POST['pag_credito_vista'] ?? '',
        'credito_parcelado_valor' => $_POST['pag_credito_parcelado_valor'] ?? '',
        'credito_parcelado_vezes' => $_POST['pag_credito_parcelado_vezes'] ?? '',
        'dinheiro' => $_POST['pag_dinheiro'] ?? '',
        'pix' => $_POST['pag_pix'] ?? ''
    ],

    'medicamentos' => $_POST['medicamentos'] ?? '',
    'alergias' => $_POST['alergias'] ?? '',
    'saude' => $_POST['saude'] ?? '',
    'alergia_anestesico' => $_POST['alergia_anestesico'] ?? '',
    'anticoagulante' => $_POST['anticoagulante'] ?? '',
    'tatuagem_anterior' => $_POST['tatuagem_anterior'] ?? '',
    'lgpd' => isset($_POST['lgpd']) ? true : false,
    'marketing' => isset($_POST['marketing']) ? true : false,
    'assinatura_arquivo' => $assinaturaFileName
];

$records[] = $novo;
file_put_contents($json_file, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8" />
<title>Ficha salva</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="container py-5">
  <div class="alert alert-success">Ficha salva com sucesso!</div>
  <a href="index.php" class="btn btn-primary">Nova ficha</a>
  <a href="listar_anamneses.php" class="btn btn-secondary">Listar fichas</a>
</body>
</html>
