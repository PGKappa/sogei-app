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

# Log di creazione in logs\
$LogDir = Join-Path $PSScriptRoot "logs"
if (-not (Test-Path $LogDir)) { New-Item -ItemType Directory -Path $LogDir | Out-Null }
$LogFile = Join-Path $LogDir ("create_{0}.log" -f (Get-Date -Format "yyyyMMdd_HHmmss"))

# docker-compose 1.x: build con il builder interno, senza avvisi Python di deprecazione
$env:COMPOSE_DOCKER_CLI_BUILD = "0"
$env:PYTHONWARNINGS = "ignore"

function Write-Log([string]$text = "") {
    Write-Host $text
    [System.IO.File]::AppendAllText($LogFile, "$text`r`n", $utf8NoBom)
}

function Log([string]$text) {
    Write-Log ("[{0}] {1}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $text)
}

# Esegue un passaggio registrandone inizio ed esito; in caso di errore interrompe la creazione.
# Nel log finiscono solo gli eventuali messaggi di errore (stderr).
# Docker scrive su stderr: con "Stop" PowerShell lo tratterebbe come errore.
function Invoke-Step([string]$descrizione, [scriptblock]$command) {
    Log "$descrizione - inizio"
    $previous = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    try {
        & $command 2>&1 | Where-Object { $_ -is [System.Management.Automation.ErrorRecord] } | ForEach-Object { Write-Log "$_" }
    } finally {
        $ErrorActionPreference = $previous
    }
    if ($LASTEXITCODE -ne 0) {
        Log "$descrizione - ERRORE"
        Log "Creazione interrotta"
        exit 1
    }
    Log "$descrizione - completato"
}

# Output di un comando esterno come singola riga (vuota se il comando non esiste)
function Get-CommandOutput([scriptblock]$command) {
    try { ((& $command 2>$null) -join " ").Trim() } catch { "" }
}

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
if (-not $env:GITHUB_TOKEN) {
    Log "GITHUB_TOKEN non impostato: aggiungere GITHUB_TOKEN=... in sogei\.env"
    exit 1
}

$StartTime = Get-Date

Log "Inizio creazione immagini Docker di produzione - partner: $($env:NOME_PARTNER)"
$os = Get-CimInstance Win32_OperatingSystem
Write-Log "  Host:           $env:COMPUTERNAME"
Write-Log "  Sistema:        $($os.Caption) $($os.Version)"
Write-Log "  Docker:         $(Get-CommandOutput { docker --version })"
Write-Log "  Docker Compose: $(Get-CommandOutput { docker-compose version --short })"
$composerLock = Join-Path $PSScriptRoot "..\composer.lock"
Write-Log "  Librerie pgvirtual:"
$lock = [System.IO.File]::ReadAllText((Resolve-Path $composerLock).Path) | ConvertFrom-Json
foreach ($pkg in @($lock.packages) + @($lock.'packages-dev')) {
    if ($pkg.name -like "pgvirtual/*") {
        Write-Log "    $($pkg.name)  $($pkg.version)  commit $($pkg.source.reference)"
    }
}

if ($env:DOCKER_USERNAME -and $env:DOCKER_PASSWORD) {
    Invoke-Step "Login Docker Hub ($($env:DOCKER_USERNAME))" { $env:DOCKER_PASSWORD | docker login --username $env:DOCKER_USERNAME --password-stdin }
}

# imposta-permessi.sh non serve su Windows (chmod +x non ha effetto)

function Set-FastcgiPass([string]$path) {
    $full = (Resolve-Path $path).Path
    $content = [System.IO.File]::ReadAllText($full)
    $content = [regex]::Replace($content, 'fastcgi_pass\s+[^;]+:9000;', "fastcgi_pass $($env:PHP_SERVICE):9000;")
    [System.IO.File]::WriteAllText($full, $content, $utf8NoBom)
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

Log "Configurazione nginx (fastcgi_pass $($env:PHP_SERVICE):9000)"
Set-FastcgiPass "./nginx/default.conf"
Set-FastcgiPass "../rootfs/etc/nginx/nginx.conf"

Log "Generazione file docker-compose da template"
Expand-Template "docker-compose-template.yaml" "docker-compose-create.yml"
Expand-Template "docker-compose-server-template.yaml" "docker-compose-server.yml"

Invoke-Step "Creazione immagine $($env:PHP_SERVICE)" { docker-compose -f docker-compose-create.yml build -q $env:PHP_SERVICE }
Invoke-Step "Creazione immagine $($env:NGINX_SERVICE)" { docker-compose -f docker-compose-create.yml build -q $env:NGINX_SERVICE }
Invoke-Step "Avvio container" { docker-compose -f docker-compose-create.yml up -d }

#docker exec -it $env:PHP_SERVICE php /var/www/html/artisan migrate --seed

Invoke-Step "Commit container $($env:PHP_SERVICE)" { docker commit $env:PHP_SERVICE "$($env:PHP_SERVICE):latest" }
Invoke-Step "Commit container $($env:NGINX_SERVICE)" { docker commit $env:NGINX_SERVICE "$($env:NGINX_SERVICE):latest" }

if (-not (Test-Path "./images")) {
    New-Item -ItemType Directory -Path "./images" | Out-Null
}
Set-Location "./images"
Invoke-Step "Esportazione $($env:PHP_SERVICE).tar" { docker save -o "$($env:PHP_SERVICE).tar" "$($env:PHP_SERVICE):latest" }
Invoke-Step "Esportazione $($env:NGINX_SERVICE).tar" { docker save -o "$($env:NGINX_SERVICE).tar" "$($env:NGINX_SERVICE):latest" }

Log "Creazione completata in $([int]((Get-Date) - $StartTime).TotalSeconds) secondi"
foreach ($img in @($env:PHP_SERVICE, $env:NGINX_SERVICE)) {
    docker image ls "$($img):latest" --format '  {{.Repository}}:{{.Tag}}  size={{.Size}}' | ForEach-Object { Write-Log $_ }
}
