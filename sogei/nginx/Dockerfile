FROM nginx:1.27-alpine
ARG NOME_PARTNER
# nginx serve solo gli statici di public/; i .php vanno a php-fpm via fastcgi_pass
COPY ./public /var/www/html/public
COPY ./${NOME_PARTNER}/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY ./rootfs/etc/nginx/nginx.conf /etc/nginx/nginx.conf
