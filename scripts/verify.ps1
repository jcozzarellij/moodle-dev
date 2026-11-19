# Comprueba que los datos demo (seed/data.json) existen en la instancia.
. "$PSScriptRoot/_common.ps1"
Invoke-PhpCli /opt/moodle-dev/seed/verify.php
