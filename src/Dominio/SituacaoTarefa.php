<?php
declare(strict_types=1);

namespace Acolhe\Dominio;

use DateTimeImmutable;

/**
 * Prazos e situação das tarefas do checklist (RF04, RNFR-04.2).
 */
final class SituacaoTarefa
{
    public const PENDENTE = 'PENDENTE';
    public const ATRASADA = 'ATRASADA';
    public const AGUARDANDO_VALIDACAO = 'AGUARDANDO_VALIDACAO';
    public const CONCLUIDA = 'CONCLUIDA';

    /** Prazo = data de início do integrante + prazo em dias da tarefa. */
    public static function calcularPrazo(DateTimeImmutable $dataInicio, int $prazoDias): DateTimeImmutable
    {
        if ($prazoDias < 0) {
            throw new RegraDeNegocioException('O prazo da tarefa não pode ser negativo.');
        }
        return $dataInicio->setTime(0, 0)->modify("+{$prazoDias} days");
    }

    /**
     * Situação exibida ao usuário. Tarefa vencida e não concluída fica "atrasada";
     * a que exige validação e ainda não foi validada aparece "aguardando validação".
     */
    public static function situacao(
        bool $concluida,
        DateTimeImmutable $dataPrazo,
        DateTimeImmutable $hoje,
        bool $exigeValidacao
    ): string {
        if ($concluida) {
            return self::CONCLUIDA;
        }
        if (self::diasAtePrazo($dataPrazo, $hoje) < 0) {
            return self::ATRASADA;
        }
        return $exigeValidacao ? self::AGUARDANDO_VALIDACAO : self::PENDENTE;
    }

    /** Dias até o prazo (negativo = já venceu). */
    public static function diasAtePrazo(DateTimeImmutable $dataPrazo, DateTimeImmutable $hoje): int
    {
        // Compara só o dia do calendário de cada data, ignorando hora e fuso,
        // para que um prazo lido do banco não "vença" por diferença de fuso.
        $diff = self::dia($hoje)->diff(self::dia($dataPrazo));
        return $diff->invert ? -$diff->days : $diff->days;
    }

    private static function dia(DateTimeImmutable $data): DateTimeImmutable
    {
        return new DateTimeImmutable($data->format('Y-m-d'), new \DateTimeZone('UTC'));
    }
}
