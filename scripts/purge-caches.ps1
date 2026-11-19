# Purga todas las cachés de Moodle.
. "$PSScriptRoot/_common.ps1"
Invoke-PhpCli admin/cli/purge_caches.php
Write-Host 'Cachés purgadas.'
