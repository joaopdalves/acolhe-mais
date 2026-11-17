<?php
declare(strict_types=1);

namespace Acolhe\Servico;

use DateTimeImmutable;

/** Contrato de acesso a dados (implementação PDO em src/Infra; fake em tests/Fakes). */
interface TermoRepositorio
{
    /** Termos vigentes e obrigatórios da ONG que o usuário ainda não aceitou. @return array<int,array<string,mixed>> */
    public function pendentes(int $idUsuario, int $idOrganizacao): array;

    /** @return array<int,array<string,mixed>> */
    public function aceitos(int $idUsuario): array;

    public function registrarAceite(int $idTermo, int $idUsuario, string $ip, DateTimeImmutable $agora): void;
}
