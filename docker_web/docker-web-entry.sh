#!/bin/bash
chown -R apache:apache /var/www/html/config /var/www/html/files /var/www/html/images /var/www/html/logs
mkdir -p /run/php-fpm
exec /usr/sbin/nginx -g 'daemon off;' -c /etc/nginx/nginx.conf | php-fpm
