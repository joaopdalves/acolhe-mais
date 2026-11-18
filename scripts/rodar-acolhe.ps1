# =============================================================
# Acolhe+ | Baixa, instala e roda o sistema em http://localhost:8000
# Windows 10/11. Usa o Docker Desktop (PHP 8.3 + MySQL 8 em containers).
# =============================================================
# 'Continue': no PowerShell 5.1, mensagens normais de progresso do git e do docker
# (que saem no canal de erro) não podem interromper o script.
$ErrorActionPreference = 'Continue'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$repo  = 'https://github.com/joaopdalves/acolhe-mais'
$pasta = Join-Path $HOME 'acolhe-mais'
$url   = 'http://localhost:8000'

function Tem($cmd) { [bool](Get-Command $cmd -ErrorAction SilentlyContinue) }
function DockerOk { cmd /c "docker info >nul 2>&1"; return ($LASTEXITCODE -eq 0) }

Write-Host ''
Write-Host '=== Acolhe+ | instalação automática ===' -ForegroundColor DarkYellow

# 1) Docker Desktop
if (-not (Tem 'docker')) {
    if (-not (Tem 'winget')) {
        Write-Host 'Instale o Docker Desktop em https://www.docker.com/products/docker-desktop/ e rode este script de novo.' -ForegroundColor Red
        Start-Process 'https://www.docker.com/products/docker-desktop/'
        exit 1
    }
    Write-Host '[1/4] Instalando o Docker Desktop (o Windows pode pedir permissão)...'
    winget install -e --id Docker.DockerDesktop --accept-package-agreements --accept-source-agreements
    Write-Host ''
    Write-Host 'Docker instalado. Falta só:' -ForegroundColor Green
    Write-Host '  1. Reiniciar o computador, se o instalador pedir;'
    Write-Host '  2. Abrir o Docker Desktop uma vez e aceitar os termos;'
    Write-Host '  3. Rodar este script de novo.'
    exit 0
}
Write-Host '[1/4] Docker encontrado.'

if (-not (DockerOk)) {
    Write-Host '      Iniciando o Docker Desktop...'
    $exe = Join-Path $Env:ProgramFiles 'Docker\Docker\Docker Desktop.exe'
    if (Test-Path $exe) { Start-Process $exe }
    for ($i = 0; $i -lt 60 -and -not (DockerOk); $i++) { Start-Sleep -Seconds 5 }
    if (-not (DockerOk)) {
        Write-Host 'O Docker não respondeu. Abra o Docker Desktop, espere aparecer "Engine running" e rode de novo.' -ForegroundColor Red
        exit 1
    }
}

# 2) Código do GitHub
Write-Host "[2/4] Baixando o código para $pasta ..."
if (Tem 'git') {
    if (Test-Path (Join-Path $pasta '.git')) { git -C $pasta pull --ff-only }
    else { git clone $repo $pasta }
    if ($LASTEXITCODE -ne 0) { Write-Host 'Falha ao baixar o código do GitHub.' -ForegroundColor Red; exit 1 }
} else {
    if (Test-Path $pasta) {
        if (-not (Test-Path (Join-Path $pasta 'docker-compose.yml'))) {
            Write-Host "A pasta $pasta já existe e não é do Acolhe+. Renomeie-a e rode de novo." -ForegroundColor Red
            exit 1
        }
        Remove-Item $pasta -Recurse -Force
    }
    $zip = Join-Path $env:TEMP 'acolhe-mais.zip'
    $tmp = Join-Path $env:TEMP 'acolhe-mais-main'
    if (Test-Path $tmp) { Remove-Item $tmp -Recurse -Force }
    try { Invoke-WebRequest "$repo/archive/refs/heads/main.zip" -OutFile $zip -UseBasicParsing }
    catch { Write-Host 'Falha ao baixar o código do GitHub. Verifique a internet.' -ForegroundColor Red; exit 1 }
    Expand-Archive $zip -DestinationPath $env:TEMP -Force
    Move-Item $tmp $pasta
}

# 3) Containers
Write-Host '[3/4] Subindo o MySQL e a aplicação (na primeira vez leva de 2 a 5 minutos)...'
Set-Location $pasta
docker compose up -d --build
if ($LASTEXITCODE -ne 0) { Write-Host 'Falha ao subir os containers. Veja a mensagem acima.' -ForegroundColor Red; exit 1 }

# 4) Espera o sistema responder e abre o navegador
Write-Host '[4/4] Aguardando o sistema responder...'
$ok = $false
for ($i = 0; $i -lt 90 -and -not $ok; $i++) {
    try { Invoke-WebRequest "$url/index.php?r=login" -UseBasicParsing -TimeoutSec 3 | Out-Null; $ok = $true }
    catch { Start-Sleep -Seconds 3 }
}
if (-not $ok) { Write-Host 'O sistema ainda não respondeu. Veja os logs com: docker compose logs' -ForegroundColor Red; exit 1 }

Start-Process $url
Write-Host ''
Write-Host "Acolhe+ rodando em $url" -ForegroundColor Green
Write-Host 'Usuários de demonstração (senha: acolhe123):'
Write-Host '  gustavo@ongesperanca.org  -> coordenador'
Write-Host '  matheus@ongesperanca.org  -> padrinho'
Write-Host '  joao@ongesperanca.org     -> integrante com tarefa atrasada'
Write-Host '  paulo@ongesperanca.org    -> integrante no primeiro acesso'
Write-Host ''
Write-Host "Para parar:  cd $pasta ; docker compose down"
Write-Host "Para zerar os dados de demonstração:  docker compose down -v  (e rode o script de novo)"
