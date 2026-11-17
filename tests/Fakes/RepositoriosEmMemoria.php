<?php
declare(strict_types=1);

namespace Acolhe\Testes\Fakes;

use Acolhe\Servico\IntegracaoRepositorio;
use Acolhe\Servico\TermoRepositorio;
use Acolhe\Servico\UsuarioRepositorio;
use DateTimeImmutable;

/** Repositórios em memória: deixam os testes de unidade rápidos e sem banco. */
final class UsuariosEmMemoria implements UsuarioRepositorio
{
    /** @param array<string,array<string,mixed>> $porEmail */
    public function __construct(public array $porEmail = [])
    {
    }

    public function buscarPorEmail(string $email): ?array
    {
        return $this->porEmail[$email] ?? null;
    }

    public function registrarTentativas(int $idUsuario, int $tentativas, ?DateTimeImmutable $bloqueadoAte): void
    {
        foreach ($this->porEmail as &$u) {
            if ($u['id_usuario'] === $idUsuario) {
                $u['tentativas_login'] = $tentativas;
                $u['bloqueado_ate'] = $bloqueadoAte?->format('Y-m-d H:i:s');
            }
        }
    }

    public function registrarAcesso(int $idUsuario, DateTimeImmutable $agora): void
    {
        foreach ($this->porEmail as &$u) {
            if ($u['id_usuario'] === $idUsuario) {
                $u['ultimo_acesso'] = $agora->format('Y-m-d H:i:s');
            }
        }
    }
}

final class TermosEmMemoria implements TermoRepositorio
{
    /** @var array<int,array{0:int,1:int,2:string}> aceites [idTermo, idUsuario, ip] */
    public array $aceites = [];

    /** @param array<int,array<string,mixed>> $termos */
    public function __construct(public array $termos = [])
    {
    }

    public function pendentes(int $idUsuario, int $idOrganizacao): array
    {
        return array_values(array_filter($this->termos, function ($t) use ($idUsuario, $idOrganizacao) {
            foreach ($this->aceites as [$idTermo, $idU]) {
                if ($idTermo === $t['id_termo'] && $idU === $idUsuario) {
                    return false;
                }
            }
            return $t['id_organizacao'] === $idOrganizacao && $t['vigente'] && $t['obrigatorio'];
        }));
    }

    public function aceitos(int $idUsuario): array
    {
        return array_values(array_filter($this->aceites, static fn ($a) => $a[1] === $idUsuario));
    }

    public function registrarAceite(int $idTermo, int $idUsuario, string $ip, DateTimeImmutable $agora): void
    {
        $this->aceites[] = [$idTermo, $idUsuario, $ip];
    }
}

final class IntegracoesEmMemoria implements IntegracaoRepositorio
{
    /** @var array<int,array<string,mixed>> */ public array $integracoes = [];
    /** @var array<int,array<string,mixed>> */ public array $etapas = [];
    /** @var array<int,array<string,mixed>> */ public array $conteudos = [];
    /** @var array<int,array<string,mixed>> */ public array $tarefas = [];
    /** @var array<int,array{0:int,1:int}> */ public array $leituras = [];

    public function integracaoAtual(int $idIntegrante): ?array
    {
        foreach ($this->integracoes as $i) {
            if ($i['id_integrante'] === $idIntegrante) {
                return $i;
            }
        }
        return null;
    }

    public function buscarIntegracao(int $idIntegracao): ?array
    {
        return $this->integracoes[$idIntegracao] ?? null;
    }

    public function etapas(int $idTrilha): array
    {
        $e = array_values(array_filter($this->etapas, static fn ($x) => $x['id_trilha'] === $idTrilha));
        usort($e, static fn ($a, $b) => $a['ordem'] <=> $b['ordem']);
        return $e;
    }

    public function conteudos(int $idIntegracao): array
    {
        return array_map(fn ($c) => $c + ['lido' => in_array([$idIntegracao, $c['id_conteudo']], $this->leituras, true)], $this->conteudos);
    }

    public function tarefas(int $idIntegracao): array
    {
        return array_values(array_filter($this->tarefas, static fn ($t) => $t['id_integracao'] === $idIntegracao));
    }

    public function buscarTarefa(int $idTarefaIntegracao): ?array
    {
        $t = $this->tarefas[$idTarefaIntegracao] ?? null;
        if ($t === null) {
            return null;
        }
        $i = $this->integracoes[$t['id_integracao']];
        return $t + ['id_integrante' => $i['id_integrante'], 'id_padrinho' => $i['id_padrinho']];
    }

    public function registrarLeitura(int $idIntegracao, int $idConteudo, DateTimeImmutable $agora): void
    {
        $this->leituras[] = [$idIntegracao, $idConteudo];
    }

    public function concluirTarefa(int $idTarefaIntegracao, int $idUsuario, bool $comoValidacao, DateTimeImmutable $agora): void
    {
        $this->tarefas[$idTarefaIntegracao]['status'] = 'CONCLUIDA';
        $this->tarefas[$idTarefaIntegracao][$comoValidacao ? 'validado_por' : 'concluido_por'] = $idUsuario;
    }

    public function concluirIntegracao(int $idIntegracao, DateTimeImmutable $agora): void
    {
        $this->integracoes[$idIntegracao]['situacao'] = 'CONCLUIDA';
    }

    public function emAndamento(int $idOrganizacao): array
    {
        return array_values(array_filter($this->integracoes, static fn ($i) => $i['situacao'] === 'EM_ANDAMENTO'));
    }

    public function apadrinhados(int $idPadrinho): array
    {
        return array_values(array_filter($this->integracoes, static fn ($i) => $i['id_padrinho'] === $idPadrinho));
    }
}
