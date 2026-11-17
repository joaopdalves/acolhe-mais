<section>
  <div class="destaque roxo">
    <h1>Seus apadrinhados</h1>
    <p>Você acompanha <?= count($lista) ?> pessoa<?= count($lista) === 1 ? '' : 's' ?>. Valide as tarefas que dependem de você.</p>
  </div>
  <?php if ($lista === []): ?><p class="cartao">Ninguém em integração com você agora.</p><?php endif; ?>
  <?php foreach ($lista as $l): $v = $visoes[(int) $l['id_integracao']]; ?>
    <article class="cartao">
      <header class="linha">
        <div><h2><?= e($l['integrante']) ?></h2><small><?= e($l['funcao']) ?> · início em <?= dataBr($l['data_inicio']) ?></small></div>
        <strong class="pct-num"><?= (int) $l['progresso'] ?>%</strong>
      </header>
      <div class="barra"><span style="width: <?= (int) $l['progresso'] ?>%"></span></div>
      <?php $podeAgir = true; require __DIR__ . '/_etapas.php'; ?>
    </article>
  <?php endforeach; ?>
</section>
