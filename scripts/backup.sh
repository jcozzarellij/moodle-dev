#!/usr/bin/env bash
# Guarda una "foto" de la instancia (BD + moodledata) en backups/<nombre>.tar.gz
# Uso: scripts/backup.sh [nombre]
source "$(dirname "$0")/_common.sh"
name="${1:-backup-$(date +%Y%m%d-%H%M%S)}"
tmp="/backups/.tmp-$name"
dc exec -T moodle sh -c "rm -rf $tmp && mkdir -p $tmp"
echo "Exportando base de datos..."
dc exec -T db sh -c "pg_dump -U \$POSTGRES_USER -d \$POSTGRES_DB -Fc -f $tmp/db.dump"
echo "Empaquetando moodledata..."
dc exec -T moodle tar -czf "$tmp/moodledata.tar.gz" -C /var/www/moodledata --exclude=./cache --exclude=./localcache --exclude=./sessions --exclude=./temp --exclude=./lock .
dc exec -T moodle sh -c "cp /opt/moodle-dev/MOODLE_VERSION $tmp/ && tar -czf /backups/$name.tar.gz -C $tmp . && rm -rf $tmp"
echo "Backup creado: backups/$name.tar.gz"
