<?php $i = $v['integracao']; ?>
<section>
  <div class="destaque verde">
    <small>Trilha ativa</small>
    <h1><?= e($i['trilha']) ?></h1>
    <p>Padrinho: <?= e($i['padrinho'] ?? 'ainda não designado') ?> · início em <?= dataBr($i['data_inicio']) ?></p>
    <div class="barra" role="progressbar" aria-valuenow="<?= (int) $v['progresso'] ?>" aria-valuemin="0" aria-valuemax="100">
      <span style="width: <?= (int) $v['progresso'] ?>%"></span>
    </div>
    <p class="pct"><?= (int) $v['progresso'] ?>% concluído · <?= (int) $v['itens_concluidos'] ?> de <?= (int) $v['itens_total'] ?> itens</p>
  </div>
  <?php if ($v['atrasadas'] > 0): ?>
    <p class="aviso erro">Você tem <?= (int) $v['atrasadas'] ?> tarefa<?= $v['atrasadas'] > 1 ? 's' : '' ?> atrasada<?= $v['atrasadas'] > 1 ? 's' : '' ?>. Veja abaixo, em vermelho.</p>
  <?php endif; ?>
  <?php if ($i['situacao'] === 'CONCLUIDA'): ?>
    <p class="aviso ok">Parabéns! Você concluiu a sua integração.</p>
  <?php endif; ?>
  <?php $podeAgir = true; require __DIR__ . '/_etapas.php'; ?>
</section>
