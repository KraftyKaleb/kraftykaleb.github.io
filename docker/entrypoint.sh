#!/bin/sh
set -e

# The app is bind-mounted from the host, so server/var/ (cache, logs, rate limiter
# state) belongs to the host user that deployed it. Run nginx and php-fpm as that
# uid/gid so they can write there, and so the next deploy can still delete what
# they wrote. Skipped when the mount is owned by root (php-fpm refuses to run as root).
APP_DIR=/var/www/html/server
uid=$(stat -c %u "$APP_DIR")
gid=$(stat -c %g "$APP_DIR")
if [ "$uid" != 0 ] && [ "$gid" != 0 ]; then
    groupmod -o -g "$gid" www-data
    usermod -o -u "$uid" www-data
fi

mkdir -p "$APP_DIR/var/cache" "$APP_DIR/var/log" "$APP_DIR/var/uploads"
chown www-data:www-data "$APP_DIR/var" "$APP_DIR/var/cache" "$APP_DIR/var/log"
# The uploads volume keeps files across deploys; make sure php-fpm can write all of it.
chown -R www-data:www-data "$APP_DIR/var/uploads"

# Config from the bind mount, so changes to it ship without rebuilding the image.
cp /var/www/html/nginx.conf /etc/nginx/nginx.conf
cp /var/www/html/docker/php-uploads.ini /etc/php/8.5/fpm/conf.d/99-uploads.ini

service php8.5-fpm start
exec nginx -g 'daemon off;'
