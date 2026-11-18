#export COMPOSER_ROOT_VERSION=dev-main
#composer update
#composer install --no-ansi --no-dev --no-interaction --no-plugins --no-progress --no-scripts --optimize-autoloader
#composer require doctrine/dbal
#chmod +x /app/vendor/pgvirtual/game-dogs/src/Libraries/drs_exec.py
#cp -R /app/. /var/www/html 
echo "start pgv"

cd /var/www/html/
#php artisan key:generate
#chown -R www-data:www-data /var/www/html
#chmod +x /var/www/html/vendor/pgvirtual/game-dogs/src/Libraries/drs_exec.py
#cp .env.example .env
ls -l /var/www/html

# composer req pgvirtual/manager:dev-package-release
#php artisan vendor:publish --provider='PGVirtual\Manager\ManagerServiceProvider'

# php artisan migrate:fresh --seed --force
# php artisan migrate --seed

#php artisan pg:generateEvents --today

#php artisan pg:generateResults &
#php artisan pg:TicketPaymentEngine --debug
#sleep 10s
#php artisan pg:publishEventsToIsibet &
php-fpm --nodaemonize &
# APP_KEY fissa in .env.production: rigenerarla a ogni avvio invaliderebbe sessioni e dati cifrati
grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force
chown -R www-data:www-data /var/www/html
# storage e cache scrivibili anche se un comando artisan lanciato a mano come root crea file
chmod -R ug+rwX /var/www/html/storage /var/www/html/bootstrap/cache
chmod +x /var/www/html/vendor/pgvirtual/game-dogs/src/Libraries/drs_exec.py 
chmod +x /var/www/html/vendor/pgvirtual/game-horses/src/Libraries/drs_exec.py 
chmod +x /var/www/html/vendor/pgvirtual/game-harness/src/Libraries/drs_exec.py 

# servizi runit eseguiti come www-data (come php-fpm): se girano come root creano i log
# (laravel.log, laravel-AAAA-MM-GG.log) di root e php-fpm non riesce più a scriverli
# -> "Failed to open stream: Permission denied" su tutte le pagine.
# Le librerie dei giochi (sotto hash) scrivono results.json e rng.log con percorso relativo,
# cioè nella cartella di lavoro del processo: ogni servizio parte quindi da una sua cartella
# scrivibile da www-data, /var/www/html/storage/services/<servizio>/ (non /etc/service/<servizio>, di root).
add_service() {
  workdir="/var/www/html/storage/services/$1"
  mkdir -p "/etc/service/$1" "$workdir"
  chown www-data:www-data "$workdir"
  rm -f "/etc/service/$1/run"
  printf '#!/bin/sh\ncd %s || exit 1\nexec chpst -u www-data:www-data %s\n' "$workdir" "$2" > "/etc/service/$1/run"
  chmod +x "/etc/service/$1/run"
}
add_service result /var/www/html/scripts/service-1.sh
add_service isibet /var/www/html/scripts/service-3.sh
add_service syncresult /var/www/html/scripts/service-4.sh

cd /var/www/html
echo "end pgv"




