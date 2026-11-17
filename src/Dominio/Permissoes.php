<?php
declare(strict_types=1);

namespace Acolhe\Dominio;

/**
 * Quem pode concluir cada tarefa do checklist (RF04, RF06).
 */
final class Permissoes
{
    public const ADMINISTRADOR = 'ADMINISTRADOR';
    public const COORDENADOR = 'COORDENADOR';
    public const PADRINHO = 'PADRINHO';
    public const INTEGRANTE = 'INTEGRANTE';

    public static function ehCoordenacao(string $perfil): bool
    {
        return in_array($perfil, [self::ADMINISTRADOR, self::COORDENADOR], true);
    }

    /**
     * @param string $responsavelTipo INTEGRANTE | PADRINHO | COORDENADOR (quem executa a tarefa)
     * @return string|null null = pode concluir; texto = motivo do bloqueio
     */
    public static function motivoParaNaoConcluir(
        string $perfilUsuario,
        int $idUsuario,
        string $responsavelTipo,
        bool $exigeValidacao,
        int $idIntegrante,
        ?int $idPadrinho
    ): ?string {
        $coordenacao = self::ehCoordenacao($perfilUsuario);
        $ehPadrinhoDaIntegracao = $idPadrinho !== null && $idUsuario === $idPadrinho;
        $ehOIntegrante = $idUsuario === $idIntegrante;

        if ($responsavelTipo === self::COORDENADOR) {
            return $coordenacao ? null : 'Esta tarefa é feita pela coordenação.';
        }
        if ($exigeValidacao || $responsavelTipo === self::PADRINHO) {
            return ($ehPadrinhoDaIntegracao || $coordenacao) ? null : 'Esta tarefa precisa da validação do seu padrinho.';
        }
        return ($ehOIntegrante || $coordenacao) ? null : 'Só o próprio integrante pode concluir esta tarefa.';
    }
}
