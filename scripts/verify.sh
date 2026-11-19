#!/usr/bin/env bash
# Comprueba que los datos demo (seed/data.json) existen en la instancia.
source "$(dirname "$0")/_common.sh"
php_cli /opt/moodle-dev/seed/verify.php
