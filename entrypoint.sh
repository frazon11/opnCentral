#!/bin/sh
set -eu
mkdir -p /var/www/data /var/www/backups
chown -R www-data:www-data /var/www/data /var/www/backups
exec "$@"
