# Instala/actualiza plugins nuevos o modificados (y el núcleo tras cambiar MOODLE_VERSION).
. "$PSScriptRoot/_common.ps1"
Invoke-PhpCli admin/cli/upgrade.php --non-interactive
Invoke-PhpCli admin/cli/purge_caches.php
Write-Host 'Actualización completada.'
