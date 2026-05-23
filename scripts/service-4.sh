#!/bin/sh


php /var/www/html/artisan pg:syncResults
exec echo "Servizio Sync ISIBET avviato"