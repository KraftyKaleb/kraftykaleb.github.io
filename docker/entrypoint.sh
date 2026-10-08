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

mkdir -p "$APP_DIR/var/cache" "$APP_DIR/var/log"
chown www-data:www-data "$APP_DIR/var" "$APP_DIR/var/cache" "$APP_DIR/var/log"

service php8.5-fpm start
exec nginx -g 'daemon off;'
