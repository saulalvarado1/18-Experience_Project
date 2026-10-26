param([string]$Filter = "")

$root = Resolve-Path (Join-Path $PSScriptRoot "..\..\..\..")
Push-Location $root
try {
    if ($Filter) {
        & "C:\xampp\php\php.exe" artisan test --filter=$Filter
    } else {
        & "C:\xampp\php\php.exe" artisan test
    }
    exit $LASTEXITCODE
} finally {
    Pop-Location
}
