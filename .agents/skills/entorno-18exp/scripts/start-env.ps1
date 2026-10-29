param([switch]$CheckOnly)

$php = "C:\xampp\php\php.exe"
$probe = @'
<?php
try {
    new PDO('pgsql:host=127.0.0.1;port=5432;dbname=db_18_experience', 'postgres', 'saul123', [PDO::ATTR_TIMEOUT => 3]);
    echo "POSTGRES_ONLINE";
} catch (Throwable $e) {
    echo "POSTGRES_OFFLINE: " . $e->getMessage();
}
'@
$tmp = Join-Path $env:TEMP "probe18exp.php"
Set-Content -Path $tmp -Value $probe -Encoding UTF8
$result = & $php $tmp
Remove-Item $tmp -ErrorAction SilentlyContinue

Write-Output "PHP:         $(& $php -r 'echo PHP_VERSION;')"
Write-Output "PostgreSQL:  $result"

if (-not $CheckOnly -and $result -like "POSTGRES_OFFLINE*") {
    Write-Output "Iniciando PostgreSQL en segundo plano..."
    & "C:\xampp\pgsql\bin\pg_ctl.exe" -D "C:\xampp\pgsql\data" -l "C:\xampp\pgsql\postgres.log" start
    Start-Sleep -Seconds 2
    & $PSCommandPath -CheckOnly
}
