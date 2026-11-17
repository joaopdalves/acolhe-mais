<?php
declare(strict_types=1);

namespace Acolhe\Servico;

use Acolhe\Dominio\RegraDeNegocioException;
use DateTimeImmutable;

/** Aceite dos termos obrigatórios no primeiro acesso (RF08). */
final class TermoServico
{
    public function __construct(private TermoRepositorio $termos)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function pendentes(array $usuario): array
    {
        return $this->termos->pendentes((int) $usuario['id_usuario'], (int) $usuario['id_organizacao']);
    }

    public function temPendencia(array $usuario): bool
    {
        return $this->pendentes($usuario) !== [];
    }

    /**
     * Registra o aceite de todos os termos pendentes. A trilha só é liberada
     * quando todos os termos obrigatórios forem marcados (RNFR-08.3).
     * @param int[] $idsMarcados
     */
    public function aceitarTodos(array $usuario, array $idsMarcados, string $ip, DateTimeImmutable $agora): int
    {
        $pendentes = array_map(static fn (array $t): int => (int) $t['id_termo'], $this->pendentes($usuario));
        $marcados = array_map('intval', $idsMarcados);
        $faltando = array_diff($pendentes, $marcados);
        if ($faltando !== []) {
            $n = count($faltando);
            throw new RegraDeNegocioException("Marque todos os termos para continuar. Falta{$this->s($n, 'm')} {$n}.");
        }
        foreach ($pendentes as $idTermo) {
            $this->termos->registrarAceite($idTermo, (int) $usuario['id_usuario'], $ip, $agora);
        }
        return count($pendentes);
    }

    private function s(int $n, string $sufixo): string
    {
        return $n === 1 ? '' : $sufixo;
    }
}
