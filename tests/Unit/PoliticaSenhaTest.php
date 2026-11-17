<?php
declare(strict_types=1);

namespace Acolhe\Testes\Unit;

use Acolhe\Dominio\PoliticaSenha;
use Acolhe\Dominio\RegraDeNegocioException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PoliticaSenhaTest extends TestCase
{
    #[DataProvider('senhasValidas')]
    public function testAceitaSenhaComOitoCaracteresLetraENumero(string $senha): void
    {
        self::assertTrue(PoliticaSenha::ehValida($senha));
    }

    public static function senhasValidas(): array
    {
        return [['acolhe123'], ['Voluntario2026'], ['ação1234']];
    }

    #[DataProvider('senhasInvalidas')]
    public function testRecusaSenhaFraca(string $senha, string $mensagemEsperada): void
    {
        self::assertContains($mensagemEsperada, PoliticaSenha::validar($senha));
    }

    public static function senhasInvalidas(): array
    {
        return [
            'curta' => ['abc12', 'A senha precisa ter pelo menos 8 caracteres.'],
            'sem número' => ['somenteletras', 'A senha precisa ter pelo menos um número.'],
            'sem letra' => ['12345678', 'A senha precisa ter pelo menos uma letra.'],
        ];
    }

    public function testGeraHashBcryptQueConfereComASenha(): void
    {
        $hash = PoliticaSenha::gerarHash('acolhe123');
        self::assertStringStartsWith('$2y$', $hash);
        self::assertTrue(password_verify('acolhe123', $hash));
        self::assertNotSame('acolhe123', $hash);
    }

    public function testNaoGeraHashDeSenhaInvalida(): void
    {
        $this->expectException(RegraDeNegocioException::class);
        PoliticaSenha::gerarHash('123');
    }
}
