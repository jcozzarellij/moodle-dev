#!/bin/bash
# Arranque del contenedor de Moodle:
#   1. Ajusta PHP según .env
#   2. Espera a PostgreSQL
#   3. BD vacía  -> instala Moodle + idioma + datos demo
#      BD con datos -> ejecuta upgrade (no-op si no hay cambios)
#   4. Lanza cron en segundo plano y arranca Apache
set -euo pipefail

MOODLE_DIR=/var/www/html
DATA_DIR=/var/www/moodledata
DEV_DIR=/opt/moodle-dev
READY_FLAG=/tmp/moodle-dev-ready
CRON_LOG=/tmp/moodle-cron.log

log() { echo "[moodle-dev] $*"; }
as_www() { runuser -u www-data -- "$@"; }

rm -f "$READY_FLAG"

# --- 1. PHP ---------------------------------------------------------------
cat > /usr/local/etc/php/conf.d/zz-moodle-dev.ini <<EOF
memory_limit = ${PHP_MEMORY_LIMIT:-512M}
upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE:-128M}
post_max_size = ${PHP_POST_MAX_SIZE:-128M}
max_input_vars = 5000
max_execution_time = 300
opcache.enable = 1
EOF

# --- 2. Directorio de datos y BD ------------------------------------------
mkdir -p "$DATA_DIR"
chown www-data:www-data "$DATA_DIR"

log "Esperando a PostgreSQL (${MOODLE_DB_HOST:-db})..."
for i in $(seq 1 60); do
  set +e; php "$DEV_DIR/bin/db-status.php"; status=$?; set -e
  [ "$status" -ne 1 ] && break
  sleep 2
done
if [ "$status" -eq 1 ]; then
  log "ERROR: no se pudo conectar con PostgreSQL."; exit 1
fi

# --- 3. Instalación / actualización ---------------------------------------
if [ "$status" -eq 2 ]; then
  log "Base de datos vacía: instalando Moodle $(cat "$DEV_DIR/MOODLE_VERSION")..."
  # Paquetes de idioma incluidos en la imagen.
  mkdir -p "$DATA_DIR/lang"
  cp -rn "$DEV_DIR"/lang/* "$DATA_DIR/lang/" 2>/dev/null || true
  chown -R www-data:www-data "$DATA_DIR"

  as_www php "$MOODLE_DIR/admin/cli/install_database.php" \
    --agree-license \
    --lang="${MOODLE_LANG:-es}" \
    --adminuser="${MOODLE_ADMIN_USER:-admin}" \
    --adminpass="${MOODLE_ADMIN_PASS:-Admin#2026}" \
    --adminemail="${MOODLE_ADMIN_EMAIL:-admin@example.com}" \
    --fullname="${MOODLE_SITE_NAME:-Moodle Dev}" \
    --shortname="${MOODLE_SITE_SHORTNAME:-moodledev}" \
    --summary="Instancia de Moodle para desarrollo y pruebas" \
    --supportemail="${MOODLE_ADMIN_EMAIL:-admin@example.com}"
  log "Moodle instalado."
else
  log "Moodle ya instalado: comprobando actualizaciones (núcleo y plugins)..."
  as_www php "$MOODLE_DIR/admin/cli/upgrade.php" --non-interactive --allow-unstable || {
    log "ERROR en upgrade.php: revisa los logs."; exit 1; }
fi

# Datos demo (idempotente: no hace nada si ya se cargaron).
as_www php "$DEV_DIR/seed/seed.php"

# --- 4. Cron + Apache ------------------------------------------------------
(
  while true; do
    sleep 60
    as_www php "$MOODLE_DIR/admin/cli/cron.php" > "$CRON_LOG" 2>&1 || true
  done
) &

touch "$READY_FLAG"
log "Moodle listo en ${MOODLE_WWWROOT:-http://localhost:8080}"
exec moodle-docker-php-entrypoint "$@"
