<?php
declare(strict_types=1);

namespace Acolhe\Infra;

use Acolhe\Servico\IntegracaoRepositorio;
use DateTimeImmutable;
use PDO;

final class PdoIntegracaoRepositorio implements IntegracaoRepositorio
{
    private const SELECT_INTEGRACAO =
        'SELECT i.id_integracao, i.id_integrante, i.id_padrinho, i.id_trilha, i.data_inicio, i.situacao,
                t.nome AS trilha, t.liberacao_sequencial, u.nome AS integrante, u.funcao, u.ultimo_acesso,
                p.nome AS padrinho
           FROM integracao i
           JOIN trilha t  ON t.id_trilha = i.id_trilha
           JOIN usuario u ON u.id_usuario = i.id_integrante
      LEFT JOIN usuario p ON p.id_usuario = i.id_padrinho';

    public function __construct(private PDO $pdo)
    {
    }

    public function integracaoAtual(int $idIntegrante): ?array
    {
        $st = $this->pdo->prepare(self::SELECT_INTEGRACAO . ' WHERE i.id_integrante = ? ORDER BY i.id_integracao DESC LIMIT 1');
        $st->execute([$idIntegrante]);
        return $st->fetch() ?: null;
    }

    public function buscarIntegracao(int $idIntegracao): ?array
    {
        $st = $this->pdo->prepare(self::SELECT_INTEGRACAO . ' WHERE i.id_integracao = ?');
        $st->execute([$idIntegracao]);
        return $st->fetch() ?: null;
    }

    public function etapas(int $idTrilha): array
    {
        $st = $this->pdo->prepare('SELECT id_etapa, titulo, descricao, ordem, tempo_estimado_min FROM etapa WHERE id_trilha = ? ORDER BY ordem');
        $st->execute([$idTrilha]);
        return $st->fetchAll();
    }

    public function conteudos(int $idIntegracao): array
    {
        $st = $this->pdo->prepare(
            'SELECT ec.id_etapa, c.id_conteudo, c.titulo, c.corpo, c.versao, c.data_atualizacao,
                    cat.nome AS categoria, r.nome AS responsavel,
                    (lc.id_conteudo IS NOT NULL) AS lido
               FROM integracao i
               JOIN etapa e           ON e.id_trilha = i.id_trilha
               JOIN etapa_conteudo ec ON ec.id_etapa = e.id_etapa
               JOIN conteudo c        ON c.id_conteudo = ec.id_conteudo AND c.publicado = 1
               JOIN categoria cat     ON cat.id_categoria = c.id_categoria
               JOIN usuario r         ON r.id_usuario = c.id_responsavel
          LEFT JOIN leitura_conteudo lc ON lc.id_integracao = i.id_integracao AND lc.id_conteudo = c.id_conteudo
              WHERE i.id_integracao = ?
              ORDER BY e.ordem, ec.ordem'
        );
        $st->execute([$idIntegracao]);
        return $st->fetchAll();
    }

    public function tarefas(int $idIntegracao): array
    {
        $st = $this->pdo->prepare(
            'SELECT ti.id_tarefa_integracao, t.id_etapa, t.titulo, t.descricao, t.responsavel_tipo, t.exige_validacao,
                    ti.data_prazo, ti.status, ti.data_conclusao, uc.nome AS concluido_por, uv.nome AS validado_por
               FROM tarefa_integracao ti
               JOIN tarefa t ON t.id_tarefa = ti.id_tarefa
          LEFT JOIN usuario uc ON uc.id_usuario = ti.id_concluido_por
          LEFT JOIN usuario uv ON uv.id_usuario = ti.id_validado_por
              WHERE ti.id_integracao = ?
              ORDER BY ti.data_prazo, t.id_tarefa'
        );
        $st->execute([$idIntegracao]);
        return $st->fetchAll();
    }

    public function buscarTarefa(int $idTarefaIntegracao): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT ti.id_tarefa_integracao, ti.id_integracao, ti.status, t.titulo, t.responsavel_tipo, t.exige_validacao,
                    i.id_integrante, i.id_padrinho
               FROM tarefa_integracao ti
               JOIN tarefa t     ON t.id_tarefa = ti.id_tarefa
               JOIN integracao i ON i.id_integracao = ti.id_integracao
              WHERE ti.id_tarefa_integracao = ?'
        );
        $st->execute([$idTarefaIntegracao]);
        return $st->fetch() ?: null;
    }

    public function registrarLeitura(int $idIntegracao, int $idConteudo, DateTimeImmutable $agora): void
    {
        $this->pdo->prepare('INSERT IGNORE INTO leitura_conteudo (id_integracao, id_conteudo, data_leitura) VALUES (?, ?, ?)')
            ->execute([$idIntegracao, $idConteudo, $agora->format('Y-m-d H:i:s')]);
    }

    public function concluirTarefa(int $idTarefaIntegracao, int $idUsuario, bool $comoValidacao, DateTimeImmutable $agora): void
    {
        $campo = $comoValidacao ? 'id_validado_por' : 'id_concluido_por';
        $this->pdo->prepare("UPDATE tarefa_integracao SET status = 'CONCLUIDA', data_conclusao = ?, {$campo} = ? WHERE id_tarefa_integracao = ?")
            ->execute([$agora->format('Y-m-d H:i:s'), $idUsuario, $idTarefaIntegracao]);
    }

    public function concluirIntegracao(int $idIntegracao, DateTimeImmutable $agora): void
    {
        $this->pdo->prepare("UPDATE integracao SET situacao = 'CONCLUIDA', data_conclusao = ? WHERE id_integracao = ?")
            ->execute([$agora->format('Y-m-d'), $idIntegracao]);
    }

    public function emAndamento(int $idOrganizacao): array
    {
        $st = $this->pdo->prepare(self::SELECT_INTEGRACAO . " WHERE u.id_organizacao = ? AND u.ativo = 1 AND i.situacao = 'EM_ANDAMENTO' ORDER BY u.nome");
        $st->execute([$idOrganizacao]);
        return $st->fetchAll();
    }

    public function apadrinhados(int $idPadrinho): array
    {
        $st = $this->pdo->prepare(self::SELECT_INTEGRACAO . " WHERE i.id_padrinho = ? AND i.situacao = 'EM_ANDAMENTO' ORDER BY u.nome");
        $st->execute([$idPadrinho]);
        return $st->fetchAll();
    }
}
