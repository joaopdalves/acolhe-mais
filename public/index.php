<?php
declare(strict_types=1);

// Ponto de entrada do Acolhe+ (todas as rotas passam por aqui: index.php?r=...)
date_default_timezone_set('America/Sao_Paulo');

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    // Sem Composer (ex.: execução pelo Docker): carrega as classes de src/ pelo padrão PSR-4.
    spl_autoload_register(static function (string $classe): void {
        if (str_starts_with($classe, 'Acolhe\\')) {
            $arquivo = __DIR__ . '/../src/' . str_replace('\\', '/', substr($classe, 7)) . '.php';
            if (is_file($arquivo)) {
                require $arquivo;
            }
        }
    });
    require __DIR__ . '/../src/Http/funcoes.php';
}

(new Acolhe\Http\App())->executar();
