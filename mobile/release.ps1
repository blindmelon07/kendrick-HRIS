<#
.SYNOPSIS
    Builds a new PKMART HRIS app release for in-app updates.

.DESCRIPTION
    Bumps the build number in pubspec.yaml, builds the signed release APK, and writes
    dist\PKMART-HRIS.apk plus dist\latest.json (version, checksum, release notes).
    Upload BOTH files to the site's public/downloads/ folder; installed apps then offer
    the update the next time they are opened.

    Usage (from the mobile folder):
        powershell -ExecutionPolicy Bypass -File .\release.ps1 -Version 1.2.0 -Notes "What changed"
    -Required   blocks older versions until they update.
    -SkipBuild  rewrites latest.json for the APK already in dist\.
#>

param(
    [Parameter(Mandatory = $true)]
    [ValidatePattern('^\d+\.\d+\.\d+$')]
    [string] $Version,

    [string] $Notes = '',

    [switch] $Required,

    [switch] $SkipBuild,

    [string] $SiteUrl = 'https://mistyrose-walrus-927022.hostingersite.com'
)

$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot
$pubspec = Join-Path $root 'pubspec.yaml'
$dist = Join-Path $root 'dist'
$apk = Join-Path $dist 'PKMART-HRIS.apk'
$latestPath = Join-Path $dist 'latest.json'

if (-not (Test-Path (Join-Path $root 'android\key.properties'))) {
    throw 'android\key.properties is missing. Restore the release key backup before building, or updates will not install over existing apps.'
}

$content = Get-Content $pubspec -Raw
if ($content -notmatch '(?m)^version:\s*([\d.]+)\+(\d+)\s*$') { throw 'Could not read the version in pubspec.yaml.' }

if ($SkipBuild) {
    if ($Matches[1] -ne $Version) { throw "The current build is version $($Matches[1]), not $Version. Run without -SkipBuild." }
    if (-not (Test-Path $apk)) { throw "No APK at $apk. Run without -SkipBuild." }
    $build = [int]$Matches[2]
    Write-Host "1/3 Using the existing build $Version (build $build)." -ForegroundColor Cyan
} else {
    # Next build number = current + 1 (installed apps compare build numbers).
    $build = [int]$Matches[2] + 1
    Write-Host "1/3 Building version $Version (build $build)..." -ForegroundColor Cyan

    Push-Location $root
    try {
        $env:JAVA_TOOL_OPTIONS = '-Djava.net.preferIPv4Stack=true'
        flutter build apk --release --build-name=$Version --build-number=$build --dart-define=API_URL=$SiteUrl
        if ($LASTEXITCODE -ne 0) { throw 'flutter build failed.' }
    } finally {
        Pop-Location
    }

    # Record the new version only once the build succeeded, so a failed attempt does not skip a number.
    [IO.File]::WriteAllText($pubspec, ($content -replace '(?m)^version:\s*.+$', "version: $Version+$build"))

    Write-Host '2/3 Copying the APK...' -ForegroundColor Cyan
    New-Item -ItemType Directory -Force -Path $dist | Out-Null
    Copy-Item (Join-Path $root 'build\app\outputs\flutter-apk\app-release.apk') $apk -Force
}

Write-Host '3/3 Writing latest.json...' -ForegroundColor Cyan
$minBuild = 1
if (Test-Path $latestPath) {
    $previous = Get-Content $latestPath -Raw | ConvertFrom-Json
    if ($previous.min_build) { $minBuild = [int]$previous.min_build }
}
if ($Required) { $minBuild = $build }

$release = [ordered]@{
    version = $Version
    build = $build
    min_build = $minBuild
    url = "$SiteUrl/downloads/PKMART-HRIS.apk?v=$build"
    sha256 = (Get-FileHash $apk -Algorithm SHA256).Hash.ToLower()
    notes = $Notes
    released_at = (Get-Date).ToString('yyyy-MM-dd HH:mm')
}
[IO.File]::WriteAllText($latestPath, ($release | ConvertTo-Json))

$size = [math]::Round((Get-Item $apk).Length / 1MB, 1)
Write-Host "Done: version $Version (build $build), $size MB" -ForegroundColor Green
Write-Host "Upload BOTH files to public/downloads/ on Hostinger:" -ForegroundColor Green
Write-Host "  $apk"
Write-Host "  $latestPath"
