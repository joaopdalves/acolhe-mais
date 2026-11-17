<?php
declare(strict_types=1);

namespace Acolhe\Dominio;

use DateTimeImmutable;

/**
 * Bloqueio de login por tentativas (US01):
 * após 5 senhas erradas seguidas, o acesso fica bloqueado por 15 minutos.
 */
final class ControleTentativas
{
    public const MAXIMO = 5;
    public const MINUTOS_BLOQUEIO = 15;

    public static function estaBloqueado(?DateTimeImmutable $bloqueadoAte, DateTimeImmutable $agora): bool
    {
        return $bloqueadoAte !== null && $bloqueadoAte > $agora;
    }

    /**
     * Registra uma senha errada.
     * @return array{tentativas:int, bloqueadoAte:?DateTimeImmutable, restantes:int}
     */
    public static function registrarFalha(int $tentativasAnteriores, DateTimeImmutable $agora): array
    {
        $tentativas = $tentativasAnteriores + 1;
        if ($tentativas >= self::MAXIMO) {
            return [
                'tentativas' => 0,
                'bloqueadoAte' => $agora->modify('+' . self::MINUTOS_BLOQUEIO . ' minutes'),
                'restantes' => 0,
            ];
        }
        return ['tentativas' => $tentativas, 'bloqueadoAte' => null, 'restantes' => self::MAXIMO - $tentativas];
    }
}
