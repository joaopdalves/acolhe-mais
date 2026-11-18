#!/usr/bin/env bash
# =============================================================
# Acolhe+ | Baixa, instala e roda o sistema em http://localhost:8000
# Linux e macOS. Usa Docker (PHP 8.3 + MySQL 8 em containers).
# =============================================================
set -euo pipefail
REPO=https://github.com/joaopdalves/acolhe-mais
PASTA="$HOME/acolhe-mais"
URL=http://localhost:8000

echo "=== Acolhe+ | instalação automática ==="

# 1) Docker
if ! command -v docker >/dev/null 2>&1; then
  if [ "$(uname)" = "Darwin" ]; then
    if command -v brew >/dev/null 2>&1; then
      echo "[1/4] Instalando o Docker Desktop pelo Homebrew..."
      brew install --cask docker
      open -a Docker
      echo "Abra o Docker Desktop, aceite os termos e rode este script de novo."
    else
      echo "Instale o Docker Desktop em https://www.docker.com/products/docker-desktop/ e rode de novo."
    fi
    exit 0
  fi
  echo "[1/4] Instalando o Docker (pede a senha de administrador)..."
  curl -fsSL https://get.docker.com | sudo sh
  sudo systemctl enable --now docker 2>/dev/null || true
fi
SUDO=""
if ! docker info >/dev/null 2>&1; then
  if [ "$(uname)" = "Darwin" ]; then
    open -a Docker || true
    echo "      Iniciando o Docker Desktop..."
    for _ in $(seq 1 60); do docker info >/dev/null 2>&1 && break; sleep 5; done
  elif sudo docker info >/dev/null 2>&1; then
    SUDO="sudo"
  fi
fi
$SUDO docker info >/dev/null 2>&1 || { echo "O Docker não está respondendo. Inicie o Docker e rode de novo."; exit 1; }
echo "[1/4] Docker pronto."

# 2) Código
echo "[2/4] Baixando o código para $PASTA ..."
if command -v git >/dev/null 2>&1; then
  if [ -d "$PASTA/.git" ]; then git -C "$PASTA" pull --ff-only; else git clone "$REPO" "$PASTA"; fi
else
  if [ -e "$PASTA" ] && [ ! -f "$PASTA/docker-compose.yml" ]; then
    echo "A pasta $PASTA já existe e não é do Acolhe+. Renomeie-a e rode de novo."; exit 1
  fi
  rm -rf "$PASTA"
  TMP=$(mktemp -d)
  curl -fsSL "$REPO/archive/refs/heads/main.tar.gz" | tar -xz -C "$TMP"
  mv "$TMP/acolhe-mais-main" "$PASTA"
fi

# 3) Containers
echo "[3/4] Subindo o MySQL e a aplicação (na primeira vez leva de 2 a 5 minutos)..."
cd "$PASTA"
$SUDO docker compose up -d --build

# 4) Espera e abre o navegador
echo "[4/4] Aguardando o sistema responder..."
for _ in $(seq 1 90); do
  curl -fs -o /dev/null "$URL/index.php?r=login" && break
  sleep 3
done
curl -fs -o /dev/null "$URL/index.php?r=login" || { echo "O sistema ainda não respondeu. Veja: docker compose logs"; exit 1; }

(command -v xdg-open >/dev/null && xdg-open "$URL" >/dev/null 2>&1) || (command -v open >/dev/null && open "$URL") || true
cat <<FIM

Acolhe+ rodando em $URL
Usuários de demonstração (senha: acolhe123):
  gustavo@ongesperanca.org  -> coordenador
  matheus@ongesperanca.org  -> padrinho
  joao@ongesperanca.org     -> integrante com tarefa atrasada
  paulo@ongesperanca.org    -> integrante no primeiro acesso

Para parar:  cd $PASTA && docker compose down
Para zerar os dados de demonstração:  docker compose down -v  (e rode o script de novo)
FIM
