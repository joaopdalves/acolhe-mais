#!/usr/bin/env bash
# Cria o banco acolhe_mais do zero: estrutura, ajustes do MVP e dados de demonstração.
# Uso: bin/instalar_banco.sh [usuario_mysql] [senha]
set -euo pipefail
U="${1:-root}"; P="${2:-}"
DIR="$(cd "$(dirname "$0")/../database" && pwd)"
mysql -u"$U" ${P:+-p"$P"} -e "DROP DATABASE IF EXISTS acolhe_mais;"
for f in 01_schema.sql 02_mvp.sql 03_seed.sql; do
  echo "-> $f"; mysql --default-character-set=utf8mb4 -u"$U" ${P:+-p"$P"} < "$DIR/$f"
done
echo "Banco acolhe_mais pronto."
