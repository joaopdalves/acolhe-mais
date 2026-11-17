<?php
declare(strict_types=1);

namespace Acolhe\Infra;

use PDO;

/** Cria a conexão PDO a partir das variáveis de ambiente (ou config/config.php). */
final class Conexao
{
    private static ?PDO $pdo = null;

    public static function obter(): PDO
    {
        if (self::$pdo === null) {
            $cfg = require __DIR__ . '/../../config/config.php';
            self::$pdo = new PDO($cfg['dsn'], $cfg['usuario'], $cfg['senha'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$pdo->exec("SET time_zone = '-03:00'");
        }
        return self::$pdo;
    }

    public static function definir(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }
}
