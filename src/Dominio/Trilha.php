<?php
declare(strict_types=1);

namespace Acolhe\Dominio;

/**
 * Regras da trilha de integração (RF03, RNFR-03.1):
 * liberação sequencial das etapas e percentual de progresso.
 */
final class Trilha
{
    /**
     * Uma etapa está concluída quando todos os seus itens (conteúdos e tarefas) foram concluídos.
     * Etapa sem itens conta como concluída.
     * @param bool[] $itensConcluidos
     */
    public static function etapaConcluida(array $itensConcluidos): bool
    {
        return !in_array(false, $itensConcluidos, true);
    }

    /**
     * Quais etapas estão liberadas. Com liberação sequencial, a etapa N só abre
     * quando a etapa N-1 foi concluída; sem ela, todas ficam abertas.
     * @param bool[] $etapasConcluidas na ordem da trilha
     * @return bool[]
     */
    public static function etapasLiberadas(array $etapasConcluidas, bool $sequencial): array
    {
        $liberadas = [];
        $anteriorConcluida = true;
        foreach (array_values($etapasConcluidas) as $concluida) {
            $liberadas[] = $sequencial ? $anteriorConcluida : true;
            $anteriorConcluida = $anteriorConcluida && $concluida;
        }
        return $liberadas;
    }

    /** Percentual inteiro de 0 a 100. Trilha vazia conta como 0%. */
    public static function progresso(int $itensConcluidos, int $totalItens): int
    {
        if ($totalItens <= 0) {
            return 0;
        }
        if ($itensConcluidos < 0 || $itensConcluidos > $totalItens) {
            throw new RegraDeNegocioException('Quantidade de itens concluídos inválida.');
        }
        return intdiv($itensConcluidos * 100, $totalItens);
    }
}
