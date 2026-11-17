<?php
$total = count($linhas);
$atrasadas = array_sum(array_column($linhas, 'atrasadas'));
$semAcesso = count(array_filter($linhas, static fn ($l) => $l['sem_acesso']));
$semPadrinho = count(array_filter($linhas, static fn ($l) => $l['id_padrinho'] === null));
$filtros = ['todos' => 'Todos', 'atraso' => 'Com atraso', 'inativo' => 'Sem acesso 7+ dias', 'sem_padrinho' => 'Sem padrinho'];
?>
<section>
  <div class="destaque escuro"><h1>Integrações em andamento</h1><p><?= e($usuario['ong'] ?? '') ?></p></div>
  <div class="kpis">
    <div class="kpi"><b><?= $total ?></b><span>em integração</span></div>
    <div class="kpi alerta"><b><?= $atrasadas ?></b><span>tarefas atrasadas</span></div>
    <div class="kpi"><b><?= $semAcesso ?></b><span>sem acesso há 7+ dias</span></div>
    <div class="kpi"><b><?= $semPadrinho ?></b><span>sem padrinho</span></div>
  </div>
  <nav class="filtros" aria-label="Filtros">
    <?php foreach ($filtros as $k => $rot): ?>
      <a class="chip <?= $filtro === $k ? 'ativo' : '' ?>" href="index.php?r=painel&amp;f=<?= e($k) ?>"><?= e($rot) ?></a>
    <?php endforeach; ?>
  </nav>
  <ul class="lista">
  <?php foreach ($filtradas as $l): ?>
    <li class="cartao <?= $l['alerta'] ? 'alerta' : '' ?>">
      <a class="linha" href="index.php?r=integrante&amp;id=<?= (int) $l['id_integracao'] ?>">
        <div>
          <h2><?= e($l['integrante']) ?></h2>
          <small>Padrinho: <?= e($l['padrinho'] ?? 'não designado') ?> ·
            <?= $l['atrasadas'] ? (int) $l['atrasadas'] . ' tarefa(s) atrasada(s)' : 'sem atrasos' ?> ·
            <?= $l['dias_sem_acesso'] === null ? 'nunca acessou' : ($l['dias_sem_acesso'] === 0 ? 'acessou hoje' : 'último acesso há ' . (int) $l['dias_sem_acesso'] . ' dia(s)') ?>
          </small>
        </div>
        <strong class="pct-num"><?= (int) $l['progresso'] ?>%</strong>
      </a>
      <div class="barra"><span style="width: <?= (int) $l['progresso'] ?>%"></span></div>
    </li>
  <?php endforeach; ?>
  <?php if ($filtradas === []): ?><li class="cartao">Ninguém nesta situação.</li><?php endif; ?>
  </ul>
</section>
