#!/bin/sh

php /var/www/html/artisan pg:generateResults
#exec php artisan pg:TicketPaymentEngine --debug 
exec echo "Servizio GENERATE RESULTS avviato"