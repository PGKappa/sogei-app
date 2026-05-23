FROM nginx
COPY . /var/www/html
ARG NOME_PARTNER
COPY ./${NOME_PARTNER}/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY ./rootfs/etc/nginx/nginx.conf /etc/nginx/nginx.conf 