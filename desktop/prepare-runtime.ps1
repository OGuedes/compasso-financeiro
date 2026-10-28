$ErrorActionPreference = 'Stop'

$runtimeDirectory = Join-Path $PSScriptRoot 'php-runtime'
$phpExecutable = Join-Path $runtimeDirectory 'php.exe'
$sqliteExtension = Join-Path $runtimeDirectory 'ext\php_pdo_sqlite.dll'

if ((Test-Path -LiteralPath $phpExecutable) -and (Test-Path -LiteralPath $sqliteExtension)) {
    Write-Host 'Runtime PHP já está preparado.'
    exit 0
}

$downloadUrl = 'https://downloads.php.net/~windows/releases/php-8.2.35-1-nts-Win32-vs16-x64.zip'
$archivePath = Join-Path $PSScriptRoot 'php-runtime.zip'

New-Item -ItemType Directory -Path $runtimeDirectory -Force | Out-Null
Write-Host 'Baixando o runtime oficial do PHP para Windows...'
Invoke-WebRequest -Uri $downloadUrl -OutFile $archivePath
Expand-Archive -LiteralPath $archivePath -DestinationPath $runtimeDirectory -Force
Remove-Item -LiteralPath $archivePath -Force

if (-not (Test-Path -LiteralPath $phpExecutable)) {
    throw 'O runtime PHP não foi preparado corretamente.'
}

Write-Host 'Runtime PHP preparado.'
