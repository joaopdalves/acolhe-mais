<?php
declare(strict_types=1);

namespace Acolhe\Dominio;

/**
 * Regra de senha do Acolhe+ (RNFR-01.1):
 * no mínimo 8 caracteres, com pelo menos uma letra e um número.
 */
final class PoliticaSenha
{
    public const TAMANHO_MINIMO = 8;

    /** @return string[] mensagens de erro (vazio = senha válida) */
    public static function validar(string $senha): array
    {
        $erros = [];
        if (mb_strlen($senha) < self::TAMANHO_MINIMO) {
            $erros[] = 'A senha precisa ter pelo menos ' . self::TAMANHO_MINIMO . ' caracteres.';
        }
        if (!preg_match('/\p{L}/u', $senha)) {
            $erros[] = 'A senha precisa ter pelo menos uma letra.';
        }
        if (!preg_match('/\d/', $senha)) {
            $erros[] = 'A senha precisa ter pelo menos um número.';
        }
        return $erros;
    }

    public static function ehValida(string $senha): bool
    {
        return self::validar($senha) === [];
    }

    public static function gerarHash(string $senha): string
    {
        $erros = self::validar($senha);
        if ($erros !== []) {
            throw new RegraDeNegocioException(implode(' ', $erros));
        }
        return password_hash($senha, PASSWORD_BCRYPT);
    }
}
