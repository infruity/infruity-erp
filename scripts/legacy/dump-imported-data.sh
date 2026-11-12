#!/usr/bin/env bash
# Run where `legacy:import` + `legacy:reconcile` were done (a machine with a MySQL/MariaDB you control).
# Writes a DATA-ONLY dump of the imported database: it empties every table first (TRUNCATE) and then fills it, so it can
# be piped into the application database of a server where the import itself cannot run (shared hosting gives one user
# one database, and the importer needs the legacy copy and the target side by side).
#
#   scripts/legacy/dump-imported-data.sh <imported_db> [outfile.sql.gz]
#   gunzip -c outfile.sql.gz | mysql <app_db>        # on the server, after `php artisan migrate`
#
# `migrations` and the reg_* region tables are left alone (the migrations own them). Connection: MYSQL_USER / MYSQL_HOST /
# MYSQL_TCP_PORT / MYSQL_PWD, like the other scripts here.
set -euo pipefail

DB="${1:?usage: dump-imported-data.sh <imported_db> [outfile.sql.gz]}"
OUT="${2:-infruity_imported_data_$(date +%Y%m%d_%H%M%S).sql.gz}"
case "$DB" in *[!A-Za-z0-9_]*) echo "unsafe database name: $DB" >&2; exit 2;; esac

CONN=()
[ -n "${MYSQL_USER:-}" ] && CONN+=("-u$MYSQL_USER")
[ -n "${MYSQL_HOST:-}" ] && CONN+=("-h$MYSQL_HOST")

TABLES=$(mysql ${CONN[@]+"${CONN[@]}"} -N -B -e "SELECT table_name FROM information_schema.tables WHERE table_schema = '$DB' AND table_type = 'BASE TABLE' AND table_name <> 'migrations' AND table_name NOT LIKE 'reg\\_%' ORDER BY 1")
[ -n "$TABLES" ] || { echo "no tables in $DB" >&2; exit 3; }

umask 077
{
  echo "SET FOREIGN_KEY_CHECKS=0; SET UNIQUE_CHECKS=0; SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO';"
  for t in $TABLES; do echo "TRUNCATE TABLE \`$t\`;"; done
  # shellcheck disable=SC2086
  mysqldump ${CONN[@]+"${CONN[@]}"} --no-create-info --skip-triggers --complete-insert --no-tablespaces --skip-add-locks \
    --default-character-set=utf8mb4 --hex-blob "$DB" $TABLES
  echo "SET FOREIGN_KEY_CHECKS=1;"
} | gzip -c > "$OUT"
gzip -t "$OUT"
echo "wrote $OUT ($(du -h "$OUT" | cut -f1)): $(echo "$TABLES" | wc -l | tr -d ' ') tables"
