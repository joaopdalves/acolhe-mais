<?php $i = $v['integracao']; ?>
<section>
  <p><a href="index.php">← Voltar</a></p>
  <div class="destaque escuro">
    <h1><?= e($i['integrante']) ?></h1>
    <p><?= e($i['trilha']) ?> · padrinho: <?= e($i['padrinho'] ?? 'não designado') ?> · início em <?= dataBr($i['data_inicio']) ?></p>
    <div class="barra"><span style="width: <?= (int) $v['progresso'] ?>%"></span></div>
    <p class="pct"><?= (int) $v['progresso'] ?>% concluído · <?= (int) $v['atrasadas'] ?> tarefa(s) atrasada(s)</p>
  </div>
  <?php $podeAgir = true; require __DIR__ . '/_etapas.php'; ?>
</section>
