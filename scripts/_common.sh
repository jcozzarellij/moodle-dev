# Funciones comunes para los scripts .sh (se carga con "source").
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
# Git Bash en Windows convierte rutas como /opt/... a C:/Program Files/Git/opt/...; lo evitamos.
export MSYS_NO_PATHCONV=1

dc() { docker compose "$@"; }
php_cli() { dc exec -T -u www-data moodle php "$@"; }
confirm() {
  local answer
  read -r -p "$1 [s/N] " answer
  [[ "$answer" =~ ^[sSyY]$ ]]
}
