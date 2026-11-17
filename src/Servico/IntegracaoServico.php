<?php
declare(strict_types=1);

namespace Acolhe\Servico;

use Acolhe\Dominio\Permissoes;
use Acolhe\Dominio\RegraDeNegocioException;
use Acolhe\Dominio\SituacaoTarefa;
use Acolhe\Dominio\Trilha;
use DateTimeImmutable;

/**
 * Trilha, conteúdos e checklist de uma integração (RF03, RF04, RF05, RF06).
 */
final class IntegracaoServico
{
    public function __construct(private IntegracaoRepositorio $repo)
    {
    }

    /**
     * Monta a visão completa da trilha: etapas com conteúdos e tarefas,
     * situação de cada tarefa, etapas liberadas e progresso.
     * @return array<string,mixed>
     */
    public function montarTrilha(int $idIntegracao, DateTimeImmutable $hoje): array
    {
        $integracao = $this->repo->buscarIntegracao($idIntegracao)
            ?? throw new RegraDeNegocioException('Integração não encontrada.');

        $etapas = $this->repo->etapas((int) $integracao['id_trilha']);
        $conteudos = $this->repo->conteudos($idIntegracao);
        $tarefas = $this->repo->tarefas($idIntegracao);

        $concluidos = 0;
        $total = 0;
        $atrasadas = 0;
        foreach ($etapas as &$etapa) {
            $idEtapa = (int) $etapa['id_etapa'];
            $etapa['conteudos'] = array_values(array_filter($conteudos, static fn ($c) => (int) $c['id_etapa'] === $idEtapa));
            $etapa['tarefas'] = [];
            $itens = [];
            foreach ($etapa['conteudos'] as $c) {
                $itens[] = (bool) $c['lido'];
            }
            foreach ($tarefas as $t) {
                if ((int) $t['id_etapa'] !== $idEtapa) {
                    continue;
                }
                $t['situacao'] = SituacaoTarefa::situacao(
                    $t['status'] === SituacaoTarefa::CONCLUIDA,
                    new DateTimeImmutable((string) $t['data_prazo']),
                    $hoje,
                    (bool) $t['exige_validacao']
                );
                $t['dias_ate_prazo'] = SituacaoTarefa::diasAtePrazo(new DateTimeImmutable((string) $t['data_prazo']), $hoje);
                if ($t['situacao'] === SituacaoTarefa::ATRASADA) {
                    $atrasadas++;
                }
                $itens[] = $t['situacao'] === SituacaoTarefa::CONCLUIDA;
                $etapa['tarefas'][] = $t;
            }
            $etapa['concluida'] = Trilha::etapaConcluida($itens);
            $concluidos += count(array_filter($itens));
            $total += count($itens);
        }
        unset($etapa);

        $liberadas = Trilha::etapasLiberadas(array_column($etapas, 'concluida'), (bool) $integracao['liberacao_sequencial']);
        foreach ($etapas as $i => &$etapa) {
            $etapa['liberada'] = $liberadas[$i];
        }
        unset($etapa);

        return [
            'integracao' => $integracao,
            'etapas' => $etapas,
            'progresso' => Trilha::progresso($concluidos, $total),
            'atrasadas' => $atrasadas,
            'itens_concluidos' => $concluidos,
            'itens_total' => $total,
        ];
    }

    /** Marca um conteúdo da trilha como lido. Só vale para etapas liberadas. */
    public function marcarComoLido(array $usuario, int $idIntegracao, int $idConteudo, DateTimeImmutable $agora): void
    {
        $visao = $this->montarTrilha($idIntegracao, $agora);
        if ((int) $visao['integracao']['id_integrante'] !== (int) $usuario['id_usuario']) {
            throw new RegraDeNegocioException('Só o próprio integrante marca a leitura dos conteúdos.');
        }
        foreach ($visao['etapas'] as $etapa) {
            foreach ($etapa['conteudos'] as $c) {
                if ((int) $c['id_conteudo'] === $idConteudo) {
                    if (!$etapa['liberada']) {
                        throw new RegraDeNegocioException('Conclua a etapa anterior para liberar esta.');
                    }
                    if (!(bool) $c['lido']) {
                        $this->repo->registrarLeitura($idIntegracao, $idConteudo, $agora);
                    }
                    $this->fecharSeCompleta($idIntegracao, $agora);
                    return;
                }
            }
        }
        throw new RegraDeNegocioException('Este conteúdo não faz parte da sua trilha.');
    }

    /** Conclui (ou valida) uma tarefa do checklist respeitando quem pode fazê-lo. */
    public function concluirTarefa(array $usuario, int $idTarefaIntegracao, DateTimeImmutable $agora): void
    {
        $t = $this->repo->buscarTarefa($idTarefaIntegracao)
            ?? throw new RegraDeNegocioException('Tarefa não encontrada.');
        if ($t['status'] === SituacaoTarefa::CONCLUIDA) {
            throw new RegraDeNegocioException('Esta tarefa já foi concluída.');
        }
        $motivo = Permissoes::motivoParaNaoConcluir(
            (string) $usuario['perfil'],
            (int) $usuario['id_usuario'],
            (string) $t['responsavel_tipo'],
            (bool) $t['exige_validacao'],
            (int) $t['id_integrante'],
            $t['id_padrinho'] !== null ? (int) $t['id_padrinho'] : null
        );
        if ($motivo !== null) {
            throw new RegraDeNegocioException($motivo);
        }
        $comoValidacao = (bool) $t['exige_validacao'] && (int) $usuario['id_usuario'] !== (int) $t['id_integrante'];
        $this->repo->concluirTarefa($idTarefaIntegracao, (int) $usuario['id_usuario'], $comoValidacao, $agora);
        $this->fecharSeCompleta((int) $t['id_integracao'], $agora);
    }

    /**
     * Linhas do painel da coordenação: progresso, atrasadas e alerta de inatividade (RF05, RNFR-05.4).
     * @return array<int,array<string,mixed>>
     */
    public function painel(int $idOrganizacao, DateTimeImmutable $agora): array
    {
        $linhas = [];
        foreach ($this->repo->emAndamento($idOrganizacao) as $i) {
            $linhas[] = $this->linhaResumo($i, $agora);
        }
        usort($linhas, static fn ($a, $b) => [$b['alerta'], $b['atrasadas']] <=> [$a['alerta'], $a['atrasadas']]);
        return $linhas;
    }

    /** @return array<int,array<string,mixed>> */
    public function apadrinhados(int $idPadrinho, DateTimeImmutable $agora): array
    {
        return array_map(fn ($i) => $this->linhaResumo($i, $agora), $this->repo->apadrinhados($idPadrinho));
    }

    /** @param array<string,mixed> $integracao @return array<string,mixed> */
    private function linhaResumo(array $integracao, DateTimeImmutable $agora): array
    {
        $visao = $this->montarTrilha((int) $integracao['id_integracao'], $agora);
        $ultimo = $integracao['ultimo_acesso'] ? new DateTimeImmutable((string) $integracao['ultimo_acesso']) : null;
        $diasSemAcesso = $ultimo ? (int) $ultimo->diff($agora)->days : null;
        $semAcesso = $ultimo === null || $diasSemAcesso > 7;
        $pendentesValidacao = 0;
        foreach ($visao['etapas'] as $e) {
            foreach ($e['tarefas'] as $t) {
                if ($t['situacao'] !== SituacaoTarefa::CONCLUIDA && (bool) $t['exige_validacao']) {
                    $pendentesValidacao++;
                }
            }
        }
        return $integracao + [
            'progresso' => $visao['progresso'],
            'atrasadas' => $visao['atrasadas'],
            'dias_sem_acesso' => $diasSemAcesso,
            'sem_acesso' => $semAcesso,
            'pendentes_validacao' => $pendentesValidacao,
            'alerta' => $semAcesso || $visao['atrasadas'] > 0 || $integracao['id_padrinho'] === null,
        ];
    }

    private function fecharSeCompleta(int $idIntegracao, DateTimeImmutable $agora): void
    {
        $visao = $this->montarTrilha($idIntegracao, $agora);
        if ($visao['progresso'] === 100 && $visao['integracao']['situacao'] === 'EM_ANDAMENTO') {
            $this->repo->concluirIntegracao($idIntegracao, $agora);
        }
    }
}
