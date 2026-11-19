#!/usr/bin/env bash
# Purga todas las cachés de Moodle.
source "$(dirname "$0")/_common.sh"
php_cli admin/cli/purge_caches.php
echo "Cachés purgadas."
