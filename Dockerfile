FROM php:8.1

RUN apt update && apt install -yq git vim

# Install symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash && \
    apt install symfony-cli && \
    mkdir /.symfony5 && \
    chown 1000:1000 -R /.symfony5

# Install composer
RUN curl https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer | \
    php -- --quiet && \
    mv composer.phar /usr/local/bin/composer

EXPOSE 8000
