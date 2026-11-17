<?php
declare(strict_types=1);

namespace Acolhe\Infra;

use Acolhe\Servico\UsuarioRepositorio;
use DateTimeImmutable;
use PDO;

final class PdoUsuarioRepositorio implements UsuarioRepositorio
{
    public function __construct(private PDO $pdo)
    {
    }

    public function buscarPorEmail(string $email): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT u.id_usuario, u.id_organizacao, u.nome, u.email, u.senha_hash, u.perfil, u.funcao,
                    u.ativo, u.tentativas_login, u.bloqueado_ate, o.nome AS ong
               FROM usuario u JOIN organizacao o ON o.id_organizacao = u.id_organizacao
              WHERE u.email = ?'
        );
        $st->execute([$email]);
        return $st->fetch() ?: null;
    }

    public function registrarTentativas(int $idUsuario, int $tentativas, ?DateTimeImmutable $bloqueadoAte): void
    {
        $this->pdo->prepare('UPDATE usuario SET tentativas_login = ?, bloqueado_ate = ? WHERE id_usuario = ?')
            ->execute([$tentativas, $bloqueadoAte?->format('Y-m-d H:i:s'), $idUsuario]);
    }

    public function registrarAcesso(int $idUsuario, DateTimeImmutable $agora): void
    {
        $this->pdo->prepare('UPDATE usuario SET ultimo_acesso = ? WHERE id_usuario = ?')
            ->execute([$agora->format('Y-m-d H:i:s'), $idUsuario]);
    }
}
