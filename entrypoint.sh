#!/bin/sh
set -eu
mkdir -p /var/www/data /var/www/backups
chown -R www-data:www-data /var/www/data /var/www/backups

if [ "${ALERTS_ENABLED:-false}" = "true" ]; then
    su -s /bin/sh www-data -c 'php /var/www/html/alert_worker.php' &
fi

exec "$@"
