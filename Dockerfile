FROM php:8.3-apache
RUN apt-get update \
 && apt-get install -y --no-install-recommends curl ca-certificates \
 && docker-php-ext-install pdo_sqlite \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*
COPY app/ /var/www/html/
COPY apache.conf /etc/apache2/conf-available/opnsense-central.conf
COPY entrypoint.sh /usr/local/bin/opnsense-central-entrypoint
RUN chmod +x /usr/local/bin/opnsense-central-entrypoint \
 && a2enconf opnsense-central \
 && mkdir -p /var/www/data /var/www/backups \
 && chown -R www-data:www-data /var/www/html /var/www/data /var/www/backups
ENTRYPOINT ["opnsense-central-entrypoint"]
CMD ["apache2-foreground"]
