#!/bin/bash
export NOME_PARTNER="localstart"
export PARTNER_TAG=pgv
export API_LISTEN_PORT=8080
export VIEWER_LISTEN_PORT=8090
export NGINX_SERVICE=pgv
export PHP_SERVICE=$PARTNER_TAG-php
export VIEWER_SERVICE=$PARTNER_TAG-viewer

docker-compose up -d --build
echo 'Waiting for containers to start...'
until [ "`docker inspect -f {{.State.Running}} pgvmysql`"=="true" ]; do
    sleep 1;
done;

until [ "`docker inspect -f {{.State.Running}} pgv-php`"=="true" ]; do
    sleep 1;
done;
sleep 20;

echo 'Containers started.'
docker exec -it  pgv-php php /var/www/html/artisan migrate




#docker exec -it  pgv-php php /var/www/html/artisan db:seed --class=ManagerUsersSeeder
#docker exec -it  pgv-php php /var/www/html/artisan pg:generateEvents --today
