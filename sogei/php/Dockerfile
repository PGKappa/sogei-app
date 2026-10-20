FROM php:8.2-fpm-alpine AS server

LABEL Maintainer="Guides4you srls" \
    Description="Lightweight container with Nginx & PHP-FPM based on Alpine Linux."

# Install packages
RUN apk update && apk --no-cache add \
    bash \
    curl \
    git \
    busybox \
    grep \
    fcgi \
    runit \
    mysql-client \
    postgresql-libs \
    icu-libs \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    libxml2 \
    sqlite-libs \
    python3 \
    py3-pip \
    py3-scipy \
  && apk --no-cache add --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    sqlite-dev \
    openssl-dev \
    build-base \
  && rm -rf /var/cache/apk/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
      bcmath \
      pdo \
      pdo_mysql \
      pdo_sqlite \
      pcntl \
      xml \
      intl \
      zip \
      gd 


    # Bring in gettext so we can get `envsubst`, then throw
    # the rest away. To do this, we need to install `gettext`
    # then move `envsubst` out of the way so `gettext` can
    # be deleted completely, then move `envsubst` back.
RUN apk add --no-cache --virtual .gettext gettext \
    && mv /usr/bin/envsubst /tmp/ \
    && runDeps="$( \
    scanelf --needed --nobanner /tmp/envsubst \
    | awk '{ gsub(/,/, "\nso:", $2); print "so:" $2 }' \
    | sort -u \
    | xargs -r apk info --installed \
    | sort -u \
    )" \
    && apk add --no-cache $runDeps \
    && apk del .gettext \
    && mv /tmp/envsubst /usr/local/bin/ \
    # Remove alpine cache
    && rm -rf /var/cache/apk/* 

RUN apk add bash build-base gcc wget git autoconf libmcrypt-dev libzip-dev zip \
    g++ make openssl-dev


RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    curl-dev \
    #imagemagick-dev \
    libtool \
    libxml2-dev \
    sqlite-dev

RUN apk add --no-cache \
    curl \
    git \
    #imagemagick \
    mysql-client \
    postgresql-libs \
    libintl \
    icu \
    icu-dev \
    libzip-dev \
    python3 \
    py3-pip \
    py3-scipy
# RUN pecl install imagick-3.4.4

WORKDIR /var/www/html
RUN python -m venv .venv
RUN chmod -R 777 .venv
RUN . .venv/bin/activate
RUN /var/www/html/.venv/bin/python -m pip install drs
# la versione di python dipende dalla release alpine dell'immagine base
RUN SITE_PACKAGES="$(python3 -c 'import sysconfig; print(sysconfig.get_paths()["purelib"])')" \
    && cp -R /var/www/html/.venv/lib/python3*/site-packages/drs "$SITE_PACKAGES/"

RUN pecl install mcrypt && \
    docker-php-ext-enable mcrypt

#RUN docker-php-ext-enable imagick
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install curl
# RUN docker-php-ext-install iconv
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install pdo_sqlite
RUN docker-php-ext-install pcntl
# RUN docker-php-ext-install tokenizer
RUN docker-php-ext-install xml
RUN docker-php-ext-install zip
RUN docker-php-ext-install intl

RUN git config --global --add safe.directory /var/www/html/vendor/pgvirtual/manager
RUN git config --global --add safe.directory /var/www/html/vendor/pgvirtual/game-dogs
ARG NOME_PARTNER
COPY ./${NOME_PARTNER}/php/rootfs/ /
# build da Windows: rimuove eventuali CRLF (errore "/bin/sh^M: bad interpreter")
RUN find /bin/docker-entrypoint.sh /bin/php-fpm-healthcheck.sh /docker-entrypoint-init.d -type f \
      -exec sed -i 's/\r$//' {} + \
    && chmod +x /bin/docker-entrypoint.sh /bin/php-fpm-healthcheck.sh /docker-entrypoint-init.d/*.sh
# Let runit start nginx & php-fpm



ENV client_max_body_size=2M \
    clear_env=no \
    allow_url_fopen=On \
    allow_url_include=Off \
    display_errors=Off \
    file_uploads=On \
    max_execution_time=0 \
    max_input_time=-1 \
    max_input_vars=1000 \
    memory_limit=128M \
    post_max_size=8M \
    upload_max_filesize=2M \
    zlib_output_compression=On

FROM composer AS composer

# token GitHub per i repository privati PGKappa/sogei-* (da sogei/.env.docker).
# Resta in questo stage: l'immagine finale parte da "server" e non lo contiene.
ARG GITHUB_TOKEN

# copying the source directory and install the dependencies with composer
COPY . /app
# bootstrap/cache e storage/framework/{sessions,views} sono in .gitignore: artisan li richiede
RUN mkdir -p /app/bootstrap/cache \
    /app/storage/framework/cache/data \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs \
  && chmod -R 777 /app/bootstrap/cache /app/storage
# run composer install to install the dependencies
RUN test -n "$GITHUB_TOKEN" || (echo "GITHUB_TOKEN mancante: compilare sogei/.env.docker" && exit 1)
# l'immagine composer usa l'ultima PHP: risolvi le dipendenze per la PHP dello stage "server"
RUN composer config platform.php 8.2
RUN COMPOSER_AUTH="{\"github-oauth\":{\"github.com\":\"$GITHUB_TOKEN\"}}" composer install \
    --optimize-autoloader \
    --no-interaction \
    --no-progress
# i pacchetti pgvirtual/* non hanno export-ignore: togli test e file di CI/git dal vendor
RUN cd /app/vendor/pgvirtual \
  && rm -rf */tests */ci */.gitlab */.gitlab-ci.yml */.gitignore

# continue stage build with the desired image and copy the source including the
# dependencies downloaded by composer
FROM server
COPY --from=composer /app /var/www/html/

EXPOSE 9000
