FROM php:8.1

RUN apt update  \
    && apt install -yq git vim libpq-dev unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install postgreSQL PHP extensions
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pgsql pdo_pgsql \
    && docker-php-ext-enable pdo_pgsql

# Install symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash \
    && apt install symfony-cli \
    && mkdir /.symfony5 \
    && chown 1000:1000 -R /.symfony5

# Install composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

EXPOSE 8000
