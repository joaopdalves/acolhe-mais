<?php
declare(strict_types=1);

namespace Acolhe\Testes\Unit;

use Acolhe\Dominio\RegraDeNegocioException;
use Acolhe\Dominio\Trilha;
use PHPUnit\Framework\TestCase;

final class TrilhaTest extends TestCase
{
    public function testEtapaSoEstaConcluidaComTodosOsItensConcluidos(): void
    {
        self::assertTrue(Trilha::etapaConcluida([true, true]));
        self::assertFalse(Trilha::etapaConcluida([true, false]));
        self::assertTrue(Trilha::etapaConcluida([]), 'etapa vazia não trava a trilha');
    }

    public function testLiberacaoSequencialAbreApenasAProximaEtapa(): void
    {
        self::assertSame([true, true, false], Trilha::etapasLiberadas([true, false, false], true));
        self::assertSame([true, false, false], Trilha::etapasLiberadas([false, true, false], true),
            'a etapa 3 não abre se a etapa 1 não foi concluída');
    }

    public function testSemLiberacaoSequencialTodasAsEtapasFicamAbertas(): void
    {
        self::assertSame([true, true, true], Trilha::etapasLiberadas([false, false, false], false));
    }

    public function testProgressoEmPercentualInteiro(): void
    {
        self::assertSame(28, Trilha::progresso(2, 7));
        self::assertSame(100, Trilha::progresso(9, 9));
        self::assertSame(0, Trilha::progresso(0, 0));
    }

    public function testProgressoComQuantidadeImpossivelGeraErro(): void
    {
        $this->expectException(RegraDeNegocioException::class);
        Trilha::progresso(8, 7);
    }
}
