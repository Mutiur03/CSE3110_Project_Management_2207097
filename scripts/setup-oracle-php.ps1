# One-time Oracle + PHP setup for Windows (XAMPP).
# Run on every new machine: composer run setup-oracle

$ErrorActionPreference = 'Stop'

function Write-Step($Message) {
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Get-PhpIniPath {
    $iniOutput = & php --ini 2>$null
    $loaded = $iniOutput | Where-Object { $_ -match 'Loaded Configuration File:\s+(.+)$' } | ForEach-Object {
        if ($_ -match 'Loaded Configuration File:\s+(.+)$') { $Matches[1].Trim() }
    } | Select-Object -First 1

    if (-not $loaded -or -not (Test-Path $loaded)) {
        throw 'Could not find php.ini. Install XAMPP PHP 8.2 and ensure php is on PATH.'
    }

    return $loaded
}

function Test-Oci8Loaded {
    $result = & php -r "echo extension_loaded('oci8') ? 'yes' : 'no';" 2>$null
    return ($result -join '').Trim() -eq 'yes'
}

function Enable-Oci8Extension($IniPath) {
    $content = Get-Content $IniPath -Raw
    $updated = $content

    $updated = $updated -replace '(?m)^\s*extension\s*=\s*oci8\s*$', ';extension=oci8'
    $updated = $updated -replace '(?m)^;\s*extension\s*=\s*oci8_19\s*', 'extension=oci8_19 '

    if ($updated -notmatch '(?m)^\s*extension\s*=\s*oci8_19') {
        $updated = $updated.TrimEnd() + "`r`nextension=oci8_19`r`n"
    }

    if ($updated -ne $content) {
        Set-Content -Path $IniPath -Value $updated -NoNewline
        Write-Step "Enabled extension=oci8_19 in $IniPath"
    }
}

function Get-InstantClientPath {
    if ($env:ORACLE_INSTANT_CLIENT -and (Test-Path "$env:ORACLE_INSTANT_CLIENT\oci.dll")) {
        return $env:ORACLE_INSTANT_CLIENT
    }

    $default = 'C:\oracle\instantclient_19_31'
    if (Test-Path "$default\oci.dll") {
        return $default
    }

    return $default
}

function Install-InstantClient($TargetDir) {
    $zipUrl = 'https://download.oracle.com/otn_software/nt/instantclient/1931000/instantclient-basic-windows.x64-19.31.0.0.0dbru.zip'
    $zipFile = Join-Path $env:TEMP 'instantclient-basic.zip'

    Write-Step 'Downloading Oracle Instant Client 19 (Basic, 64-bit)...'
    Invoke-WebRequest -Uri $zipUrl -OutFile $zipFile -UseBasicParsing

    Write-Step "Extracting to $TargetDir ..."
    New-Item -ItemType Directory -Force -Path 'C:\oracle' | Out-Null
    Expand-Archive -Path $zipFile -DestinationPath 'C:\oracle' -Force

    $extracted = Get-ChildItem 'C:\oracle' -Directory | Where-Object { $_.Name -like 'instantclient_*' } | Sort-Object Name -Descending | Select-Object -First 1
    if (-not $extracted) {
        throw 'Instant Client extraction failed.'
    }

    if ($extracted.FullName -ne $TargetDir) {
        if (Test-Path $TargetDir) {
            Remove-Item $TargetDir -Recurse -Force
        }
        Move-Item $extracted.FullName $TargetDir
    }

    Remove-Item $zipFile -Force -ErrorAction SilentlyContinue
}

function Copy-InstantClientToPhp($InstantClientDir, $PhpDir) {
    if (Test-Path "$PhpDir\oci.dll") {
        Write-Step 'Oracle DLLs already present in PHP folder — skipping copy'
        return
    }

    Write-Step "Copying Instant Client DLLs to $PhpDir ..."
    Copy-Item "$InstantClientDir\*.dll" $PhpDir -Force
}

Write-Step 'ScrumLab Oracle PHP setup (Windows)'

if ($PSVersionTable.PSPlatform -eq 'Unix') {
    throw 'This script is for Windows + XAMPP. On Linux/Mac, install php-oci8 and Oracle Instant Client via your package manager.'
}

$phpExe = (Get-Command php -ErrorAction Stop).Source
$phpDir = Split-Path $phpExe -Parent
$iniPath = Get-PhpIniPath

Write-Step "PHP: $phpExe"
Write-Step "php.ini: $iniPath"

if (Test-Oci8Loaded) {
    Write-Host 'oci8 is already loaded. Nothing to do.' -ForegroundColor Green
    exit 0
}

Enable-Oci8Extension $iniPath

$instantClient = Get-InstantClientPath
if (-not (Test-Path "$instantClient\oci.dll")) {
    Install-InstantClient $instantClient
}

Copy-InstantClientToPhp $instantClient $phpDir

if (-not (Test-Oci8Loaded)) {
    Write-Host ''
    Write-Host 'oci8 still not loaded. Also install Visual C++ Redistributable:' -ForegroundColor Yellow
    Write-Host 'https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist' -ForegroundColor Yellow
    throw 'Oracle PHP setup failed. See messages above.'
}

Write-Host ''
Write-Host 'Success: oci8 is ready. Use php artisan migrate and php artisan serve like MySQL.' -ForegroundColor Green
