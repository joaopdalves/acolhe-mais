<?php
declare(strict_types=1);

namespace Acolhe\Testes\Unit;

use Acolhe\Dominio\RegraDeNegocioException;
use Acolhe\Servico\AutenticacaoServico;
use Acolhe\Testes\Fakes\UsuariosEmMemoria;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Fakes/RepositoriosEmMemoria.php';

final class AutenticacaoServicoTest extends TestCase
{
    private UsuariosEmMemoria $repo;
    private AutenticacaoServico $auth;
    private DateTimeImmutable $agora;

    protected function setUp(): void
    {
        $this->repo = new UsuariosEmMemoria([
            'joao@ong.org' => [
                'id_usuario' => 3, 'id_organizacao' => 1, 'nome' => 'João', 'email' => 'joao@ong.org',
                'senha_hash' => password_hash('acolhe123', PASSWORD_BCRYPT), 'perfil' => 'INTEGRANTE',
                'ativo' => 1, 'tentativas_login' => 0, 'bloqueado_ate' => null,
            ],
        ]);
        $this->auth = new AutenticacaoServico($this->repo);
        $this->agora = new DateTimeImmutable('2026-11-16 14:00:00');
    }

    public function testLoginComCredenciaisValidasRetornaOUsuarioSemOHash(): void
    {
        $u = $this->auth->entrar(' JOAO@ong.org ', 'acolhe123', $this->agora);
        self::assertSame(3, $u['id_usuario']);
        self::assertArrayNotHasKey('senha_hash', $u);
        self::assertSame('2026-11-16 14:00:00', $this->repo->porEmail['joao@ong.org']['ultimo_acesso']);
    }

    public function testSenhaErradaInformaQuantasTentativasRestam(): void
    {
        try {
            $this->auth->entrar('joao@ong.org', 'errada', $this->agora);
            self::fail('deveria recusar a senha');
        } catch (RegraDeNegocioException $e) {
            self::assertSame('E-mail ou senha incorretos. Restam 4 tentativas.', $e->getMessage());
        }
    }

    public function testCincoSenhasErradasBloqueiamInclusiveASenhaCerta(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->tentar('errada');
        }
        self::assertStringStartsWith('Acesso bloqueado após 5 tentativas', $this->tentar('errada'));
        self::assertStringStartsWith('Acesso bloqueado', $this->tentar('acolhe123'), 'bloqueado: nem a senha certa entra');

        $depois = $this->auth->entrar('joao@ong.org', 'acolhe123', $this->agora->modify('+16 minutes'));
        self::assertSame(3, $depois['id_usuario'], 'depois de 15 minutos o acesso volta');
    }

    public function testLoginBemSucedidoZeraAsTentativas(): void
    {
        $this->tentar('errada');
        $this->tentar('errada');
        $this->auth->entrar('joao@ong.org', 'acolhe123', $this->agora);
        self::assertSame(0, $this->repo->porEmail['joao@ong.org']['tentativas_login']);
    }

    public function testEmailInexistenteDaAMesmaMensagemGenerica(): void
    {
        $this->expectExceptionMessage('E-mail ou senha incorretos.');
        $this->auth->entrar('ninguem@ong.org', 'acolhe123', $this->agora);
    }

    public function testUsuarioInativoNaoEntra(): void
    {
        $this->repo->porEmail['joao@ong.org']['ativo'] = 0;
        $this->expectExceptionMessage('Este acesso está inativo.');
        $this->auth->entrar('joao@ong.org', 'acolhe123', $this->agora);
    }

    private function tentar(string $senha): string
    {
        try {
            $this->auth->entrar('joao@ong.org', $senha, $this->agora);
            return 'ok';
        } catch (RegraDeNegocioException $e) {
            return $e->getMessage();
        }
    }
}
