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

# Credenziali da .env.docker (vedi .env.docker.example): GITHUB_TOKEN obbligatorio,
# DOCKER_USERNAME/DOCKER_PASSWORD opzionali
$envFile = Join-Path $PSScriptRoot ".env.docker"
if (-not (Test-Path $envFile)) {
    throw "File .env.docker mancante: copiare .env.docker.example in .env.docker e compilarlo"
}
foreach ($line in [System.IO.File]::ReadAllLines($envFile)) {
    if ($line -match '^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*?)\s*$') {
        $value = $matches[2] -replace '^"(.*)"$', '$1' -replace "^'(.*)'$", '$1'
        [Environment]::SetEnvironmentVariable($matches[1], $value, "Process")
    }
}
if (-not $env:GITHUB_TOKEN) { throw "GITHUB_TOKEN non impostato in .env.docker" }

if ($env:DOCKER_USERNAME -and $env:DOCKER_PASSWORD) {
    $env:DOCKER_PASSWORD | docker login --username $env:DOCKER_USERNAME --password-stdin
    if ($LASTEXITCODE -ne 0) { throw "docker login fallito" }
}

# imposta-permessi.sh non serve su Windows (chmod +x non ha effetto)

# Scrive in UTF-8 senza BOM, mantenendo i fine riga originali
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

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

Set-FastcgiPass "./nginx/default.conf"
Set-FastcgiPass "../rootfs/etc/nginx/nginx.conf"

Expand-Template "docker-compose-template.yaml" "docker-compose-create.yml"
Expand-Template "docker-compose-server-template.yaml" "docker-compose-server.yml"

Write-Host 'Waiting for containers to start...'

docker-compose -f docker-compose-create.yml up -d --build
if ($LASTEXITCODE -ne 0) { throw "docker-compose up fallito" }

Write-Host 'Containers started.'
#docker exec -it $env:PHP_SERVICE php /var/www/html/artisan migrate --seed

docker commit $env:PHP_SERVICE "$($env:PHP_SERVICE):latest"
docker commit $env:NGINX_SERVICE "$($env:NGINX_SERVICE):latest"

if (-not (Test-Path "./images")) {
    New-Item -ItemType Directory -Path "./images" | Out-Null
}
Set-Location "./images"

docker save -o "$($env:PHP_SERVICE).tar" "$($env:PHP_SERVICE):latest"
docker save -o "$($env:NGINX_SERVICE).tar" "$($env:NGINX_SERVICE):latest"
