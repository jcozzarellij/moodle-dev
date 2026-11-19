#!/usr/bin/env bash
# Abre una terminal dentro del contenedor. Uso: scripts/shell.sh [moodle|db]
source "$(dirname "$0")/_common.sh"
if [[ "${1:-moodle}" == "db" ]]; then
  dc exec db sh -c 'psql -U $POSTGRES_USER -d $POSTGRES_DB'
else
  dc exec moodle bash
fi
