<?php
require_once(__DIR__ . '/config/config.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<?php include(INCLUDES_PATH . 'head.php'); ?>
<body>
  <?php include(INCLUDES_PATH . 'header.php'); ?>

  <main>
    <h2>Conteúdo principal</h2>
    <img src="<?= IMG_URL ?>exemplo.jpg" alt="Imagem de exemplo">
    <p>Este site está totalmente modularizado.</p>
  </main>

  <?php include(INCLUDES_PATH . 'footer.php'); ?>
</body>
</html>
