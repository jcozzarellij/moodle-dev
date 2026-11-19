# Funciones comunes para los scripts .ps1 (se carga con ". $PSScriptRoot/_common.ps1").
$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')

function Invoke-Dc {
    docker compose @args
    if ($LASTEXITCODE -ne 0) { throw "docker compose $($args -join ' ') falló (código $LASTEXITCODE)" }
}
function Invoke-PhpCli {
    Invoke-Dc exec -T -u www-data moodle php @args
}
function Confirm-Action([string]$Message) {
    $answer = Read-Host "$Message [s/N]"
    return $answer -match '^[sSyY]$'
}
