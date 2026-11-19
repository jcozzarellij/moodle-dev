#!/usr/bin/env bash
# Restaura un backup creado con backup.sh (REEMPLAZA la BD y moodledata actuales).
# Uso: scripts/restore.sh backups/<nombre>.tar.gz [-y]
source "$(dirname "$0")/_common.sh"
[[ -n "${1:-}" ]] || { echo "Uso: scripts/restore.sh backups/<nombre>.tar.gz [-y]"; ls backups/*.tar.gz 2>/dev/null || true; exit 1; }
file="$(basename "$1")"
[[ -f "backups/$file" ]] || { echo "No existe backups/$file"; exit 1; }
if [[ "${2:-}" != "-y" ]]; then
  confirm "Se REEMPLAZARÁN la base de datos y moodledata actuales con backups/$file. ¿Continuar?" || { echo "Cancelado."; exit 1; }
fi
tmp=/backups/.restore-tmp
dc exec -T moodle sh -c "rm -rf $tmp && mkdir -p $tmp && tar -xzf /backups/$file -C $tmp"
echo "Restaurando base de datos..."
dc exec -T db sh -c "psql -q -U \$POSTGRES_USER -d \$POSTGRES_DB -v ON_ERROR_STOP=1 -c 'SET client_min_messages TO warning; DROP SCHEMA public CASCADE; CREATE SCHEMA public;' && pg_restore -U \$POSTGRES_USER -d \$POSTGRES_DB --no-owner $tmp/db.dump"
echo "Restaurando moodledata..."
dc exec -T moodle sh -c "find /var/www/moodledata -mindepth 1 -delete && tar -xzf $tmp/moodledata.tar.gz -C /var/www/moodledata && chown -R www-data:www-data /var/www/moodledata && rm -rf $tmp"
php_cli admin/cli/purge_caches.php
echo "Restauración completada."
