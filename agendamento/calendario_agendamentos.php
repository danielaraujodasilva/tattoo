<?php
// calendario_agendamentos.php

$caminhoJSON = __DIR__ . '/data/agendamentos.json';

$agendamentos = [];
if (file_exists($caminhoJSON)) {
    $agendamentos = json_decode(file_get_contents($caminhoJSON), true);
    if (!is_array($agendamentos)) $agendamentos = [];
}

// Tatuadores para filtro e display
$tatuadores = [
    'daniel' => 'Daniel',
    'theo' => 'Theo',
    'meduri' => 'Meduri',
    'wesley' => 'Wesley',
    'will' => 'Will'
];

// Preparar eventos para o FullCalendar
$eventos = [];
foreach ($agendamentos as $a) {
    // ignorar se faltar dados mínimos
    if (empty($a['id']) || empty($a['nome']) || empty($a['data']) || empty($a['horaInicio']) || empty($a['horaFim'])) {
        continue;
    }

    $tatuadorKey = $a['tatuador'] ?? '';

    // Cria evento para FullCalendar
    $eventos[] = [
        'id' => $a['id'],
        'title' => $a['nome'] . ' (' . ($tatuadores[$tatuadorKey] ?? 'Sem tatuador') . ')',
        'start' => $a['data'] . 'T' . $a['horaInicio'],
        'end' => $a['data'] . 'T' . $a['horaFim'],
        'extendedProps' => [
            'tatuador' => $tatuadorKey,
            'telefone' => $a['telefone'] ?? '',
            'parte_corpo' => $a['parte_corpo'] ?? '',
            'descricao' => $a['descricao'] ?? '',
            'imagensReferencia' => $a['imagensReferencia'] ?? [],
        ],
    ];
}

// Função para escapar texto no HTML
function esc($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Calendário de Agendamentos - Estúdio</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />

<style>
  body {
    background: #0f1720;
    color: #f8fafc;
  }
  .fc-theme-bootstrap .fc-scrollgrid,
  .fc-theme-bootstrap .fc-scrollgrid-section > td,
  .fc-theme-bootstrap .fc-scrollgrid-section > tr {
    border-color: #334155;
  }
  .fc .fc-toolbar-title {
    color: #f8fafc;
  }
  .fc .fc-button {
    background-color: #1e293b;
    color: #f8fafc;
    border: 1px solid #334155;
  }
  .fc .fc-button:hover, .fc .fc-button:focus {
    background-color: #3b82f6;
    color: #fff;
  }
  .fc .fc-button-primary {
    background-color: #3b82f6;
    border-color: #2563eb;
    color: #fff;
  }
  .fc .fc-button-primary:hover, .fc .fc-button-primary:focus {
    background-color: #2563eb;
  }
  /* Modal agendamento */
  .modal-content {
    background: linear-gradient(180deg,#111827 0%, #0b1220 100%);
    color: #f8fafc;
    border-radius: 12px;
  }
  .img-thumb {
    max-width: 100px;
    max-height: 100px;
    object-fit: cover;
    margin: 5px;
    border-radius: 8px;
    border: 1px solid #334155;
    cursor: pointer;
    transition: transform 0.15s ease-in-out;
  }
  .img-thumb:hover {
    transform: scale(1.05);
  }
</style>

</head>
<body class="container py-4">

<h1 class="mb-4">Calendário de Agendamentos</h1>

<div class="mb-3">
  <label for="filtroTatuador" class="form-label">Filtrar por Tatuador:</label>
  <select id="filtroTatuador" class="form-select w-auto">
    <option value="">Todos</option>
    <?php foreach ($tatuadores as $key => $nome): ?>
      <option value="<?= esc($key) ?>"><?= esc($nome) ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div id="calendar"></div>

<!-- Modal agendamento -->
<div class="modal fade" id="modalAgendamento" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-4">
      <h3 id="modalNome"></h3>
      <p><strong>Telefone / WhatsApp:</strong> <span id="modalTelefone"></span></p>
      <p><strong>Data:</strong> <span id="modalData"></span></p>
      <p><strong>Horário:</strong> <span id="modalHorario"></span></p>
      <p><strong>Parte do Corpo:</strong> <span id="modalParteCorpo"></span></p>
      <p><strong>Descrição:</strong><br /><span id="modalDescricao" style="white-space: pre-wrap;"></span></p>
      <hr>
      <p><strong>Imagens de Referência:</strong></p>
      <div id="modalImagens" class="d-flex flex-wrap"></div>
      <hr>
      <button id="btnExcluir" type="button" class="btn btn-danger">Excluir Agendamento</button>
      <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">Fechar</button>
    </div>
  </div>
</div>

<!-- Modal imagem ampliada -->
<div class="modal fade" id="modalImagem" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="background: transparent; border: none; box-shadow: none;">
      <div class="modal-body p-0">
        <img id="imgModal" src="" alt="Imagem Ampliada" style="width: 100%; border-radius: 12px;" />
      </div>
      <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Fechar"></button>
    </div>
  </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
  const eventos = <?= json_encode($eventos, JSON_UNESCAPED_UNICODE) ?>;

  // Inicializa FullCalendar
  const calendarEl = document.getElementById('calendar');
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    themeSystem: 'bootstrap',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
    },
    events: eventos,
    eventClick: function(info) {
      const event = info.event;
      const props = event.extendedProps;

      // Preencher modal com dados do agendamento
      document.getElementById('modalNome').textContent = event.title;
      document.getElementById('modalTelefone').textContent = props.telefone || '-';
      document.getElementById('modalData').textContent = event.start.toLocaleDateString('pt-BR');
      document.getElementById('modalHorario').textContent = 
          event.start.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'}) + ' às ' +
          event.end.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
      document.getElementById('modalParteCorpo').textContent = props.parte_corpo || '-';
      document.getElementById('modalDescricao').textContent = props.descricao || '-';

      // Limpar imagens anteriores
      const divImagens = document.getElementById('modalImagens');
      divImagens.innerHTML = '';

      if (props.imagensReferencia && props.imagensReferencia.length > 0) {
        props.imagensReferencia.forEach((imgSrc, idx) => {
          const img = document.createElement('img');
          img.src = imgSrc;
          img.alt = `Imagem de Referência ${idx+1}`;
          img.className = 'img-thumb';
          img.style.cursor = 'pointer';
          img.addEventListener('click', () => {
            document.getElementById('imgModal').src = imgSrc;
            modalImagem.show();
          });
          divImagens.appendChild(img);
        });
      } else {
        divImagens.textContent = 'Nenhuma imagem enviada.';
      }

      // Botão excluir
      btnExcluir.onclick = () => {
        if (confirm('Excluir este agendamento? Esta ação não pode ser desfeita.')) {
          window.location.href = 'listar_agendamentos.php?excluir=' + encodeURIComponent(event.id);
        }
      };

      modalAgendamento.show();
    }
  });

  calendar.render();

  // Modal Bootstrap
  const modalAgendamentoEl = document.getElementById('modalAgendamento');
  const modalAgendamento = new bootstrap.Modal(modalAgendamentoEl);

  const modalImagemEl = document.getElementById('modalImagem');
  const modalImagem = new bootstrap.Modal(modalImagemEl);

  const btnExcluir = document.getElementById('btnExcluir');

  // Filtro tatuador
  document.getElementById('filtroTatuador').addEventListener('change', function() {
    const filtro = this.value;
    calendar.removeAllEvents();

    const eventosFiltrados = filtro ? 
      eventos.filter(ev => ev.extendedProps.tatuador === filtro) : 
      eventos;

    eventosFiltrados.forEach(ev => calendar.addEvent(ev));
  });

</script>

</body>
</html>
