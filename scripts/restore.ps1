# Restaura un backup creado con backup.ps1 (REEMPLAZA la BD y moodledata actuales).
# Uso: .\scripts\restore.ps1 backups\<nombre>.tar.gz [-Yes]
param([Parameter(Mandatory = $true)][string]$File, [switch]$Yes)
. "$PSScriptRoot/_common.ps1"
$name = Split-Path $File -Leaf
if (-not (Test-Path "backups/$name")) { throw "No existe backups\$name" }
if (-not $Yes -and -not (Confirm-Action "Se REEMPLAZARÁN la base de datos y moodledata actuales con backups\$name. ¿Continuar?")) {
    Write-Host 'Cancelado.'; exit 1
}
$tmp = '/backups/.restore-tmp'
Invoke-Dc exec -T moodle sh -c "rm -rf $tmp && mkdir -p $tmp && tar -xzf /backups/$name -C $tmp"
Write-Host 'Restaurando base de datos...'
Invoke-Dc exec -T db sh -c "psql -q -U `$POSTGRES_USER -d `$POSTGRES_DB -v ON_ERROR_STOP=1 -c 'SET client_min_messages TO warning; DROP SCHEMA public CASCADE; CREATE SCHEMA public;' && pg_restore -U `$POSTGRES_USER -d `$POSTGRES_DB --no-owner $tmp/db.dump"
Write-Host 'Restaurando moodledata...'
Invoke-Dc exec -T moodle sh -c "find /var/www/moodledata -mindepth 1 -delete && tar -xzf $tmp/moodledata.tar.gz -C /var/www/moodledata && chown -R www-data:www-data /var/www/moodledata && rm -rf $tmp"
Invoke-PhpCli admin/cli/purge_caches.php
Write-Host 'Restauración completada.'
