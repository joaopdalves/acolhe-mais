<?php
// Configuração do banco. Em produção, defina as variáveis de ambiente
// ACOLHE_DB_DSN, ACOLHE_DB_USER e ACOLHE_DB_PASS na hospedagem.
return [
    'dsn' => getenv('ACOLHE_DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=acolhe_mais;charset=utf8mb4',
    'usuario' => getenv('ACOLHE_DB_USER') ?: 'acolhe',
    'senha' => getenv('ACOLHE_DB_PASS') ?: 'acolhe',
];
