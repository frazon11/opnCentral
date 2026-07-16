FROM php:8.3-apache

LABEL org.opencontainers.image.title="opnCentral"
LABEL org.opencontainers.image.description="Central manager for multiple OPNsense firewalls"
LABEL org.opencontainers.image.source="https://github.com/frazon11/opnCentral"
LABEL org.opencontainers.image.licenses="MIT"

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        libcurl4-openssl-dev \
        libsqlite3-dev \
    && docker-php-ext-install -j"$(nproc)" \
        curl \
        pdo_sqlite \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY app/ /var/www/html/
COPY apache.conf /etc/apache2/conf-available/opnsense-central.conf
COPY entrypoint.sh /usr/local/bin/opnsense-central-entrypoint

RUN chmod +x /usr/local/bin/opnsense-central-entrypoint \
    && a2enconf opnsense-central \
    && mkdir -p /var/www/data /var/www/backups \
    && chown -R www-data:www-data \
        /var/www/html \
        /var/www/data \
        /var/www/backups

WORKDIR /var/www/html

ENTRYPOINT ["opnsense-central-entrypoint"]
CMD ["apache2-foreground"]