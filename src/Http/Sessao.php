<?php
declare(strict_types=1);

namespace Acolhe\Http;

/** Sessão do usuário, token CSRF e avisos de uma única exibição. */
final class Sessao
{
    public static function iniciar(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_secure' => !empty($_SERVER['HTTPS']),
            ]);
        }
        $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    }

    public static function entrar(array $usuario): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario'] = $usuario;
    }

    public static function sair(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function csrf(): string
    {
        return $_SESSION['csrf'];
    }

    public static function csrfValido(string $token): bool
    {
        return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }

    public static function aviso(string $texto, string $tipo): void
    {
        $_SESSION['aviso'] = ['texto' => $texto, 'tipo' => $tipo];
    }

    public static function pegarAviso(): ?array
    {
        $a = $_SESSION['aviso'] ?? null;
        unset($_SESSION['aviso']);
        return $a;
    }
}
