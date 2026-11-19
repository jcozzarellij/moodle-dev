# Borra la BD y moodledata de ESTA instancia y reinstala Moodle desde cero con los datos demo.
# Uso: .\scripts\reset.ps1 [-Yes]
param([switch]$Yes)
. "$PSScriptRoot/_common.ps1"
if (-not $Yes -and -not (Confirm-Action 'Esto BORRARÁ la base de datos y los archivos de la instancia actual. ¿Continuar?')) {
    Write-Host 'Cancelado.'; exit 1
}
Invoke-Dc down -v
Invoke-Dc up -d --build
Write-Host 'Reinstalando... sigue el progreso con: .\scripts\logs.ps1'
