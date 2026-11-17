<?php
/** Partial: lista de etapas com conteúdos e tarefas. Espera $v (visão da trilha), $usuario, $csrf e $podeAgir (bool). */
$rotulos = [
    'CONCLUIDA' => 'Concluída',
    'ATRASADA' => 'Atrasada',
    'PENDENTE' => 'Pendente',
    'AGUARDANDO_VALIDACAO' => 'Aguarda validação',
];
$ehIntegrante = $usuario['perfil'] === 'INTEGRANTE';
?>
<ol class="etapas">
<?php foreach ($v['etapas'] as $i => $etapa): ?>
  <li class="etapa <?= $etapa['concluida'] ? 'feita' : ($etapa['liberada'] ? 'aberta' : 'bloqueada') ?>">
    <div class="etapa-cab">
      <span class="no" aria-hidden="true"><?= $etapa['concluida'] ? '✓' : ($etapa['liberada'] ? $i + 1 : '🔒') ?></span>
      <div>
        <h3><?= e($etapa['titulo']) ?></h3>
        <small><?= $etapa['concluida'] ? 'Etapa concluída' : ($etapa['liberada'] ? 'Etapa liberada' : 'Conclua a etapa anterior para liberar') ?>
          · cerca de <?= (int) $etapa['tempo_estimado_min'] ?> min</small>
      </div>
    </div>
    <?php if ($etapa['liberada'] || !$ehIntegrante): ?>
      <ul class="itens">
      <?php foreach ($etapa['conteudos'] as $c): ?>
        <li class="item conteudo <?= $c['lido'] ? 'ok' : '' ?>">
          <details>
            <summary><span class="tipo">Leitura</span> <?= e($c['titulo']) ?></summary>
            <p><?= e($c['corpo']) ?></p>
            <p class="meta">Versão oficial v<?= (int) $c['versao'] ?> · <?= e($c['categoria']) ?> · atualizado em <?= dataBr($c['data_atualizacao']) ?> por <?= e($c['responsavel']) ?></p>
          </details>
          <?php if ($c['lido']): ?>
            <span class="selo-ok">Lido</span>
          <?php elseif ($ehIntegrante && $podeAgir): ?>
            <form method="post" action="index.php?r=lido"><?= csrf($csrf) ?>
              <input type="hidden" name="integracao" value="<?= (int) $v['integracao']['id_integracao'] ?>">
              <input type="hidden" name="conteudo" value="<?= (int) $c['id_conteudo'] ?>">
              <button class="btn mini">Marcar como lido</button>
            </form>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
      <?php foreach ($etapa['tarefas'] as $t): ?>
        <li class="item tarefa s-<?= e(strtolower($t['situacao'])) ?>">
          <div>
            <span class="tipo">Tarefa</span> <strong><?= e($t['titulo']) ?></strong>
            <p class="meta">
              <?= e($rotulos[$t['situacao']]) ?> ·
              <?php if ($t['situacao'] === 'CONCLUIDA'): ?>
                em <?= dataBr($t['data_conclusao'], true) ?><?= $t['validado_por'] ? ' · validada por ' . e($t['validado_por']) : '' ?>
              <?php elseif ($t['dias_ate_prazo'] < 0): ?>
                venceu em <?= dataBr($t['data_prazo']) ?>
              <?php else: ?>
                prazo <?= dataBr($t['data_prazo']) ?> (<?= $t['dias_ate_prazo'] === 0 ? 'hoje' : 'em ' . $t['dias_ate_prazo'] . ' dia' . ($t['dias_ate_prazo'] > 1 ? 's' : '') ?>)
              <?php endif; ?>
              <?= $t['responsavel_tipo'] === 'COORDENADOR' ? ' · feita pela coordenação' : '' ?>
            </p>
          </div>
          <?php
          $pode = $t['situacao'] !== 'CONCLUIDA' && $podeAgir && (
              ($ehIntegrante && $t['responsavel_tipo'] === 'INTEGRANTE' && !$t['exige_validacao'])
              || ($usuario['perfil'] === 'PADRINHO' && $t['exige_validacao'])
              || (in_array($usuario['perfil'], ['COORDENADOR', 'ADMINISTRADOR'], true))
          );
          ?>
          <?php if ($pode): ?>
            <form method="post" action="index.php?r=concluir"><?= csrf($csrf) ?>
              <input type="hidden" name="tarefa" value="<?= (int) $t['id_tarefa_integracao'] ?>">
              <button class="btn mini <?= $ehIntegrante ? '' : 'roxo' ?>"><?= $ehIntegrante ? 'Concluir' : ($t['exige_validacao'] ? 'Validar' : 'Concluir') ?></button>
            </form>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </li>
<?php endforeach; ?>
</ol>
