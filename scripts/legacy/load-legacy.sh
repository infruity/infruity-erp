#!/usr/bin/env bash
# Run ON THE NEW SERVER, in the application folder. Loads a dump from dump-legacy.sh into a SCRATCH database
# (the "source" of `php artisan legacy:import`) and rebuilds the stock / HPP ledger views in it, which
# `php artisan legacy:reconcile` needs to compare the ledgers.
#
#   scripts/legacy/load-legacy.sh <scratch_db> <dump.sql.gz> [--replace]
#
# Refuses to touch an existing database unless --replace is given. Never point it at the application database.
set -euo pipefail

SCRATCH="${1:?usage: load-legacy.sh <scratch_db> <dump.sql.gz> [--replace]}"
DUMP="${2:?usage: load-legacy.sh <scratch_db> <dump.sql.gz> [--replace]}"
REPLACE="${3:-}"
HERE="$(cd "$(dirname "$0")/../.." && pwd)"

case "$SCRATCH" in *[!A-Za-z0-9_]*) echo "unsafe database name: $SCRATCH" >&2; exit 2;; esac
[ -f "$DUMP" ] || { echo "no such file: $DUMP" >&2; exit 2; }

CONN=()
[ -n "${MYSQL_USER:-}" ] && CONN+=("-u$MYSQL_USER")
[ -n "${MYSQL_HOST:-}" ] && CONN+=("-h$MYSQL_HOST")
sql() { mysql ${CONN[@]+"${CONN[@]}"} "$@"; }

EXISTS=$(sql -N -B -e "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = '$SCRATCH'")
if [ "$EXISTS" != "0" ] && [ "$REPLACE" != "--replace" ]; then
  echo "database $SCRATCH already exists; pass --replace to drop and reload it" >&2
  exit 3
fi

sql -e "DROP DATABASE IF EXISTS \`$SCRATCH\`; CREATE DATABASE \`$SCRATCH\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
gunzip -c "$DUMP" | sql "$SCRATCH"

# Older legacy databases predate some columns the ledger views read. Add them the way their migrations do (nullable,
# NULL = the old rule) in this scratch database only, so the views can be built.
add_column() { # table column definition
  local has
  has=$(sql -N -B -e "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = '$SCRATCH' AND table_name = '$1' AND column_name = '$2'")
  [ "$has" != "0" ] || sql "$SCRATCH" -e "ALTER TABLE \`$1\` ADD COLUMN \`$2\` $3"
}
add_column transfer_detail sent_quantity "DECIMAL(10,2) NULL"
add_column production hpp_per_unit "DECIMAL(15,3) NULL"

# The ledger views, from the same SQL files the migrations use (order matters: product_stock reads transaction_stock and transaction_stock_show, product_hpp reads transaction_stock).
for f in transaction_stock transaction_stock_show product_stock product_hpp; do
  sql "$SCRATCH" < "$HERE/database/sql/$f.sql"
done

TABLES=$(sql -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$SCRATCH' AND table_type = 'BASE TABLE'")
VIEWS=$(sql -N -B -e "SELECT COUNT(*) FROM information_schema.views WHERE table_schema = '$SCRATCH'")
echo "loaded into $SCRATCH: $TABLES tables, $VIEWS ledger view(s)"
echo "next: DB_DATABASE=<new_db> php artisan legacy:import $SCRATCH --dry-run"
