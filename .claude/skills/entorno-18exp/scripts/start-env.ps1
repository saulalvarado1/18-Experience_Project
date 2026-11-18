param([switch]$CheckOnly)

# Verifica PHP y PostgreSQL (puerto 5432) para 18 Experience Web.
$php = "C:\xampp\php\php.exe"
$servicio = "postgresql-x64-18"

function Test-Postgres {
    $tcp = Test-NetConnection -ComputerName 127.0.0.1 -Port 5432 -WarningAction SilentlyContinue
    if ($tcp.TcpTestSucceeded) { return "PGSQL_ONLINE" }
    return "PGSQL_OFFLINE"
}

Write-Output "PHP:        $(& $php -r 'echo PHP_VERSION;')"
$estado = Test-Postgres
Write-Output "PostgreSQL: $estado"

$svc = Get-Service -Name $servicio -ErrorAction SilentlyContinue
if ($svc) { Write-Output "Servicio:   $servicio ($($svc.Status))" }

if (-not $CheckOnly -and $estado -eq "PGSQL_OFFLINE") {
    Write-Output "Intentando iniciar el servicio $servicio (requiere administrador)..."
    try {
        Start-Service -Name $servicio -ErrorAction Stop
        Start-Sleep -Seconds 3
        Write-Output "PostgreSQL: $(Test-Postgres)"
    } catch {
        Write-Output "No se pudo iniciar: $($_.Exception.Message). Ejecutar como administrador."
    }
}
