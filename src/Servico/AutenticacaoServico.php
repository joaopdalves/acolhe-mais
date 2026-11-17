<?php
declare(strict_types=1);

namespace Acolhe\Servico;

use Acolhe\Dominio\ControleTentativas;
use Acolhe\Dominio\RegraDeNegocioException;
use DateTimeImmutable;

/** Login por e-mail e senha com bloqueio após 5 tentativas (US01, RF01). */
final class AutenticacaoServico
{
    public function __construct(private UsuarioRepositorio $usuarios)
    {
    }

    /** @return array<string,mixed> usuário autenticado */
    public function entrar(string $email, string $senha, DateTimeImmutable $agora): array
    {
        $email = mb_strtolower(trim($email));
        $usuario = $this->usuarios->buscarPorEmail($email);
        if ($usuario === null) {
            throw new RegraDeNegocioException('E-mail ou senha incorretos.');
        }
        if (!(bool) $usuario['ativo']) {
            throw new RegraDeNegocioException('Este acesso está inativo. Fale com a coordenação da ONG.');
        }

        $bloqueadoAte = $usuario['bloqueado_ate'] ? new DateTimeImmutable((string) $usuario['bloqueado_ate']) : null;
        if (ControleTentativas::estaBloqueado($bloqueadoAte, $agora)) {
            throw new RegraDeNegocioException(
                'Acesso bloqueado após ' . ControleTentativas::MAXIMO . ' tentativas. Tente de novo às '
                . $bloqueadoAte->format('H:i') . '.'
            );
        }

        if (empty($usuario['senha_hash']) || !password_verify($senha, (string) $usuario['senha_hash'])) {
            $r = ControleTentativas::registrarFalha((int) $usuario['tentativas_login'], $agora);
            $this->usuarios->registrarTentativas((int) $usuario['id_usuario'], $r['tentativas'], $r['bloqueadoAte']);
            if ($r['bloqueadoAte'] !== null) {
                throw new RegraDeNegocioException(
                    'Acesso bloqueado após ' . ControleTentativas::MAXIMO . ' tentativas. Tente de novo às '
                    . $r['bloqueadoAte']->format('H:i') . '.'
                );
            }
            $plural = $r['restantes'] === 1 ? 'tentativa' : 'tentativas';
            throw new RegraDeNegocioException("E-mail ou senha incorretos. Restam {$r['restantes']} {$plural}.");
        }

        $this->usuarios->registrarTentativas((int) $usuario['id_usuario'], 0, null);
        $this->usuarios->registrarAcesso((int) $usuario['id_usuario'], $agora);
        unset($usuario['senha_hash']);
        return $usuario;
    }
}
