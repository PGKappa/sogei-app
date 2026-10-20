FROM php:8.2-fpm-alpine AS server

LABEL Maintainer="Guides4you srls" \
    Description="Lightweight container with Nginx & PHP-FPM based on Alpine Linux."

# Pacchetti di runtime (le librerie -dev servono solo in compilazione, vedi sotto)
RUN apk add --no-cache \
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
    libintl \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    libxml2 \
    sqlite-libs \
    libmcrypt \
    python3 \
    py3-scipy \
    py3-cffi

# Estensioni PHP: compilatore e header vengono rimossi nello stesso layer.
# curl, pdo e xml sono gia' incluse nell'immagine php ufficiale.
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    sqlite-dev \
    openssl-dev \
    curl-dev \
    libmcrypt-dev \
    py3-pip \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
      bcmath \
      pdo_mysql \
      pdo_sqlite \
      pcntl \
      intl \
      zip \
      gd \
  && pecl install mcrypt \
  && docker-php-ext-enable mcrypt \
  # drs nel python di sistema (/usr/bin/python3, usato da drs_exec.py):
  # numpy, scipy e cffi arrivano da apk
  && pip install --no-cache-dir --no-deps --break-system-packages drs \
  && apk del .build-deps \
  && rm -rf /tmp/pear /usr/src/php.tar.xz* ~/.cache

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
    && mv /tmp/envsubst /usr/local/bin/

WORKDIR /var/www/html

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

# composer gira sulla stessa PHP 8.2 di produzione (stage "server"): l'immagine
# composer ufficiale usa l'ultima PHP e Laravel genera warning di deprecazione
FROM server AS composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

# token GitHub per i repository privati PGKappa/sogei-* (da sogei/.env).
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
RUN test -n "$GITHUB_TOKEN" || (echo "GITHUB_TOKEN mancante: compilare sogei/.env" && exit 1)
# installa esattamente le versioni di composer.lock (platform.php 8.2 e' fissato in composer.json)
RUN test -f composer.lock || (echo "composer.lock mancante" && exit 1)
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
# i run dei servizi runit puntano a scripts/*.sh: con CRLF lo shebang diventa "/bin/sh\r"
# e runsv fallisce con "unable to start ./run: file does not exist"
RUN sed -i 's/\r$//' /var/www/html/scripts/*.sh \
    && chmod +x /var/www/html/scripts/*.sh

EXPOSE 9000
