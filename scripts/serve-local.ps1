param([ValidateRange(1024,65535)][int]$Port = 8080)
$ErrorActionPreference = 'Stop'
$projectDirectory = Split-Path -Parent $PSScriptRoot
$phpExecutable = 'C:\xampp\php\php.exe'
if (-not (Test-Path -LiteralPath $phpExecutable)) { throw 'PHP executable missing; edit phpExecutable in this local script.' }
if (Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue) { throw "Port $Port is already used. Choose another -Port; existing services were not stopped." }
$previousAppUrl = $env:COUPONHUB_APP_URL
$previousSessionPath = $env:COUPONHUB_SESSION_PATH
$previousRateLimitPath = $env:COUPONHUB_RATE_LIMIT_DIR
try {
    $runtimeDirectory = Join-Path $env:LOCALAPPDATA 'CouponHub\runtime'
    New-Item -ItemType Directory -Path $runtimeDirectory -Force | Out-Null
    $env:COUPONHUB_SESSION_PATH = Join-Path $runtimeDirectory 'sessions'
    $env:COUPONHUB_RATE_LIMIT_DIR = Join-Path $runtimeDirectory 'rate-limits'
    $env:COUPONHUB_APP_URL = "http://localhost:$Port/" + (Split-Path -Leaf $projectDirectory)
    Write-Host "CouponHub local: $env:COUPONHUB_APP_URL/"
    Write-Host 'Protected router active; Ctrl+C stops only this local server.'
    & $phpExecutable -d display_errors=0 -d log_errors=1 -S "localhost:$Port" -t (Split-Path -Parent $projectDirectory) (Join-Path $projectDirectory 'router.php')
} finally {
    $env:COUPONHUB_APP_URL = $previousAppUrl
    $env:COUPONHUB_SESSION_PATH = $previousSessionPath
    $env:COUPONHUB_RATE_LIMIT_DIR = $previousRateLimitPath
}
