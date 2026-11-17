<?php
declare(strict_types=1);

namespace Acolhe\Http;

/** Renderiza um template PHP dentro do layout, com escape de HTML. */
final class View
{
    /** @param array<string,mixed> $dados */
    public static function render(string $template, array $dados = []): void
    {
        extract($dados, EXTR_SKIP);
        $aviso = Sessao::pegarAviso();
        $csrf = Sessao::csrf();
        $usuario ??= Sessao::usuario();
        ob_start();
        require __DIR__ . '/../../templates/' . $template . '.php';
        $conteudo = ob_get_clean();
        require __DIR__ . '/../../templates/layout.php';
    }
}
