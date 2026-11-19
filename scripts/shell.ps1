# Abre una terminal dentro del contenedor. Uso: .\scripts\shell.ps1 [moodle|db]
param([string]$Service = 'moodle')
. "$PSScriptRoot/_common.ps1"
if ($Service -eq 'db') { Invoke-Dc exec db sh -c 'psql -U $POSTGRES_USER -d $POSTGRES_DB' }
else { Invoke-Dc exec moodle bash }
