<?php
declare(strict_types=1);

namespace Acolhe\Servico;

use DateTimeImmutable;

/** Contrato de acesso a dados (implementação PDO em src/Infra; fake em tests/Fakes). */
interface IntegracaoRepositorio
{
    /** @return array<string,mixed>|null */
    public function integracaoAtual(int $idIntegrante): ?array;

    /** @return array<string,mixed>|null */
    public function buscarIntegracao(int $idIntegracao): ?array;

    /** @return array<int,array<string,mixed>> etapas da trilha em ordem */
    public function etapas(int $idTrilha): array;

    /** @return array<int,array<string,mixed>> conteúdos de cada etapa, com o campo "lido" */
    public function conteudos(int $idIntegracao): array;

    /** @return array<int,array<string,mixed>> tarefas geradas para a integração */
    public function tarefas(int $idIntegracao): array;

    /** @return array<string,mixed>|null tarefa da integração + dados da integração */
    public function buscarTarefa(int $idTarefaIntegracao): ?array;

    public function registrarLeitura(int $idIntegracao, int $idConteudo, DateTimeImmutable $agora): void;

    public function concluirTarefa(int $idTarefaIntegracao, int $idUsuario, bool $comoValidacao, DateTimeImmutable $agora): void;

    public function concluirIntegracao(int $idIntegracao, DateTimeImmutable $agora): void;

    /** @return array<int,array<string,mixed>> integrações em andamento da ONG (painel) */
    public function emAndamento(int $idOrganizacao): array;

    /** @return array<int,array<string,mixed>> integrações acompanhadas por um padrinho */
    public function apadrinhados(int $idPadrinho): array;
}
