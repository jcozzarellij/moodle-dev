#!/usr/bin/env bash
# Muestra los logs en vivo. Uso: scripts/logs.sh [moodle|db|cron]
source "$(dirname "$0")/_common.sh"
if [[ "${1:-moodle}" == "cron" ]]; then
  dc exec moodle cat /tmp/moodle-cron.log
else
  dc logs -f --tail=200 "${1:-moodle}"
fi
