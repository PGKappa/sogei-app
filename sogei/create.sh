#!/bin/bash

export NOME_PARTNER="sogei"
export PARTNER_TAG=${NOME_PARTNER:0:3}
export API_LISTEN_PORT=8083
export VIEWER_LISTEN_PORT=8093
export NGINX_SERVICE=$PARTNER_TAG-pgv
export PHP_SERVICE=$PARTNER_TAG-php
export VIEWER_SERVICE=$PARTNER_TAG-viewer

# Log di creazione in logs/
LOG_DIR="$(pwd)/logs"
mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/create_$(date +%Y%m%d_%H%M%S).log"
exec > >(tee -a "$LOG_FILE") 2>&1

# docker-compose 1.x: build con il builder interno, senza avvisi Python di deprecazione
export COMPOSE_DOCKER_CLI_BUILD=0
export PYTHONWARNINGS=ignore

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

# Esegue un passaggio registrandone inizio ed esito; in caso di errore interrompe la creazione.
# Nel log finiscono solo gli eventuali messaggi di errore (stderr)
run() {
    local descrizione="$1"
    shift
    log "$descrizione - inizio"
    if "$@" >/dev/null; then
        log "$descrizione - completato"
    else
        log "$descrizione - ERRORE"
        log "Creazione interrotta"
        exit 1
    fi
}

# Token GitHub per composer: da sogei/.env (GITHUB_TOKEN=...), altrimenti dall'ambiente.
# sed toglie eventuali CRLF se il file e' stato salvato da Windows
if [ -f .env ]; then
    set -a
    . <(sed 's/\r$//' .env)
    set +a
fi
if [ -z "$GITHUB_TOKEN" ]; then
    log "GITHUB_TOKEN non impostato: aggiungere GITHUB_TOKEN=... in sogei/.env"
    exit 1
fi

START_TS=$(date +%s)

log "Inizio creazione immagini Docker di produzione - partner: $NOME_PARTNER"
echo "  Host:           $(hostname)"
echo "  Sistema:        $(uname -srm)"
echo "  Docker:         $(docker --version)"
echo "  Docker Compose: $(docker-compose version --short)"
echo "  Librerie pgvirtual:"
grep -A5 '"name": "pgvirtual/' ../composer.lock | grep -E '"name"|"version"|"reference"' \
    | sed -E 's/.*"(name|version|reference)": "([^"]+)".*/\2/' | paste - - - \
    | awk '{print "    " $1 "  " $2 "  commit " $3}'

run "Impostazione permessi script" ./imposta-permessi.sh

log "Configurazione nginx (fastcgi_pass ${PHP_SERVICE}:9000)"
sed -i -E "s/fastcgi_pass\s+[^;]+:9000;/fastcgi_pass ${PHP_SERVICE}:9000;/g" ./nginx/default.conf
sed -i -E "s/fastcgi_pass\s+[^;]+:9000;/fastcgi_pass ${PHP_SERVICE}:9000;/g" ../rootfs/etc/nginx/nginx.conf

log "Generazione file docker-compose da template"
envsubst < docker-compose-template.yaml > docker-compose-create.yml
envsubst < docker-compose-server-template.yaml > docker-compose-server.yml

run "Creazione immagine $PHP_SERVICE" docker-compose -f docker-compose-create.yml build -q $PHP_SERVICE
run "Creazione immagine $NGINX_SERVICE" docker-compose -f docker-compose-create.yml build -q $NGINX_SERVICE
run "Avvio container" docker-compose -f docker-compose-create.yml up -d

#until [ "`docker inspect -f {{.State.Running}} pgvmysql`"=="true" ]; do
#    sleep 1;
#done;

#docker exec -it  $PHP_SERVICE php /var/www/html/artisan migrate --seed

run "Commit container $PHP_SERVICE" docker commit $PHP_SERVICE $PHP_SERVICE:latest
run "Commit container $NGINX_SERVICE" docker commit $NGINX_SERVICE $NGINX_SERVICE:latest

mkdir -p images && cd images || exit 1
run "Esportazione $PHP_SERVICE.tar" docker save -o $PHP_SERVICE.tar $PHP_SERVICE:latest
run "Esportazione $NGINX_SERVICE.tar" docker save -o $NGINX_SERVICE.tar $NGINX_SERVICE:latest

log "Creazione completata in $(( $(date +%s) - START_TS )) secondi"
for img in $PHP_SERVICE $NGINX_SERVICE; do
    docker image ls "$img:latest" --format '  {{.Repository}}:{{.Tag}}  size={{.Size}}'
done
