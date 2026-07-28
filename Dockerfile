FROM php:8.5-apache-trixie

LABEL org.opencontainers.image.title="opnCentral"
LABEL org.opencontainers.image.description="Central manager for multiple OPNsense firewalls"
LABEL org.opencontainers.image.source="https://github.com/frazon11/opnCentral"
LABEL org.opencontainers.image.licenses="MIT"

ENV DEBIAN_FRONTEND=noninteractive

RUN set -eux; \
    apt-get update; \
    apt-get upgrade -y; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        libcurl4t64 \
        libsqlite3-0 \
        libcurl4-openssl-dev \
        libsqlite3-dev \
        libzip-dev; \
    docker-php-ext-install -j"$(nproc)" curl pdo_sqlite zip; \
    a2enmod rewrite headers; \
    apt-mark manual ca-certificates curl libcurl4t64 libsqlite3-0; \
    apt-get purge -y --auto-remove libcurl4-openssl-dev libsqlite3-dev libzip-dev; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY app/ /var/www/html/
COPY apache.conf /etc/apache2/conf-available/opnsense-central.conf
COPY entrypoint.sh /usr/local/bin/opnsense-central-entrypoint

RUN set -eux; \
    chmod +x /usr/local/bin/opnsense-central-entrypoint; \
    a2enconf opnsense-central; \
    mkdir -p /var/www/data /var/www/backups; \
    chown -R www-data:www-data /var/www/html /var/www/data /var/www/backups

WORKDIR /var/www/html

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=5 \
    CMD curl -fsS http://127.0.0.1/health.php || exit 1

ENTRYPOINT ["opnsense-central-entrypoint"]
CMD ["apache2-foreground"]
