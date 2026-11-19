#!/usr/bin/env bash
# Borra la BD y moodledata de ESTA instancia y reinstala Moodle desde cero con los datos demo.
# Uso: scripts/reset.sh [-y]
source "$(dirname "$0")/_common.sh"
if [[ "${1:-}" != "-y" ]]; then
  confirm "Esto BORRARÁ la base de datos y los archivos de la instancia actual. ¿Continuar?" || { echo "Cancelado."; exit 1; }
fi
dc down -v
dc up -d --build
echo "Reinstalando... sigue el progreso con: scripts/logs.sh"
