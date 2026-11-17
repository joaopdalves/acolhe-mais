<section>
  <div class="destaque laranja">
    <h1>Bem-vindo(a) à <?= e($usuario['ong'] ?? 'ONG') ?>!</h1>
    <p>Antes de começar, leia e aceite os termos da ONG. Sua trilha é liberada logo depois.</p>
  </div>
  <form method="post" action="index.php?r=termos" class="cartao">
    <?= csrf($csrf) ?>
    <h2>Termos obrigatórios</h2>
    <?php foreach ($termos as $t): ?>
      <details class="termo">
        <summary><?= e($t['titulo']) ?> <small>versão <?= e($t['versao']) ?></small></summary>
        <p><?= e($t['texto']) ?></p>
      </details>
      <label class="check"><input type="checkbox" name="termos[]" value="<?= (int) $t['id_termo'] ?>"> Li e aceito: <?= e($t['titulo']) ?></label>
    <?php endforeach; ?>
    <button class="btn primario" type="submit">Aceitar e liberar trilha</button>
    <p class="nota">O aceite fica registrado com data, hora, endereço IP e versão de cada termo.</p>
  </form>
</section>
