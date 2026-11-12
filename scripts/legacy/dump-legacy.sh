#!/usr/bin/env bash
# Run ON THE OLD SERVER. Takes a read-only, tables-only dump of the legacy Infruity database.
#
#   scripts/legacy/dump-legacy.sh <old_db> [outfile.sql.gz]
#
# Credentials come from the usual mysql client sources (~/.my.cnf, MYSQL_PWD, or MYSQL_USER / MYSQL_HOST below).
# Nothing in the old database is changed: the dump runs in one consistent snapshot (--single-transaction, no locks on
# InnoDB tables). VIEWS ARE LEFT OUT on purpose: mysqldump cannot always reload them (recursive CTE views fail with
# "Incorrect column name"), and the new application builds its own views from its migrations.
set -euo pipefail

OLD_DB="${1:?usage: dump-legacy.sh <old_db> [outfile.sql.gz]}"
OUT="${2:-infruity_legacy_$(date +%Y%m%d_%H%M%S).sql.gz}"

case "$OLD_DB" in *[!A-Za-z0-9_]*) echo "unsafe database name: $OLD_DB" >&2; exit 2;; esac

CONN=()
[ -n "${MYSQL_USER:-}" ] && CONN+=("-u$MYSQL_USER")
[ -n "${MYSQL_HOST:-}" ] && CONN+=("-h$MYSQL_HOST")

IGNORE=()
while IFS= read -r view; do
  [ -n "$view" ] && IGNORE+=("--ignore-table=$OLD_DB.$view")
done < <(mysql ${CONN[@]+"${CONN[@]}"} -N -B -e "SELECT table_name FROM information_schema.views WHERE table_schema = '$OLD_DB'")

mysqldump ${CONN[@]+"${CONN[@]}"} \
  --single-transaction --no-tablespaces --skip-triggers --skip-routines \
  --set-gtid-purged=OFF --default-character-set=utf8mb4 \
  ${IGNORE[@]+"${IGNORE[@]}"} "$OLD_DB" | gzip -c > "$OUT"

TABLES=$(mysql ${CONN[@]+"${CONN[@]}"} -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$OLD_DB' AND table_type = 'BASE TABLE'")
echo "wrote $OUT ($(du -h "$OUT" | cut -f1)): $TABLES tables, ${#IGNORE[@]} view(s) left out"
echo "next: copy it to the new server, then scripts/legacy/load-legacy.sh <scratch_db> $OUT"
