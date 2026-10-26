param([switch]$CheckOnly)

$php = "C:\xampp\php\php.exe"
$probe = @'
<?php
try {
    new PDO('mysql:host=127.0.0.1;port=3306;dbname=db_18_experience', 'root', '', [PDO::ATTR_TIMEOUT => 3]);
    echo "MYSQL_ONLINE";
} catch (Throwable $e) {
    echo "MYSQL_OFFLINE: " . $e->getMessage();
}
'@
$tmp = Join-Path $env:TEMP "probe18exp.php"
Set-Content -Path $tmp -Value $probe -Encoding UTF8
$result = & $php $tmp
Remove-Item $tmp -ErrorAction SilentlyContinue

Write-Output "PHP:      $(& $php -r 'echo PHP_VERSION;')"
Write-Output "MySQL:    $result"

if (-not $CheckOnly -and $result -like "MYSQL_OFFLINE*") {
    Write-Output "Iniciando mysqld en segundo plano..."
    Start-Process -FilePath "C:\xampp\mysql\bin\mysqld.exe" `
        -ArgumentList '--defaults-file="C:\xampp\mysql\bin\my.ini"', '--standalone' `
        -WindowStyle Hidden
    Start-Sleep -Seconds 4
    & $PSCommandPath -CheckOnly
}
