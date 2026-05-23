#!/bin/bash

export NOME_PARTNER="sogei"
export PARTNER_TAG=${NOME_PARTNER:0:3}
export API_LISTEN_PORT=8083
export VIEWER_LISTEN_PORT=8093
export NGINX_SERVICE=$PARTNER_TAG-pgv
export PHP_SERVICE=$PARTNER_TAG-php
export VIEWER_SERVICE=$PARTNER_TAG-viewer

./imposta-permessi.sh

sed -i -E "s/fastcgi_pass\s+[^;]+:9000;/fastcgi_pass ${PHP_SERVICE}:9000;/g" ./nginx/default.conf

sed -i -E "s/fastcgi_pass\s+[^;]+:9000;/fastcgi_pass ${PHP_SERVICE}:9000;/g" ../rootfs/etc/nginx/nginx.conf

envsubst < docker-compose-template.yaml > docker-compose-create.yml

envsubst < docker-compose-server-template.yaml > docker-compose-server.yml

echo 'Waiting for containers to start...'

docker-compose -f docker-compose-create.yml up -d --build


#until [ "`docker inspect -f {{.State.Running}} pgvmysql`"=="true" ]; do
#    sleep 1;
#done;

echo 'Containers started.'
#docker exec -it  $PHP_SERVICE php /var/www/html/artisan migrate --seed

docker commit $PHP_SERVICE $PHP_SERVICE:latest
docker commit $NGINX_SERVICE $NGINX_SERVICE:latest
if [ ! -d "./images" ]; then
    mkdir images & cd images
else 
    cd images
fi
docker save -o $PHP_SERVICE.tar $PHP_SERVICE:latest
docker save -o $NGINX_SERVICE.tar $NGINX_SERVICE:latest
