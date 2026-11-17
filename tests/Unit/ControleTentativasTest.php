<?php
declare(strict_types=1);

namespace Acolhe\Testes\Unit;

use Acolhe\Dominio\ControleTentativas;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ControleTentativasTest extends TestCase
{
    private DateTimeImmutable $agora;

    protected function setUp(): void
    {
        $this->agora = new DateTimeImmutable('2026-11-16 14:00:00');
    }

    public function testPrimeiraSenhaErradaDeixaQuatroTentativas(): void
    {
        $r = ControleTentativas::registrarFalha(0, $this->agora);
        self::assertSame(1, $r['tentativas']);
        self::assertSame(4, $r['restantes']);
        self::assertNull($r['bloqueadoAte']);
    }

    public function testQuintaSenhaErradaBloqueiaPorQuinzeMinutos(): void
    {
        $r = ControleTentativas::registrarFalha(4, $this->agora);
        self::assertSame(0, $r['restantes']);
        self::assertEquals(new DateTimeImmutable('2026-11-16 14:15:00'), $r['bloqueadoAte']);
        self::assertSame(0, $r['tentativas'], 'o contador recomeça depois do bloqueio');
    }

    public function testBloqueioValeSoAteOHorarioDefinido(): void
    {
        $ate = new DateTimeImmutable('2026-11-16 14:15:00');
        self::assertTrue(ControleTentativas::estaBloqueado($ate, $this->agora));
        self::assertFalse(ControleTentativas::estaBloqueado($ate, new DateTimeImmutable('2026-11-16 14:15:01')));
        self::assertFalse(ControleTentativas::estaBloqueado(null, $this->agora));
    }
}
