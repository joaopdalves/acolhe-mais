<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo ?? 'Acolhe+') ?> · Acolhe+</title>
<link rel="stylesheet" href="assets/app.css">
<link rel="manifest" href="manifest.webmanifest">
<meta name="theme-color" content="#FF6B35">
</head>
<body>
<header class="topo">
  <a class="marca" href="index.php"><span class="logo" aria-hidden="true">♥</span> Acolhe<b>+</b></a>
  <?php if ($usuario): ?>
    <span class="quem"><?= e($usuario['nome']) ?> <small><?= e(ucfirst(mb_strtolower($usuario['perfil']))) ?></small></span>
    <a class="sair" href="index.php?r=sair">Sair</a>
  <?php endif; ?>
</header>
<main class="pagina">
  <?php if ($aviso): ?>
    <p class="aviso <?= e($aviso['tipo']) ?>" role="<?= $aviso['tipo'] === 'erro' ? 'alert' : 'status' ?>"><?= e($aviso['texto']) ?></p>
  <?php endif; ?>
  <?= $conteudo ?>
</main>
<footer class="rodape">Acolhe+ · ONG Esperança · <a href="#privacidade">Política de privacidade (LGPD)</a></footer>
</body>
</html>
