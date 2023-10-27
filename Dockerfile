FROM php:8.1

RUN apt update  \
    && apt install -yq git vim libpq-dev libicu-dev unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install postgreSQL PHP extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions intl pdo_pgsql xsl opcache

# Install symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash \
    && apt install symfony-cli \
    && mkdir /.symfony5 \
    && chown 1000:1000 -R /.symfony5

# Install composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

EXPOSE 8000
