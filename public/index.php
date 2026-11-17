<?php
declare(strict_types=1);

// Ponto de entrada do Acolhe+ (todas as rotas passam por aqui: index.php?r=...)
require __DIR__ . '/../vendor/autoload.php';

(new Acolhe\Http\App())->executar();
