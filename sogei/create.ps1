# Versione Windows (PowerShell) di create.sh
# Uso: powershell -ExecutionPolicy Bypass -File .\create.ps1

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

$env:NOME_PARTNER = "sogei"
$env:PARTNER_TAG = $env:NOME_PARTNER.Substring(0, [Math]::Min(3, $env:NOME_PARTNER.Length))
$env:API_LISTEN_PORT = "8083"
$env:VIEWER_LISTEN_PORT = "8093"
$env:NGINX_SERVICE = "$($env:PARTNER_TAG)-pgv"
$env:PHP_SERVICE = "$($env:PARTNER_TAG)-php"
$env:VIEWER_SERVICE = "$($env:PARTNER_TAG)-viewer"

# Scrive in UTF-8 senza BOM, mantenendo i fine riga originali
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

# Log di creazione: tutto l'output viene salvato anche su file
$LogDir = Join-Path $PSScriptRoot "logs"
if (-not (Test-Path $LogDir)) { New-Item -ItemType Directory -Path $LogDir | Out-Null }
$LogFile = Join-Path $LogDir ("create_{0}.log" -f (Get-Date -Format "yyyyMMdd_HHmmss"))
$env:BUILDKIT_PROGRESS = "plain"

function Write-Log([string]$text = "") {
    Write-Host $text
    [System.IO.File]::AppendAllText($LogFile, "$text`r`n", $utf8NoBom)
}

function Write-Step([string]$title) {
    Write-Log
    Write-Log ("=" * 66)
    Write-Log ("[{0}] {1}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $title)
    Write-Log ("=" * 66)
}

# Esegue un comando esterno registrandone l'output (stdout e stderr) nel log.
# Docker scrive l'avanzamento su stderr: con "Stop" PowerShell lo tratterebbe come errore.
function Invoke-Logged([scriptblock]$command) {
    $previous = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    try {
        & $command 2>&1 | ForEach-Object { Write-Log "$_" }
    } finally {
        $ErrorActionPreference = $previous
    }
    return $LASTEXITCODE
}

# Output di un comando esterno come singola riga (vuota se il comando non esiste)
function Get-CommandOutput([scriptblock]$command) {
    try { ((& $command 2>$null) -join " ").Trim() } catch { "" }
}

$StartTime = Get-Date

# Credenziali da sogei/.env (come create.sh): GITHUB_TOKEN obbligatorio,
# DOCKER_USERNAME/DOCKER_PASSWORD opzionali. Se il file manca si usano le variabili d'ambiente
$envFile = Join-Path $PSScriptRoot ".env"
if (Test-Path $envFile) {
    foreach ($line in [System.IO.File]::ReadAllLines($envFile)) {
        if ($line -match '^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*?)\s*$') {
            $value = $matches[2] -replace '^"(.*)"$', '$1' -replace "^'(.*)'$", '$1'
            [Environment]::SetEnvironmentVariable($matches[1], $value, "Process")
        }
    }
}
if (-not $env:GITHUB_TOKEN) { throw "GITHUB_TOKEN non impostato: aggiungere GITHUB_TOKEN=... in sogei\.env" }

Write-Step "CREAZIONE IMMAGINI DOCKER DI PRODUZIONE - partner: $($env:NOME_PARTNER)"
$os = (Get-CimInstance Win32_OperatingSystem)
Write-Log "Host:            $env:COMPUTERNAME"
Write-Log "Utente:          $env:USERNAME"
Write-Log "Sistema:         $($os.Caption) $($os.Version)"
Write-Log "PowerShell:      $($PSVersionTable.PSVersion)"
Write-Log "Docker:          $(Get-CommandOutput { docker --version })"
Write-Log "Docker Compose:  $(Get-CommandOutput { docker-compose version --short })"
Write-Log "Branch Git:      $(Get-CommandOutput { git rev-parse --abbrev-ref HEAD })"
Write-Log "Commit Git:      $(Get-CommandOutput { git log -1 --format='%h %s (%ad)' --date=iso })"
Write-Log "Modifiche locali: $(@(git status --porcelain 2>$null).Count) file"
Write-Log "Servizi:         $($env:PHP_SERVICE), $($env:NGINX_SERVICE), $($env:VIEWER_SERVICE)"
Write-Log "Porte:           API $($env:API_LISTEN_PORT), Viewer $($env:VIEWER_LISTEN_PORT)"
Write-Log "File di log:     $LogFile"
$composerLock = Join-Path $PSScriptRoot "..\composer.lock"
Write-Log "composer.lock SHA1: $((Get-FileHash $composerLock -Algorithm SHA1).Hash.ToLower())"
Write-Log "Librerie pgvirtual (da composer.lock):"
$lock = [System.IO.File]::ReadAllText((Resolve-Path $composerLock).Path) | ConvertFrom-Json
foreach ($pkg in @($lock.packages) + @($lock.'packages-dev')) {
    if ($pkg.name -like "pgvirtual/*") {
        Write-Log "  $($pkg.name)  $($pkg.version)  commit $($pkg.source.reference)"
    }
}

if ($env:DOCKER_USERNAME -and $env:DOCKER_PASSWORD) {
    Write-Step "Login Docker Hub ($($env:DOCKER_USERNAME))"
    $rc = Invoke-Logged { $env:DOCKER_PASSWORD | docker login --username $env:DOCKER_USERNAME --password-stdin }
    if ($rc -ne 0) { throw "docker login fallito" }
}

# imposta-permessi.sh non serve su Windows (chmod +x non ha effetto)

function Set-FastcgiPass([string]$path) {
    $full = (Resolve-Path $path).Path
    $content = [System.IO.File]::ReadAllText($full)
    $content = [regex]::Replace($content, 'fastcgi_pass\s+[^;]+:9000;', "fastcgi_pass $($env:PHP_SERVICE):9000;")
    [System.IO.File]::WriteAllText($full, $content, $utf8NoBom)
    foreach ($m in [regex]::Matches($content, '.*fastcgi_pass.*')) {
        Write-Log "${path}: $($m.Value.Trim())"
    }
}

# Equivalente di envsubst: sostituisce ${VAR} con il valore della variabile d'ambiente
function Expand-Template([string]$template, [string]$output) {
    $content = [System.IO.File]::ReadAllText((Resolve-Path $template).Path)
    $content = [regex]::Replace($content, '\$\{(\w+)\}', {
        param($m)
        [Environment]::GetEnvironmentVariable($m.Groups[1].Value)
    })
    [System.IO.File]::WriteAllText((Join-Path $PSScriptRoot $output), $content, $utf8NoBom)
}

# Contenuto dei file generati nel log, senza il token GitHub
function Write-LogFile([string]$path) {
    Write-Log "--- $path ---"
    foreach ($line in [System.IO.File]::ReadAllLines((Resolve-Path $path).Path)) {
        if ($env:GITHUB_TOKEN) { $line = $line.Replace($env:GITHUB_TOKEN, "***") }
        Write-Log $line
    }
}

Write-Step "Configurazione nginx (fastcgi_pass -> $($env:PHP_SERVICE):9000)"
Set-FastcgiPass "./nginx/default.conf"
Set-FastcgiPass "../rootfs/etc/nginx/nginx.conf"

Write-Step "Generazione file docker-compose da template"
Expand-Template "docker-compose-template.yaml" "docker-compose-create.yml"
Expand-Template "docker-compose-server-template.yaml" "docker-compose-server.yml"
Write-LogFile "docker-compose-create.yml"
Write-LogFile "docker-compose-server.yml"

Write-Step "Build e avvio container"
$rc = Invoke-Logged { docker-compose -f docker-compose-create.yml up -d --build }
Write-Log "Esito build: $rc"
if ($rc -ne 0) { throw "docker-compose up fallito (log: $LogFile)" }

Write-Step "Container avviati"
Invoke-Logged { docker-compose -f docker-compose-create.yml ps } | Out-Null
#docker exec -it $env:PHP_SERVICE php /var/www/html/artisan migrate --seed

Write-Step "Commit dei container in immagini"
Invoke-Logged { docker commit $env:PHP_SERVICE "$($env:PHP_SERVICE):latest" } | Out-Null
Invoke-Logged { docker commit $env:NGINX_SERVICE "$($env:NGINX_SERVICE):latest" } | Out-Null

Write-Step "Esportazione immagini in archivi tar"
if (-not (Test-Path "./images")) {
    New-Item -ItemType Directory -Path "./images" | Out-Null
}
Set-Location "./images"
$rc = Invoke-Logged { docker save -o "$($env:PHP_SERVICE).tar" "$($env:PHP_SERVICE):latest" }
if ($rc -ne 0) { throw "docker save $($env:PHP_SERVICE) fallito" }
$rc = Invoke-Logged { docker save -o "$($env:NGINX_SERVICE).tar" "$($env:NGINX_SERVICE):latest" }
if ($rc -ne 0) { throw "docker save $($env:NGINX_SERVICE) fallito" }

Write-Step "RIEPILOGO"
Write-Log "Immagini:"
foreach ($img in @($env:PHP_SERVICE, $env:NGINX_SERVICE)) {
    Invoke-Logged { docker image ls "$($img):latest" --format '  {{.Repository}}:{{.Tag}}  id={{.ID}}  size={{.Size}}  created={{.CreatedAt}}' } | Out-Null
}
Write-Log "Archivi esportati ($((Get-Location).Path)):"
foreach ($img in @($env:PHP_SERVICE, $env:NGINX_SERVICE)) {
    $tar = Get-Item "$img.tar"
    Write-Log ("  {0}  {1:N0} MB" -f $tar.Name, ($tar.Length / 1MB))
}
Write-Log "Durata totale: $([int]((Get-Date) - $StartTime).TotalSeconds) secondi"
Write-Log "Log salvato in: $LogFile"
