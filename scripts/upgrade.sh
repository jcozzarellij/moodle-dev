#!/usr/bin/env bash
# Instala/actualiza plugins nuevos o modificados (y el núcleo tras cambiar MOODLE_VERSION).
source "$(dirname "$0")/_common.sh"
php_cli admin/cli/upgrade.php --non-interactive
php_cli admin/cli/purge_caches.php
echo "Actualización completada."
