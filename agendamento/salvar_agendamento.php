<?php
header('Content-Type: application/json; charset=utf-8');

$dirUploads = __DIR__ . '/uploads/';
$dirData = __DIR__ . '/data/';
$fileJson = $dirData . 'agendamentos.json';

// Criar diretórios se não existirem
if (!is_dir($dirUploads)) mkdir($dirUploads, 0777, true);
if (!is_dir($dirData)) mkdir($dirData, 0777, true);

// Ler agendamentos existentes
$agendamentos = [];
if (file_exists($fileJson)) {
    $jsonContent = file_get_contents($fileJson);
    $agendamentos = json_decode($jsonContent, true);
    if (!is_array($agendamentos)) $agendamentos = [];
}

// Função para gerar ID único
function gerarID() {
    return uniqid('ag_', true);
}

// Função para converter hora (HH:MM) para minutos desde 00:00
function horaParaMinutos($horaStr) {
    if (!preg_match('/^\d{2}:\d{2}$/', $horaStr)) return false;
    list($h, $m) = explode(':', $horaStr);
    return ((int)$h) * 60 + ((int)$m);
}

// Receber dados do POST
$clienteNome = trim($_POST['clienteNome'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$dataAgendamento = trim($_POST['dataAgendamento'] ?? '');
$horaInicio = trim($_POST['horaInicio'] ?? '');
$horaFim = trim($_POST['horaFim'] ?? '');
$tatuador = trim($_POST['tatuador'] ?? '');
$parteCorpo = trim($_POST['parteCorpo'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');

// Validar campos obrigatórios
if (!$clienteNome || !$telefone || !$dataAgendamento || !$horaInicio || !$horaFim || !$tatuador || !$parteCorpo) {
    http_response_code(400);
    echo json_encode(['success' => false, 'msg' => 'Campos obrigatórios não preenchidos']);
    exit;
}

// Validar formato e lógica dos horários
$inicioMin = horaParaMinutos($horaInicio);
$fimMin = horaParaMinutos($horaFim);
if ($inicioMin === false || $fimMin === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'msg' => 'Formato de hora inválido']);
    exit;
}
if ($fimMin <= $inicioMin) {
    http_response_code(400);
    echo json_encode(['success' => false, 'msg' => 'Hora fim deve ser maior que hora início']);
    exit;
}

// Verificar conflito com agendamentos existentes (mesmo tatuador e data)
foreach ($agendamentos as $ag) {
    if (($ag['data'] ?? '') === $dataAgendamento && ($ag['tatuador'] ?? '') === $tatuador) {
        $agInicio = horaParaMinutos($ag['horaInicio'] ?? '');
        $agFim = horaParaMinutos($ag['horaFim'] ?? '');

        if ($agInicio === false || $agFim === false) {
            // Caso dados antigos não tenham horaFim, considerar conflito simplificado
            if (isset($ag['hora'])) {
                $agHora = horaParaMinutos($ag['hora']);
                if ($agHora !== false) {
                    // Considera 1h de duração padrão para dados antigos
                    $agInicio = $agHora;
                    $agFim = $agHora + 60;
                } else {
                    continue; // pula se sem dados
                }
            } else {
                continue;
            }
        }

        // Condição de conflito: 
        // novoInicio < agendamentoFim AND novoFim > agendamentoInicio
        if ($inicioMin < $agFim && $fimMin > $agInicio) {
            http_response_code(409);
            echo json_encode(['success' => false, 'msg' => "Conflito: o horário selecionado para o tatuador está ocupado."]);
            exit;
        }
    }
}

// Processar upload das imagens
$imagensSalvas = [];
if (!empty($_FILES['imagens']) && !empty($_FILES['imagens']['name'][0])) {
    foreach ($_FILES['imagens']['tmp_name'] as $key => $tmpName) {
        $nomeOriginal = $_FILES['imagens']['name'][$key];
        $ext = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
        $novoNome = gerarID() . '_' . time() . "_$key." . $ext;
        $destino = $dirUploads . $novoNome;

        if (move_uploaded_file($tmpName, $destino)) {
            $imagensSalvas[] = 'uploads/' . $novoNome;
        }
    }
}

// Montar novo agendamento
$novoAgendamento = [
    'id' => gerarID(),
    'nome' => $clienteNome,
    'telefone' => $telefone,
    'data' => $dataAgendamento,
    'horaInicio' => $horaInicio,
    'horaFim' => $horaFim,
    'tatuador' => $tatuador,
    'parte_corpo' => $parteCorpo,
    'descricao' => $descricao,
    'imagensReferencia' => $imagensSalvas,
    'dataCadastro' => date('Y-m-d H:i:s'),
];

// Adicionar e salvar
$agendamentos[] = $novoAgendamento;

if (file_put_contents($fileJson, json_encode($agendamentos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'msg' => 'Agendamento salvo com sucesso']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Erro ao salvar agendamento']);
}
