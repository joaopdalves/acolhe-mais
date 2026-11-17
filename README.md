# Acolhe+

Plataforma web de integração (onboarding) de novos voluntários e colaboradores em ONGs.
Projeto do Grupo 1 de Engenharia de Software III, Fatec Itapetininga (2026/2):
Gustavo Henrique Mota Lima, João Pedro Domingues Alves e Matheus Silva Luna.

- Protótipo navegável: https://joaopdalves.github.io/prototipacao/
- Este repositório: MVP funcional em PHP 8 + MySQL 8

## O que o MVP já faz

| História | Funcionalidade |
| --- | --- |
| US01 | Login com e-mail e senha (bcrypt), bloqueio de 15 minutos após 5 senhas erradas, perfis (integrante, padrinho, coordenador) |
| RF08 | Aceite obrigatório dos termos no primeiro acesso, registrado com data, hora, IP e versão |
| US06 | Trilha por etapas com liberação sequencial e barra de progresso |
| US05/US07 | Leitura dos conteúdos oficiais da etapa (versão, responsável e data de atualização) |
| US03 | Checklist com prazos calculados a partir do início, tarefas atrasadas destacadas |
| US08 | Padrinho vê os apadrinhados e valida as tarefas que exigem validação |
| US04 | Painel da coordenação com progresso, atrasos, alerta de 7+ dias sem acesso e filtros |
| US11 | Layout responsivo, pensado primeiro para o celular |

Ficam para as próximas sprints: cadastro de conteúdos e trilhas pela tela, busca e perguntas frequentes (US09),
lembretes por e-mail (US10), dúvidas ao padrinho e exportação de relatórios.

## Como rodar

Requisitos: PHP 8.2+ com `pdo_mysql` e `mbstring`, MySQL 8 e Composer.

```bash
composer install
bin/instalar_banco.sh root          # cria o banco acolhe_mais com dados de demonstração
php -S localhost:8000 -t public     # abre em http://localhost:8000
```

A conexão usa `ACOLHE_DB_DSN`, `ACOLHE_DB_USER` e `ACOLHE_DB_PASS` (padrão: usuário `acolhe`, senha `acolhe`).

Usuários de demonstração (senha `acolhe123`):

| E-mail | Perfil |
| --- | --- |
| gustavo@ongesperanca.org | Coordenador |
| matheus@ongesperanca.org | Padrinho |
| joao@ongesperanca.org | Integrante com tarefa atrasada |
| paulo@ongesperanca.org | Integrante no primeiro acesso (termos) |

## Testes

```bash
vendor/bin/phpunit --testsuite unidade                 # 46 testes, sem banco
ACOLHE_DB_DSN='mysql:host=127.0.0.1;dbname=acolhe_mais;charset=utf8mb4' \
  vendor/bin/phpunit --testsuite integracao            # 6 testes com o MySQL
```

O GitHub Actions (`.github/workflows/ci.yml`) roda a verificação de sintaxe e as duas suítes a cada push.

## Estrutura

```
public/        ponto de entrada (index.php), CSS e manifest
src/Http       controlador, sessão (CSRF) e renderização das telas
src/Servico    casos de uso: autenticação, termos, integração
src/Dominio    regras de negócio puras (senha, tentativas, prazos, trilha, permissões)
src/Infra      repositórios PDO para MySQL
templates/     telas em PHP
database/      01_schema.sql, 02_mvp.sql, 03_seed.sql
tests/         Unit (com repositórios em memória) e Integracao (MySQL)
```

## Versionamento

- `main`: versões estáveis, demonstradas na revisão de cada sprint
- `develop`: integração do trabalho em andamento
- `feature/USxx-descricao`: uma branch por história, que entra na `develop` por pull request revisado
- Mensagens de commit no padrão `feat:`, `fix:`, `test:`, `docs:`
