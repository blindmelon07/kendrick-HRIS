<#
.SYNOPSIS
    Builds tristellar-hris-hostinger.zip for upload to Hostinger shared/cloud hosting.

.DESCRIPTION
    Hostinger shared hosting has no Node.js, so the web assets are built here, and
    production Composer packages are bundled so the server only needs PHP 8.4+.
    The zip never contains .env, tests, the mobile app or local caches.

    Usage (from the project folder):  powershell -ExecutionPolicy Bypass -File .\package-for-hostinger.ps1
#>

$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot
$staging = Join-Path $env:TEMP 'tristellar-hris-package'
$zip = Join-Path $root 'tristellar-hris-hostinger.zip'

Write-Host '1/4 Building web assets...' -ForegroundColor Cyan
Push-Location $root
try {
    npm run build
    if ($LASTEXITCODE -ne 0) { throw 'npm run build failed.' }
} finally {
    Pop-Location
}

Write-Host '2/4 Copying application files...' -ForegroundColor Cyan
if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }
New-Item -ItemType Directory -Path $staging | Out-Null

$excludeDirs = @('node_modules', 'vendor', 'mobile', '.git', '.github', 'tests', '.claude', '.agents', '.vscode', '.idea', '.playwright-mcp', '.junie', '.cursor')
$excludeFiles = @('.env', '.env.backup', '.env.production', 'hot', '*.docx', '*.xlsx', 'tristellar.jpg', 'tristellar-hris-hostinger.zip', 'package-for-hostinger.ps1', '*.log', '.phpunit.result.cache')
robocopy $root $staging /E /NFL /NDL /NJH /NJS /NP /XD $excludeDirs /XF $excludeFiles | Out-Null
if ($LASTEXITCODE -ge 8) { throw "Copying files failed (robocopy exit code $LASTEXITCODE)." }

# Local caches must not reach the server; keep the folders and their .gitignore files.
$cacheFolders = @('bootstrap\cache', 'storage\framework\cache\data', 'storage\framework\sessions', 'storage\framework\views', 'storage\logs')
foreach ($folder in $cacheFolders) {
    $path = Join-Path $staging $folder
    if (Test-Path $path) {
        Get-ChildItem $path -Recurse -File | Where-Object { $_.Name -ne '.gitignore' } | Remove-Item -Force
    }
}

Write-Host '3/4 Installing production Composer packages...' -ForegroundColor Cyan
composer install --working-dir="$staging" --no-dev --optimize-autoloader --no-interaction --no-scripts --no-progress
if ($LASTEXITCODE -ne 0) { throw 'composer install failed.' }

Write-Host '4/4 Creating zip...' -ForegroundColor Cyan
if (Test-Path $zip) { Remove-Item $zip -Force }
# Windows' tar.exe writes standard zip paths (forward slashes) that Linux servers extract correctly.
tar.exe -a -c -f "$zip" -C "$staging" .
if ($LASTEXITCODE -ne 0) { throw 'Creating the zip failed.' }

Remove-Item $staging -Recurse -Force
$size = [math]::Round((Get-Item $zip).Length / 1MB, 1)
Write-Host "Done: $zip ($size MB)" -ForegroundColor Green
