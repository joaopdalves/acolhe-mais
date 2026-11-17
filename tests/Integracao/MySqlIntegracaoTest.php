<?php
declare(strict_types=1);

namespace Acolhe\Testes\Integracao;

use Acolhe\Infra\PdoIntegracaoRepositorio;
use Acolhe\Infra\PdoTermoRepositorio;
use Acolhe\Infra\PdoUsuarioRepositorio;
use Acolhe\Servico\AutenticacaoServico;
use Acolhe\Servico\IntegracaoServico;
use Acolhe\Servico\TermoServico;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Testes de integração com o MySQL real (banco criado por bin/instalar_banco.sh).
 * Cada teste roda dentro de uma transação desfeita no final, para não sujar os dados.
 * São pulados quando ACOLHE_DB_DSN não está definido.
 */
final class MySqlIntegracaoTest extends TestCase
{
    private PDO $pdo;
    private DateTimeImmutable $agora;

    protected function setUp(): void
    {
        $dsn = getenv('ACOLHE_DB_DSN');
        if (!$dsn) {
            self::markTestSkipped('Defina ACOLHE_DB_DSN para rodar os testes com o MySQL.');
        }
        $this->pdo = new PDO($dsn, getenv('ACOLHE_DB_USER') ?: 'acolhe', getenv('ACOLHE_DB_PASS') ?: 'acolhe', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->beginTransaction();
        $this->agora = new DateTimeImmutable('now');
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testLoginDoSeedComSenhaCriptografada(): void
    {
        $auth = new AutenticacaoServico(new PdoUsuarioRepositorio($this->pdo));
        $u = $auth->entrar('joao@ongesperanca.org', 'acolhe123', $this->agora);
        self::assertSame('INTEGRANTE', $u['perfil']);
        self::assertSame('ONG Esperança', $u['ong']);
    }

    public function testSenhaErradaGravaATentativaNoBanco(): void
    {
        $auth = new AutenticacaoServico(new PdoUsuarioRepositorio($this->pdo));
        try {
            $auth->entrar('joao@ongesperanca.org', 'errada', $this->agora);
        } catch (\Throwable) {
        }
        $n = $this->pdo->query("SELECT tentativas_login FROM usuario WHERE email = 'joao@ongesperanca.org'")->fetchColumn();
        self::assertSame(1, (int) $n);
    }

    public function testPauloTemOsQuatroTermosPendentesEOAceiteFicaGravado(): void
    {
        $servico = new TermoServico(new PdoTermoRepositorio($this->pdo));
        $paulo = ['id_usuario' => 5, 'id_organizacao' => 1];
        $pendentes = $servico->pendentes($paulo);
        self::assertCount(4, $pendentes);
        $servico->aceitarTodos($paulo, array_column($pendentes, 'id_termo'), '127.0.0.1', $this->agora);
        self::assertFalse($servico->temPendencia($paulo));
    }

    public function testTrilhaDoJoaoVemDoBancoComTarefaAtrasada(): void
    {
        $servico = new IntegracaoServico(new PdoIntegracaoRepositorio($this->pdo));
        $v = $servico->montarTrilha(1, $this->agora);
        self::assertCount(3, $v['etapas']);
        self::assertSame(1, $v['atrasadas'], 'o RG venceu ontem no seed');
        self::assertSame([true, true, false], array_column($v['etapas'], 'liberada'));
    }

    public function testPadrinhoValidaATarefaEOBancoGuardaQuemValidou(): void
    {
        $repo = new PdoIntegracaoRepositorio($this->pdo);
        $servico = new IntegracaoServico($repo);
        $id = (int) $this->pdo->query(
            "SELECT ti.id_tarefa_integracao FROM tarefa_integracao ti JOIN tarefa t USING (id_tarefa)
              WHERE ti.id_integracao = 1 AND t.exige_validacao = 1"
        )->fetchColumn();
        $servico->concluirTarefa(['id_usuario' => 2, 'perfil' => 'PADRINHO'], $id, $this->agora);
        $linha = $this->pdo->query("SELECT status, id_validado_por FROM tarefa_integracao WHERE id_tarefa_integracao = {$id}")->fetch();
        self::assertSame('CONCLUIDA', $linha['status']);
        self::assertSame(2, (int) $linha['id_validado_por']);
    }

    public function testPainelMostraOsTresIntegrantesComJailsonEmAlerta(): void
    {
        $servico = new IntegracaoServico(new PdoIntegracaoRepositorio($this->pdo));
        $linhas = array_column($servico->painel(1, $this->agora), null, 'integrante');
        self::assertCount(3, $linhas);
        self::assertTrue($linhas['Jailson Mendes']['sem_acesso']);
        self::assertNull($linhas['Jailson Mendes']['id_padrinho']);
    }
}
