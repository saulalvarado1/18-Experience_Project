$ErrorActionPreference = "Continue"
$projectDir = (Resolve-Path (Join-Path $PSScriptRoot "..\..\..\..")).Path
$php = "C:\xampp\php\php.exe"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "       AUDITORIA TECNICA - 18 EXPERIENCE WEB              " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Estado de PHP y PostgreSQL
Write-Host "`n[1/5] Entorno y Base de Datos (PostgreSQL):" -ForegroundColor Yellow
& $php -v | Select-Object -First 1

$portCheckPg = Test-NetConnection -ComputerName 127.0.0.1 -Port 5432 -WarningAction SilentlyContinue
if ($portCheckPg.TcpTestSucceeded) {
    Write-Host "  [OK] PostgreSQL 18 activo en 127.0.0.1:5432" -ForegroundColor Green
    Push-Location $projectDir
    & $php artisan db:show
    Pop-Location
} else {
    Write-Host "  [ERROR] PostgreSQL NO responde en 127.0.0.1:5432" -ForegroundColor Red
}

# 2. Modulos implementados
Write-Host "`n[2/5] Modulos en app/Modules:" -ForegroundColor Yellow
$modulesPath = Join-Path $projectDir "app\Modules"
if (Test-Path $modulesPath) {
    Get-ChildItem -Path $modulesPath -Directory | ForEach-Object {
        $modName = $_.Name
        $files = Get-ChildItem -Path $_.FullName -Recurse -File -Filter "*.php" | Select-Object -ExpandProperty Name
        Write-Host "  - $modName ($($files.Count) archivos): $($files -join ', ')"
    }
}

# 3. Vistas Blade
Write-Host "`n[3/5] Vistas Blade en resources/views:" -ForegroundColor Yellow
$viewsPath = Join-Path $projectDir "resources\views"
Get-ChildItem -Path $viewsPath -Recurse -File -Filter "*.blade.php" | ForEach-Object {
    $rel = $_.FullName.Substring($viewsPath.Length + 1)
    Write-Host "  - $rel"
}

# 4. Pruebas Automatizadas (PHPUnit)
Write-Host "`n[4/5] Ejecucion de Pruebas PHPUnit:" -ForegroundColor Yellow
Push-Location $projectDir
& $php artisan test --no-ansi
Pop-Location

# 5. Estado de Git
Write-Host "`n[5/5] Historial reciente de Git y estado:" -ForegroundColor Yellow
Push-Location $projectDir
git log --oneline -n 5
git status -s
Pop-Location
