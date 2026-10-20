#!/bin/bash

export NOME_PARTNER="sogei"
export PARTNER_TAG=${NOME_PARTNER:0:3}
export API_LISTEN_PORT=8083
export VIEWER_LISTEN_PORT=8093
export NGINX_SERVICE=$PARTNER_TAG-pgv
export PHP_SERVICE=$PARTNER_TAG-php
export VIEWER_SERVICE=$PARTNER_TAG-viewer

# Log di creazione: tutto l'output (stdout e stderr) viene salvato anche su file
LOG_DIR="$(pwd)/logs"
mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/create_$(date +%Y%m%d_%H%M%S).log"
exec > >(tee -a "$LOG_FILE") 2>&1
export BUILDKIT_PROGRESS=plain

# Token GitHub per composer: da sogei/.env (GITHUB_TOKEN=...), altrimenti dall'ambiente.
# sed toglie eventuali CRLF se il file e' stato salvato da Windows
if [ -f .env ]; then
    set -a
    . <(sed 's/\r$//' .env)
    set +a
fi
if [ -z "$GITHUB_TOKEN" ]; then
    echo "GITHUB_TOKEN non impostato: aggiungere GITHUB_TOKEN=... in sogei/.env"
    exit 1
fi

step() {
    echo
    echo "=================================================================="
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
    echo "=================================================================="
}

START_TS=$(date +%s)

step "CREAZIONE IMMAGINI DOCKER DI PRODUZIONE - partner: $NOME_PARTNER"
echo "Host:            $(hostname)"
echo "Utente:          $(whoami)"
echo "Sistema:         $(uname -srm)"
echo "Docker:          $(docker --version)"
echo "Docker Compose:  $(docker-compose version --short 2>/dev/null)"
echo "Branch Git:      $(git rev-parse --abbrev-ref HEAD 2>/dev/null)"
echo "Commit Git:      $(git log -1 --format='%h %s (%ad)' --date=iso 2>/dev/null)"
echo "Modifiche locali: $(git status --porcelain 2>/dev/null | wc -l) file"
echo "Servizi:         $PHP_SERVICE, $NGINX_SERVICE, $VIEWER_SERVICE"
echo "Porte:           API $API_LISTEN_PORT, Viewer $VIEWER_LISTEN_PORT"
echo "File di log:     $LOG_FILE"
echo "composer.lock SHA1: $(sha1sum ../composer.lock | cut -d' ' -f1)"
echo "Librerie pgvirtual (da composer.lock):"
grep -A5 '"name": "pgvirtual/' ../composer.lock | grep -E '"name"|"version"|"reference"' \
    | sed -E 's/.*"(name|version|reference)": "([^"]+)".*/\2/' | paste - - - \
    | awk '{print "  " $1 "  " $2 "  commit " $3}'

step "Impostazione permessi script"
./imposta-permessi.sh

step "Configurazione nginx (fastcgi_pass -> ${PHP_SERVICE}:9000)"
sed -i -E "s/fastcgi_pass\s+[^;]+:9000;/fastcgi_pass ${PHP_SERVICE}:9000;/g" ./nginx/default.conf
sed -i -E "s/fastcgi_pass\s+[^;]+:9000;/fastcgi_pass ${PHP_SERVICE}:9000;/g" ../rootfs/etc/nginx/nginx.conf
grep -H "fastcgi_pass" ./nginx/default.conf ../rootfs/etc/nginx/nginx.conf

step "Generazione file docker-compose da template"
envsubst < docker-compose-template.yaml > docker-compose-create.yml
envsubst < docker-compose-server-template.yaml > docker-compose-server.yml
# contenuto nel log senza il token GitHub
echo "--- docker-compose-create.yml ---"
sed "s/$GITHUB_TOKEN/***/g" docker-compose-create.yml
echo "--- docker-compose-server.yml ---"
sed "s/$GITHUB_TOKEN/***/g" docker-compose-server.yml

step "Build e avvio container"
docker-compose -f docker-compose-create.yml up -d --build
BUILD_RC=$?
echo "Esito build: $BUILD_RC"

#until [ "`docker inspect -f {{.State.Running}} pgvmysql`"=="true" ]; do
#    sleep 1;
#done;

step "Container avviati"
docker-compose -f docker-compose-create.yml ps
#docker exec -it  $PHP_SERVICE php /var/www/html/artisan migrate --seed

step "Commit dei container in immagini"
docker commit $PHP_SERVICE $PHP_SERVICE:latest
docker commit $NGINX_SERVICE $NGINX_SERVICE:latest

step "Esportazione immagini in archivi tar"
mkdir -p images && cd images || exit 1
docker save -o $PHP_SERVICE.tar $PHP_SERVICE:latest
docker save -o $NGINX_SERVICE.tar $NGINX_SERVICE:latest

step "RIEPILOGO"
echo "Immagini:"
for img in $PHP_SERVICE $NGINX_SERVICE; do
    docker image ls "$img:latest" --format '  {{.Repository}}:{{.Tag}}  id={{.ID}}  size={{.Size}}  created={{.CreatedAt}}'
done
echo "Archivi esportati ($(pwd)):"
ls -lh $PHP_SERVICE.tar $NGINX_SERVICE.tar | awk '{print "  " $9 "  " $5}'
echo "Durata totale: $(( $(date +%s) - START_TS )) secondi"
echo "Log salvato in: $LOG_FILE"
