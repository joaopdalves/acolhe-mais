<?php
declare(strict_types=1);

namespace Acolhe\Http;

use Acolhe\Dominio\Permissoes;
use Acolhe\Dominio\RegraDeNegocioException;
use Acolhe\Infra\Conexao;
use Acolhe\Infra\PdoIntegracaoRepositorio;
use Acolhe\Infra\PdoTermoRepositorio;
use Acolhe\Infra\PdoUsuarioRepositorio;
use Acolhe\Servico\AutenticacaoServico;
use Acolhe\Servico\IntegracaoServico;
use Acolhe\Servico\TermoServico;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Controlador frontal: recebe a rota (?r=), confere sessão e permissões,
 * chama o serviço e renderiza o template.
 */
final class App
{
    private AutenticacaoServico $auth;
    private TermoServico $termos;
    private IntegracaoServico $integracoes;
    private PdoIntegracaoRepositorio $repoIntegracao;
    private PdoTermoRepositorio $repoTermo;

    public function __construct()
    {
        $pdo = Conexao::obter();
        $this->repoIntegracao = new PdoIntegracaoRepositorio($pdo);
        $this->repoTermo = new PdoTermoRepositorio($pdo);
        $this->auth = new AutenticacaoServico(new PdoUsuarioRepositorio($pdo));
        $this->termos = new TermoServico($this->repoTermo);
        $this->integracoes = new IntegracaoServico($this->repoIntegracao);
    }

    public function executar(): void
    {
        Sessao::iniciar();
        $rota = (string) ($_GET['r'] ?? 'inicio');
        $post = $_SERVER['REQUEST_METHOD'] === 'POST';
        if ($post && !Sessao::csrfValido((string) ($_POST['csrf'] ?? ''))) {
            http_response_code(400);
            echo 'Sessão expirada. Volte e tente de novo.';
            return;
        }

        try {
            match ($rota) {
                'login' => $post ? $this->entrar() : View::render('login', ['titulo' => 'Entrar']),
                'sair' => $this->sair(),
                'termos' => $post ? $this->aceitarTermos() : $this->telaTermos(),
                'trilha' => $this->telaTrilha(),
                'lido' => $this->marcarLido(),
                'concluir' => $this->concluir(),
                'padrinho' => $this->telaPadrinho(),
                'painel' => $this->telaPainel(),
                'integrante' => $this->telaIntegrante(),
                default => $this->inicio(),
            };
        } catch (RegraDeNegocioException $e) {
            Sessao::aviso($e->getMessage(), 'erro');
            if ($rota === 'login') {
                View::render('login', ['titulo' => 'Entrar', 'email' => $_POST['email'] ?? '']);
                return;
            }
            $this->voltar();
        }
    }

    private function agora(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
    }

    private function inicio(): void
    {
        $u = Sessao::usuario();
        if ($u === null) {
            $this->redirecionar('login');
        }
        $this->redirecionar(match (true) {
            Permissoes::ehCoordenacao($u['perfil']) => 'painel',
            $u['perfil'] === Permissoes::PADRINHO => 'padrinho',
            default => 'trilha',
        });
    }

    private function entrar(): void
    {
        $usuario = $this->auth->entrar((string) ($_POST['email'] ?? ''), (string) ($_POST['senha'] ?? ''), $this->agora());
        Sessao::entrar($usuario);
        $this->inicio();
    }

    private function sair(): void
    {
        Sessao::sair();
        $this->redirecionar('login');
    }

    private function exigir(string ...$perfis): array
    {
        $u = Sessao::usuario();
        if ($u === null) {
            $this->redirecionar('login');
        }
        if ($perfis !== [] && !in_array($u['perfil'], $perfis, true)) {
            http_response_code(403);
            View::render('erro', ['titulo' => 'Sem acesso', 'mensagem' => 'Seu perfil não tem acesso a esta página.']);
            exit;
        }
        return $u;
    }

    private function telaTermos(): void
    {
        $u = $this->exigir();
        $pendentes = $this->termos->pendentes($u);
        if ($pendentes === []) {
            $this->inicio();
        }
        View::render('termos', ['titulo' => 'Termos', 'termos' => $pendentes, 'usuario' => $u]);
    }

    private function aceitarTermos(): void
    {
        $u = $this->exigir();
        $ids = array_map('intval', (array) ($_POST['termos'] ?? []));
        $n = $this->termos->aceitarTodos($u, $ids, (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), $this->agora());
        Sessao::aviso("Aceite de {$n} termos registrado. Sua trilha foi liberada.", 'ok');
        $this->redirecionar('trilha');
    }

    private function telaTrilha(): void
    {
        $u = $this->exigir(Permissoes::INTEGRANTE);
        if ($this->termos->temPendencia($u)) {
            $this->redirecionar('termos');
        }
        $integracao = $this->repoIntegracao->integracaoAtual((int) $u['id_usuario']);
        if ($integracao === null) {
            View::render('erro', ['titulo' => 'Trilha', 'mensagem' => 'Você ainda não tem uma trilha. Fale com a coordenação.', 'usuario' => $u]);
            return;
        }
        $visao = $this->integracoes->montarTrilha((int) $integracao['id_integracao'], $this->agora());
        View::render('trilha', ['titulo' => 'Minha trilha', 'usuario' => $u, 'v' => $visao]);
    }

    private function marcarLido(): void
    {
        $u = $this->exigir(Permissoes::INTEGRANTE);
        $this->integracoes->marcarComoLido($u, (int) $_POST['integracao'], (int) $_POST['conteudo'], $this->agora());
        Sessao::aviso('Leitura registrada.', 'ok');
        $this->voltar();
    }

    private function concluir(): void
    {
        $u = $this->exigir();
        $this->integracoes->concluirTarefa($u, (int) $_POST['tarefa'], $this->agora());
        Sessao::aviso($u['perfil'] === Permissoes::INTEGRANTE ? 'Tarefa concluída.' : 'Tarefa validada e concluída.', 'ok');
        $this->voltar();
    }

    private function telaPadrinho(): void
    {
        $u = $this->exigir(Permissoes::PADRINHO);
        $lista = $this->integracoes->apadrinhados((int) $u['id_usuario'], $this->agora());
        $visoes = [];
        foreach ($lista as $l) {
            $visoes[(int) $l['id_integracao']] = $this->integracoes->montarTrilha((int) $l['id_integracao'], $this->agora());
        }
        View::render('padrinho', ['titulo' => 'Apadrinhados', 'usuario' => $u, 'lista' => $lista, 'visoes' => $visoes]);
    }

    private function telaPainel(): void
    {
        $u = $this->exigir(Permissoes::COORDENADOR, Permissoes::ADMINISTRADOR);
        $linhas = $this->integracoes->painel((int) $u['id_organizacao'], $this->agora());
        $filtro = (string) ($_GET['f'] ?? 'todos');
        $filtradas = array_values(array_filter($linhas, static fn ($l) => match ($filtro) {
            'atraso' => $l['atrasadas'] > 0,
            'inativo' => $l['sem_acesso'],
            'sem_padrinho' => $l['id_padrinho'] === null,
            default => true,
        }));
        View::render('painel', ['titulo' => 'Painel', 'usuario' => $u, 'linhas' => $linhas, 'filtradas' => $filtradas, 'filtro' => $filtro]);
    }

    private function telaIntegrante(): void
    {
        $u = $this->exigir(Permissoes::COORDENADOR, Permissoes::ADMINISTRADOR, Permissoes::PADRINHO);
        $visao = $this->integracoes->montarTrilha((int) ($_GET['id'] ?? 0), $this->agora());
        if ($u['perfil'] === Permissoes::PADRINHO && (int) $visao['integracao']['id_padrinho'] !== (int) $u['id_usuario']) {
            throw new RegraDeNegocioException('Você só acompanha os seus apadrinhados.');
        }
        View::render('integrante', ['titulo' => $visao['integracao']['integrante'], 'usuario' => $u, 'v' => $visao]);
    }

    private function voltar(): never
    {
        $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        header('Location: ' . ($ref !== '' && str_contains($ref, 'index.php') ? $ref : 'index.php'));
        exit;
    }

    private function redirecionar(string $rota): never
    {
        header('Location: index.php?r=' . $rota);
        exit;
    }
}
