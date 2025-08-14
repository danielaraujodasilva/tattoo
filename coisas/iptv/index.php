<?php
// Configurações
$m3uUrl = 'http://e.bn0710.xyz/p/279666360598/467544412182/m3u'; // URL do M3U remoto
$cacheM3U = __DIR__ . '/cache.m3u'; // caminho do cache local
$cacheTime = 24 * 3600; // 24 horas em segundos

// Função para atualizar cache usando cURL com timeout de 300 segundos
function updateCache($url, $localFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutos timeout
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); // Timeout conexão 60s
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; IPTV-CacheBot/1.0)');
    $content = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($content !== false && strlen($content) > 1000) { // verifica conteúdo mínimo razoável
        file_put_contents($localFile, $content);
        return true;
    } else {
        error_log("Falha ao baixar M3U: $err");
        return false;
    }
}

// Atualiza cache se necessário
if (!file_exists($cacheM3U) || (time() - filemtime($cacheM3U)) > $cacheTime) {
    $success = updateCache($m3uUrl, $cacheM3U);
    if (!$success && !file_exists($cacheM3U)) {
        die('Erro: não foi possível baixar o arquivo M3U e não existe cache local.');
    }
}

// Lê o conteúdo do cache
if (!file_exists($cacheM3U)) {
    die('Erro: arquivo cache.m3u não encontrado.');
}
$content = file_get_contents($cacheM3U);
if (!$content) {
    die('Erro: não foi possível ler o arquivo cache.m3u.');
}

// Força HTTPS nos links do M3U (atenção: funciona só se servidor aceitar HTTPS)
//$content = str_replace('http://', 'https://', $content);

// Processa o M3U para extrair canais e grupos
$lines = explode("\n", $content);

$channels = [];
$currentGroup = 'Sem Categoria';
$currentTitle = '';
$currentLogo = '';

foreach ($lines as $line) {
    $line = trim($line);
    if (strpos($line, '#EXTINF:') === 0) {
        preg_match('/tvg-logo="([^"]*)"/', $line, $logoMatch);
        preg_match('/group-title="([^"]*)"/', $line, $groupMatch);
        preg_match('/,(.*)$/', $line, $titleMatch);

        $currentLogo = $logoMatch[1] ?? '';
        $currentGroup = $groupMatch[1] ?? 'Sem Categoria';
        $currentTitle = $titleMatch[1] ?? 'Sem Nome';
    } elseif ($line !== '' && strpos($line, '#') !== 0) {
        $channels[$currentGroup][] = [
            'title' => $currentTitle,
            'logo' => $currentLogo,
            'url' => $line
        ];
    }
}

// Paginação e seleção por categoria via GET
$category = $_GET['category'] ?? null;
$page = max(1, intval($_GET['page'] ?? 1));
$itemsPerPage = 10;

if (!$category) {
    $categories = array_keys($channels);
}

function paginate($items, $page, $perPage) {
    $start = ($page - 1) * $perPage;
    return array_slice($items, $start, $perPage);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>IPTV Viewer com Cache e Chromecast</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #121212; color: #eee; padding: 20px; }
    a { color: #f44336; }
    a:hover { text-decoration: none; }
    .channel-logo { width: 60px; height: 40px; object-fit: contain; }
    .category-link { cursor: pointer; }
    .pagination a { color: #f44336; }
    .card { cursor: pointer; }
    .modal-content { background-color: #222; color: white; }
    #errorMessage {
      color: #f44336;
      margin-top: 10px;
      font-weight: 600;
      user-select: none;
      text-align: center;
    }
  </style>
</head>
<body>

<div class="container">
  <h1 class="mb-4">IPTV Viewer</h1>

  <?php if (!$category): ?>
    <h3>Categorias</h3>
    <div class="list-group">
      <?php foreach ($categories as $cat): ?>
        <a href="?category=<?= urlencode($cat) ?>" class="list-group-item list-group-item-action list-group-item-dark">
          <?= htmlspecialchars($cat) ?> (<?= count($channels[$cat]) ?> canais)
        </a>
      <?php endforeach; ?>
    </div>

  <?php else: 
    if (!isset($channels[$category])) {
      echo '<div class="alert alert-danger">Categoria não encontrada.</div>';
    } else {
      $totalChannels = count($channels[$category]);
      $pagedChannels = paginate($channels[$category], $page, $itemsPerPage);
      $totalPages = ceil($totalChannels / $itemsPerPage);
  ?>

    <a href="?" class="btn btn-outline-light mb-3">← Voltar às categorias</a>

    <h3>Categoria: <?= htmlspecialchars($category) ?></h3>
    <p><?= $totalChannels ?> canais encontrados</p>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
      <?php foreach ($pagedChannels as $channel): ?>
        <div class="col">
          <div class="card bg-dark text-white h-100 channel-card" 
               data-url="<?= htmlspecialchars($channel['url']) ?>"
               data-title="<?= htmlspecialchars($channel['title']) ?>">
            <?php if ($channel['logo']): ?>
              <img src="<?= htmlspecialchars($channel['logo']) ?>" alt="Logo" class="card-img-top channel-logo" />
            <?php else: ?>
              <div style="height:40px;background:#333;"></div>
            <?php endif; ?>
            <div class="card-body p-2">
              <h6 class="card-title"><?= htmlspecialchars($channel['title']) ?></h6>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav aria-label="Navegação de páginas" class="mt-4">
        <ul class="pagination justify-content-center">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
              <a class="page-link" href="?category=<?= urlencode($category) ?>&page=<?= $p ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>

  <?php } endif; ?>

</div>

<!-- Modal Player -->
<div class="modal fade" id="playerModal" tabindex="-1" aria-labelledby="playerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content p-3">

      <div class="modal-header">
        <h5 class="modal-title" id="playerModalLabel">Assistindo</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body">
        <video id="videoPlayer" controls playsinline style="width:100%; border-radius: 6px; background:black;"></video>
        <div id="errorMessage"> </div>
      </div>

      <div class="modal-footer">
        <button id="castBtn" class="btn btn-danger d-flex align-items-center gap-2" title="Enviar para Chromecast" style="display:none;">
          <i class="bi bi-cast"></i> Chromecast
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>

    </div>
  </div>
</div>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Google Cast SDK -->
<script src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>
<!-- HLS.js -->
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>

<script>
const playerModal = new bootstrap.Modal(document.getElementById('playerModal'));
const videoPlayer = document.getElementById('videoPlayer');
const statusMessage = document.getElementById('errorMessage'); // agora mensagem geral, não só erro
const castBtn = document.getElementById('castBtn');
const playerModalLabel = document.getElementById('playerModalLabel');

let castSession = null;
let hls = null;
// Abre o modal e toca o vídeo com logs detalhados
function openPlayerModal(url, title) {
  statusMessage.style.display = 'block';
  statusMessage.textContent = 'Carregando...';

  if (hls) {
    hls.destroy();
    hls = null;
  }
  videoPlayer.pause();
  videoPlayer.removeAttribute('src'); 
  videoPlayer.load();

  playerModalLabel.textContent = title;
  playerModal.show();

  castBtn.style.display = 'inline-flex';

  if (videoPlayer.canPlayType('application/vnd.apple.mpegurl')) {
    videoPlayer.src = url;
    videoPlayer.play().then(() => {
      statusMessage.textContent = 'Reprodução iniciada.';
    }).catch(() => {
      statusMessage.textContent = 'Erro ao tentar reproduzir o vídeo.';
    });
  }
  else if (Hls.isSupported()) {
    hls = new Hls();

    // Eventos para mostrar status e progresso
    hls.on(Hls.Events.MANIFEST_LOADING, () => {
      statusMessage.textContent = 'Carregando playlist...';
    });

    hls.on(Hls.Events.MANIFEST_PARSED, () => {
      statusMessage.textContent = 'Playlist carregada, iniciando reprodução...';
    });

    hls.on(Hls.Events.FRAG_LOADING, (event, data) => {
      statusMessage.textContent = `Carregando segmento ${data.frag.sn}...`;
    });

    hls.on(Hls.Events.FRAG_LOAD_PROGRESS, (event, data) => {
      let pct = Math.round(data.stats.loaded / data.stats.total * 100);
      statusMessage.textContent = `Carregando segmento ${data.frag.sn}: ${pct}%`;
    });

    hls.on(Hls.Events.FRAG_LOADED, (event, data) => {
      statusMessage.textContent = `Segmento ${data.frag.sn} carregado.`;
    });

    hls.on(Hls.Events.FRAG_BUFFERED, (event, data) => {
      statusMessage.textContent = `Segmento ${data.frag.sn} armazenado no buffer.`;
    });

    hls.on(Hls.Events.LEVEL_LOADED, (event, data) => {
      statusMessage.textContent = `Nível de qualidade ${data.level} carregado (${Math.round(data.details.totalduration)}s).`;
    });

    hls.on(Hls.Events.ERROR, (event, data) => {
      let msg = '';
      if (data.fatal) {
        switch(data.type) {
          case Hls.ErrorTypes.NETWORK_ERROR:
            msg = 'Erro fatal de rede ao carregar o vídeo.';
            break;
          case Hls.ErrorTypes.MEDIA_ERROR:
            msg = 'Erro fatal de mídia ao reproduzir o vídeo.';
            hls.recoverMediaError();
            break;
          default:
            msg = 'Erro fatal desconhecido ao carregar o vídeo.';
            hls.destroy();
            break;
        }
        statusMessage.textContent = msg;
      } else {
        statusMessage.textContent = `Erro não fatal: ${data.type} - ${data.details}`;
      }
    });

    hls.loadSource(url);
    hls.attachMedia(videoPlayer);
    hls.on(Hls.Events.MANIFEST_PARSED, function() {
      videoPlayer.play().then(() => {
        statusMessage.textContent = 'Reprodução iniciada.';
      }).catch(() => {
        statusMessage.textContent = 'Erro ao tentar reproduzir o vídeo.';
      });
    });
  }
  else {
    videoPlayer.src = url;
    videoPlayer.play().then(() => {
      statusMessage.textContent = 'Reprodução iniciada.';
    }).catch(() => {
      statusMessage.textContent = 'Erro ao tentar reproduzir o vídeo.';
    });
  }

  if (window.cast && window.cast.framework) {
    castSession = null;
  }
}

videoPlayer.addEventListener('playing', () => {
  statusMessage.style.display = 'none'; // Oculta mensagem quando o vídeo começa
});

videoPlayer.addEventListener('error', () => {
  let msg = 'Erro ao carregar o vídeo. Verifique sua conexão ou tente outro canal.';
  if (videoPlayer.error) {
    switch(videoPlayer.error.code){
      case 1: msg = 'Erro: Reprodução abortada pelo usuário.'; break;
      case 2: msg = 'Erro: Problema de rede ao carregar o vídeo.'; break;
      case 3: msg = 'Erro: Vídeo corrompido ou não suportado.'; break;
      case 4: msg = 'Erro: Fonte de vídeo não suportada.'; break;
    }
  }
  statusMessage.textContent = msg;
  statusMessage.style.display = 'block';
});

// Inicializa Chromecast se disponível
window['__onGCastApiAvailable'] = function(isAvailable) {
  if (isAvailable && window.cast && window.cast.framework) {
    initializeCastApi();
  }
};

function initializeCastApi() {
  if (!window.cast || !window.cast.framework) {
    console.warn('Cast API não disponível ainda');
    return;
  }
  const context = cast.framework.CastContext.getInstance();
  context.setOptions({
    receiverApplicationId: chrome.cast.media.DEFAULT_MEDIA_RECEIVER_APP_ID,
    autoJoinPolicy: chrome.cast.AutoJoinPolicy.ORIGIN_SCOPED
  });

  context.addEventListener(
    cast.framework.CastContextEventType.SESSION_STATE_CHANGED,
    function(event) {
      if (event.sessionState === cast.framework.SessionState.SESSION_STARTED ||
          event.sessionState === cast.framework.SessionState.SESSION_RESUMED) {
        castSession = context.getCurrentSession();
        if (videoPlayer.src && castSession) {
          loadMediaToCast(videoPlayer.src, playerModalLabel.textContent);
        }
      } else if (event.sessionState === cast.framework.SessionState.SESSION_ENDED) {
        castSession = null;
      }
    }
  );
}

function loadMediaToCast(url, title) {
  if (!castSession) return;

  const mediaInfo = new chrome.cast.media.MediaInfo(url, 'application/x-mpegurl');
  mediaInfo.metadata = new chrome.cast.media.GenericMediaMetadata();
  mediaInfo.metadata.title = title;

  const request = new chrome.cast.media.LoadRequest(mediaInfo);
  castSession.loadMedia(request).then(
    function() { console.log('Enviando para Chromecast: ' + title); },
    function(error) { console.error('Erro Chromecast: ', error); }
  );
}

// Botão Chromecast
castBtn.addEventListener('click', () => {
  if (castSession) {
    loadMediaToCast(videoPlayer.src, playerModalLabel.textContent);
  } else if (window.cast && window.cast.framework) {
    const context = cast.framework.CastContext.getInstance();
    context.requestSession().then(() => {
      castSession = context.getCurrentSession();
      loadMediaToCast(videoPlayer.src, playerModalLabel.textContent);
    }).catch(err => {
      alert('Erro ao iniciar sessão Chromecast: ' + err);
    });
  } else {
    alert('Chromecast não disponível no seu navegador.');
  }
});

// Ao clicar no card abre modal com vídeo
document.querySelectorAll('.channel-card').forEach(card => {
  card.addEventListener('click', () => {
    const url = card.getAttribute('data-url');
    const title = card.getAttribute('data-title');
    openPlayerModal(url, title);
  });
});
</script>

</body>
</html>
