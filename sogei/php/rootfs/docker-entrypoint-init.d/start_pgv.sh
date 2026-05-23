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
php artisan key:generate
chown -R www-data:www-data /var/www/html
chmod +x /var/www/html/vendor/pgvirtual/game-dogs/src/Libraries/drs_exec.py 
chmod +x /var/www/html/vendor/pgvirtual/game-horses/src/Libraries/drs_exec.py 
chmod +x /var/www/html/vendor/pgvirtual/game-harness/src/Libraries/drs_exec.py 

mkdir -p /etc/service/result
mkdir -p /etc/service/isibet
mkdir -p /etc/service/syncresult
ln -s /var/www/html/scripts/service-1.sh /etc/service/result/run
ln -s /var/www/html/scripts/service-3.sh /etc/service/isibet/run
ln -s /var/www/html/scripts/service-4.sh /etc/service/syncresult/run

cd /var/www/html
echo "end pgv"




