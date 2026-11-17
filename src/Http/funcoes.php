<?php
declare(strict_types=1);

/** Escapa texto para HTML. */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Data no formato brasileiro. */
function dataBr(?string $data, bool $hora = false): string
{
    if (!$data) {
        return '—';
    }
    return (new DateTimeImmutable($data))->format($hora ? 'd/m/Y \à\s H:i' : 'd/m/Y');
}

/** Campo oculto com o token CSRF. */
function csrf(string $token): string
{
    return '<input type="hidden" name="csrf" value="' . e($token) . '">';
}
