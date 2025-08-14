<?php
$url = 'http://e.bn0710.xyz/p/279666360598/467544412182/m3u';
$content = @file_get_contents($url);
if (!$content) {
  echo 'Falha ao carregar arquivo M3U';
} else {
  echo 'Arquivo M3U carregado, tamanho: ' . strlen($content) . ' bytes';
}
