# Muestra los logs en vivo. Uso: .\scripts\logs.ps1 [moodle|db|cron]
param([string]$Service = 'moodle')
. "$PSScriptRoot/_common.ps1"
if ($Service -eq 'cron') { Invoke-Dc exec moodle cat /tmp/moodle-cron.log }
else { Invoke-Dc logs -f --tail=200 $Service }
