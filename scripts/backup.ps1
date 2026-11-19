# Guarda una "foto" de la instancia (BD + moodledata) en backups\<nombre>.tar.gz
# Uso: .\scripts\backup.ps1 [nombre]
param([string]$Name = "backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')")
. "$PSScriptRoot/_common.ps1"
$tmp = "/backups/.tmp-$Name"
Invoke-Dc exec -T moodle sh -c "rm -rf $tmp && mkdir -p $tmp"
Write-Host 'Exportando base de datos...'
Invoke-Dc exec -T db sh -c "pg_dump -U `$POSTGRES_USER -d `$POSTGRES_DB -Fc -f $tmp/db.dump"
Write-Host 'Empaquetando moodledata...'
Invoke-Dc exec -T moodle tar -czf "$tmp/moodledata.tar.gz" -C /var/www/moodledata --exclude=./cache --exclude=./localcache --exclude=./sessions --exclude=./temp --exclude=./lock .
Invoke-Dc exec -T moodle sh -c "cp /opt/moodle-dev/MOODLE_VERSION $tmp/ && tar -czf /backups/$Name.tar.gz -C $tmp . && rm -rf $tmp"
Write-Host "Backup creado: backups\$Name.tar.gz"
