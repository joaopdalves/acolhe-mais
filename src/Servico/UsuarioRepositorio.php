<?php
declare(strict_types=1);

namespace Acolhe\Servico;

use DateTimeImmutable;

/** Contrato de acesso a dados (implementação PDO em src/Infra; fake em tests/Fakes). */
interface UsuarioRepositorio
{
    /** @return array<string,mixed>|null */
    public function buscarPorEmail(string $email): ?array;

    public function registrarTentativas(int $idUsuario, int $tentativas, ?DateTimeImmutable $bloqueadoAte): void;

    public function registrarAcesso(int $idUsuario, DateTimeImmutable $agora): void;
}
