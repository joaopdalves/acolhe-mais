<?php
declare(strict_types=1);

namespace Acolhe\Infra;

use Acolhe\Servico\TermoRepositorio;
use DateTimeImmutable;
use PDO;

final class PdoTermoRepositorio implements TermoRepositorio
{
    public function __construct(private PDO $pdo)
    {
    }

    public function pendentes(int $idUsuario, int $idOrganizacao): array
    {
        $st = $this->pdo->prepare(
            'SELECT t.id_termo, t.tipo, t.titulo, t.texto, t.versao
               FROM termo t
              WHERE t.id_organizacao = ? AND t.vigente = 1 AND t.obrigatorio = 1
                AND NOT EXISTS (SELECT 1 FROM aceite_termo a WHERE a.id_termo = t.id_termo AND a.id_usuario = ?)
              ORDER BY t.id_termo'
        );
        $st->execute([$idOrganizacao, $idUsuario]);
        return $st->fetchAll();
    }

    public function aceitos(int $idUsuario): array
    {
        $st = $this->pdo->prepare(
            'SELECT t.titulo, t.versao, a.data_hora, a.endereco_ip
               FROM aceite_termo a JOIN termo t ON t.id_termo = a.id_termo
              WHERE a.id_usuario = ? ORDER BY a.data_hora'
        );
        $st->execute([$idUsuario]);
        return $st->fetchAll();
    }

    public function registrarAceite(int $idTermo, int $idUsuario, string $ip, DateTimeImmutable $agora): void
    {
        $this->pdo->prepare('INSERT INTO aceite_termo (id_termo, id_usuario, data_hora, endereco_ip) VALUES (?, ?, ?, ?)')
            ->execute([$idTermo, $idUsuario, $agora->format('Y-m-d H:i:s'), $ip]);
    }
}
