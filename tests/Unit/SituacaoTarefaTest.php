<?php
declare(strict_types=1);

namespace Acolhe\Testes\Unit;

use Acolhe\Dominio\RegraDeNegocioException;
use Acolhe\Dominio\SituacaoTarefa;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SituacaoTarefaTest extends TestCase
{
    public function testPrazoEContadoAPartirDaDataDeInicio(): void
    {
        $prazo = SituacaoTarefa::calcularPrazo(new DateTimeImmutable('2026-11-07 10:30'), 8);
        self::assertSame('2026-11-15', $prazo->format('Y-m-d'));
    }

    public function testPrazoNegativoNaoEPermitido(): void
    {
        $this->expectException(RegraDeNegocioException::class);
        SituacaoTarefa::calcularPrazo(new DateTimeImmutable('2026-11-07'), -1);
    }

    public function testTarefaVencidaENaoConcluidaFicaAtrasada(): void
    {
        $s = SituacaoTarefa::situacao(false, new DateTimeImmutable('2026-11-15'), new DateTimeImmutable('2026-11-16 09:00'), false);
        self::assertSame(SituacaoTarefa::ATRASADA, $s);
    }

    public function testNoDiaDoPrazoAindaNaoEstaAtrasada(): void
    {
        $s = SituacaoTarefa::situacao(false, new DateTimeImmutable('2026-11-16'), new DateTimeImmutable('2026-11-16 23:59'), false);
        self::assertSame(SituacaoTarefa::PENDENTE, $s);
    }

    public function testTarefaConcluidaNuncaFicaAtrasada(): void
    {
        $s = SituacaoTarefa::situacao(true, new DateTimeImmutable('2026-11-01'), new DateTimeImmutable('2026-11-16'), true);
        self::assertSame(SituacaoTarefa::CONCLUIDA, $s);
    }

    public function testTarefaComValidacaoNoPrazoAguardaValidacao(): void
    {
        $s = SituacaoTarefa::situacao(false, new DateTimeImmutable('2026-11-20'), new DateTimeImmutable('2026-11-16'), true);
        self::assertSame(SituacaoTarefa::AGUARDANDO_VALIDACAO, $s);
    }

    public function testDiasAtePrazoNegativoQuandoVenceu(): void
    {
        $hoje = new DateTimeImmutable('2026-11-16 18:00');
        self::assertSame(-1, SituacaoTarefa::diasAtePrazo(new DateTimeImmutable('2026-11-15'), $hoje));
        self::assertSame(0, SituacaoTarefa::diasAtePrazo(new DateTimeImmutable('2026-11-16'), $hoje));
        self::assertSame(2, SituacaoTarefa::diasAtePrazo(new DateTimeImmutable('2026-11-18'), $hoje));
    }

    public function testComparaSoAsDatasMesmoComFusosHorariosDiferentes(): void
    {
        // Prazo lido do banco (fuso padrão do PHP, UTC) e "agora" em São Paulo, de madrugada.
        $prazo = new \DateTimeImmutable('2026-11-18', new \DateTimeZone('UTC'));
        $hoje = new \DateTimeImmutable('2026-11-17 01:41', new \DateTimeZone('America/Sao_Paulo'));

        self::assertSame(1, SituacaoTarefa::diasAtePrazo($prazo, $hoje));
        self::assertSame(
            SituacaoTarefa::PENDENTE,
            SituacaoTarefa::situacao(false, new \DateTimeImmutable('2026-11-17', new \DateTimeZone('UTC')), $hoje, false)
        );
    }
}
