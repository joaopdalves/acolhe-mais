<?php
declare(strict_types=1);

namespace Acolhe\Testes\Unit;

use Acolhe\Dominio\RegraDeNegocioException;
use Acolhe\Servico\IntegracaoServico;
use Acolhe\Testes\Fakes\IntegracoesEmMemoria;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Fakes/RepositoriosEmMemoria.php';

final class IntegracaoServicoTest extends TestCase
{
    private IntegracoesEmMemoria $repo;
    private IntegracaoServico $servico;
    private DateTimeImmutable $hoje;
    private array $joao = ['id_usuario' => 3, 'perfil' => 'INTEGRANTE'];
    private array $matheus = ['id_usuario' => 2, 'perfil' => 'PADRINHO'];
    private array $gustavo = ['id_usuario' => 1, 'perfil' => 'COORDENADOR'];

    protected function setUp(): void
    {
        $this->hoje = new DateTimeImmutable('2026-11-16 10:00');
        $r = new IntegracoesEmMemoria();
        $r->integracoes[1] = [
            'id_integracao' => 1, 'id_integrante' => 3, 'id_padrinho' => 2, 'id_trilha' => 1, 'situacao' => 'EM_ANDAMENTO',
            'liberacao_sequencial' => 1, 'ultimo_acesso' => '2026-11-15 20:00:00', 'integrante' => 'João',
        ];
        $r->etapas = [
            ['id_etapa' => 1, 'id_trilha' => 1, 'titulo' => 'Conhecendo a ONG', 'ordem' => 1],
            ['id_etapa' => 2, 'id_trilha' => 1, 'titulo' => 'Regras e conduta', 'ordem' => 2],
            ['id_etapa' => 3, 'id_trilha' => 1, 'titulo' => 'Tarefas de chegada', 'ordem' => 3],
        ];
        $r->conteudos = [
            ['id_etapa' => 1, 'id_conteudo' => 1, 'titulo' => 'História'],
            ['id_etapa' => 2, 'id_conteudo' => 2, 'titulo' => 'Código de convivência'],
        ];
        $t = static fn (int $id, int $etapa, string $prazo, string $resp = 'INTEGRANTE', int $valida = 0, string $status = 'PENDENTE') => [
            'id_tarefa_integracao' => $id, 'id_integracao' => 1, 'id_etapa' => $etapa, 'titulo' => "Tarefa {$id}",
            'responsavel_tipo' => $resp, 'exige_validacao' => $valida, 'data_prazo' => $prazo, 'status' => $status,
        ];
        $r->tarefas = [
            10 => $t(10, 1, '2026-11-08', 'INTEGRANTE', 0, 'CONCLUIDA'),
            11 => $t(11, 3, '2026-11-15'),                  // atrasada
            12 => $t(12, 3, '2026-11-18'),                  // vence em 2 dias
            13 => $t(13, 3, '2026-11-21', 'INTEGRANTE', 1), // precisa do padrinho
            14 => $t(14, 3, '2026-11-17', 'COORDENADOR'),   // feita pela coordenação
        ];
        $r->leituras = [[1, 1]];
        $this->repo = $r;
        $this->servico = new IntegracaoServico($r);
    }

    public function testMontaATrilhaComProgressoEtapasLiberadasEAtrasos(): void
    {
        $v = $this->servico->montarTrilha(1, $this->hoje);
        self::assertSame(28, $v['progresso'], '2 de 7 itens: 1 leitura + 1 tarefa');
        self::assertSame(1, $v['atrasadas']);
        self::assertSame([true, false, false], array_column($v['etapas'], 'concluida'));
        self::assertSame([true, true, false], array_column($v['etapas'], 'liberada'));
        self::assertSame(['ATRASADA', 'PENDENTE', 'AGUARDANDO_VALIDACAO', 'PENDENTE'],
            array_column($v['etapas'][2]['tarefas'], 'situacao'));
    }

    public function testLerOConteudoDaEtapaDoisLiberaAEtapaTres(): void
    {
        $this->servico->marcarComoLido($this->joao, 1, 2, $this->hoje);
        $v = $this->servico->montarTrilha(1, $this->hoje);
        self::assertTrue($v['etapas'][1]['concluida']);
        self::assertTrue($v['etapas'][2]['liberada']);
    }

    public function testNaoMarcaLeituraDeEtapaBloqueada(): void
    {
        $this->repo->conteudos[] = ['id_etapa' => 3, 'id_conteudo' => 9, 'titulo' => 'Rotina'];
        $this->expectExceptionMessage('Conclua a etapa anterior para liberar esta.');
        $this->servico->marcarComoLido($this->joao, 1, 9, $this->hoje);
    }

    public function testIntegranteNaoConcluiTarefaQuePrecisaDoPadrinho(): void
    {
        $this->expectExceptionMessage('Esta tarefa precisa da validação do seu padrinho.');
        $this->servico->concluirTarefa($this->joao, 13, $this->hoje);
    }

    public function testPadrinhoValidaETarefaRegistraQuemValidou(): void
    {
        $this->servico->concluirTarefa($this->matheus, 13, $this->hoje);
        self::assertSame('CONCLUIDA', $this->repo->tarefas[13]['status']);
        self::assertSame(2, $this->repo->tarefas[13]['validado_por']);
    }

    public function testNaoConcluiDuasVezes(): void
    {
        $this->expectException(RegraDeNegocioException::class);
        $this->servico->concluirTarefa($this->joao, 10, $this->hoje);
    }

    public function testConcluirTudoEncerraAIntegracao(): void
    {
        $this->servico->marcarComoLido($this->joao, 1, 2, $this->hoje);
        $this->servico->concluirTarefa($this->joao, 11, $this->hoje);
        $this->servico->concluirTarefa($this->joao, 12, $this->hoje);
        $this->servico->concluirTarefa($this->matheus, 13, $this->hoje);
        self::assertSame('EM_ANDAMENTO', $this->repo->integracoes[1]['situacao']);
        $this->servico->concluirTarefa($this->gustavo, 14, $this->hoje);
        self::assertSame('CONCLUIDA', $this->repo->integracoes[1]['situacao']);
        self::assertSame(100, $this->servico->montarTrilha(1, $this->hoje)['progresso']);
    }

    public function testPainelDestacaQuemEstaSemAcessoOuSemPadrinho(): void
    {
        $this->repo->integracoes[2] = [
            'id_integracao' => 2, 'id_integrante' => 4, 'id_padrinho' => null, 'id_trilha' => 1, 'situacao' => 'EM_ANDAMENTO',
            'liberacao_sequencial' => 1, 'ultimo_acesso' => '2026-11-08 09:00:00', 'integrante' => 'Jailson',
        ];
        $linhas = $this->servico->painel(1, $this->hoje);
        $porNome = array_column($linhas, null, 'integrante');
        self::assertTrue($porNome['Jailson']['sem_acesso']);
        self::assertSame(8, $porNome['Jailson']['dias_sem_acesso']);
        self::assertFalse($porNome['João']['sem_acesso']);
        self::assertSame(1, $porNome['João']['pendentes_validacao']);
        self::assertTrue($porNome['Jailson']['alerta']);
    }
}
