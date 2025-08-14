<?php
$dirUploads = __DIR__ . '/uploads/';
$dirData = __DIR__ . '/data/';
$fileJson = $dirData . 'agendamentos.json';

if (!is_dir($dirUploads)) mkdir($dirUploads, 0777, true);
if (!is_dir($dirData)) mkdir($dirData, 0777, true);

$agendamentos = [];
if (file_exists($fileJson)) {
    $jsonContent = file_get_contents($fileJson);
    $agendamentos = json_decode($jsonContent, true);
    if (!is_array($agendamentos)) $agendamentos = [];
}

// Receber dados do POST
$id = $_POST['id'] ?? '';
$clienteNome = trim($_POST['clienteNome'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$dataAgendamento = trim($_POST['dataAgendamento'] ?? '');
$horaInicio = trim($_POST['horaInicio'] ?? '');
$horaFim = trim($_POST['horaFim'] ?? '');
$tatuador = trim($_POST['tatuador'] ?? '');
$parteCorpo = trim($_POST['parteCorpo'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');

if (!$id || !$clienteNome || !$telefone || !$dataAgendamento || !$horaInicio || !$horaFim || !$tatuador || !$parteCorpo) {
    http_response_code(400);
    echo json_encode(['success' => false, 'msg' => 'Campos obrigatórios não preenchidos']);
    exit;
}

// Encontrar índice do agendamento existente
$index = null;
foreach ($agendamentos as $k => $a) {
    if (($a['id'] ?? '') === $id) {
        $index = $k;
        break;
    }
}

if ($index === null) {
    http_response_code(404);
    echo json_encode(['success' => false, 'msg' => 'Agendamento não encontrado']);
    exit;
}

// Pegar imagens antigas
$imagensExistentes = $agendamentos[$index]['imagensReferencia'] ?? [];
$imagensNovas = [];

// Processar upload das novas imagens
if (!empty($_FILES['imagens']) && !empty($_FILES['imagens']['name'][0])) {
    foreach ($_FILES['imagens']['tmp_name'] as $key => $tmpName) {
        $nomeOriginal = $_FILES['imagens']['name'][$key];
        $ext = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
        $novoNome = $id . '_' . time() . "_$key." . $ext;

        $destino = $dirUploads . $novoNome;
        if (move_uploaded_file($tmpName, $destino)) {
            $imagensNovas[] = 'uploads/' . $novoNome;
        }
    }
}

// Atualizar dados no array
$agendamentos[$index]['nome'] = $clienteNome;
$agendamentos[$index]['telefone'] = $telefone;
$agendamentos[$index]['data'] = $dataAgendamento;
$agendamentos[$index]['horaInicio'] = $horaInicio;
$agendamentos[$index]['horaFim'] = $horaFim;
$agendamentos[$index]['tatuador'] = $tatuador;
$agendamentos[$index]['parte_corpo'] = $parteCorpo;
$agendamentos[$index]['descricao'] = $descricao;
// Concatenar imagens antigas + novas
$agendamentos[$index]['imagensReferencia'] = array_merge($imagensExistentes, $imagensNovas);
// Atualizar data de alteração (opcional)
$agendamentos[$index]['dataAlteracao'] = date('Y-m-d H:i:s');

// Salvar JSON
if (file_put_contents($fileJson, json_encode($agendamentos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    // Redirecionar para editar_agendamento com mensagem de sucesso
    header("Location: editar_agendamento.php?id=" . urlencode($id) . "&sucesso=1");
    exit;
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Erro ao salvar agendamento']);
}
