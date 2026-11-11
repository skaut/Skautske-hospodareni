# Web server as on the h.skauting.cz hosting: Apache 2.4 from Debian 13 (trixie), PHP-FPM through
# mod_proxy_fcgi, the application's www/.htaccess applies. TLS stays outside (Traefik in development).
FROM debian:trixie-slim AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends apache2 \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod proxy_fcgi setenvif rewrite headers unique_id http2 \
    && a2dissite 000-default \
    && ln -sfT /dev/stderr /var/log/apache2/error.log \
    && ln -sfT /dev/stdout /var/log/apache2/access.log \
    && ln -sfT /dev/stdout /var/log/apache2/other_vhosts_access.log \
    && mkdir -p /app/www

# PHP-FPM addresses; the request goes to the Xdebug one when the browser sends XDEBUG_SESSION=PHPSTORM.
ENV PHP_FPM_ADDRESS=php:9000 \
    PHP_FPM_XDEBUG_ADDRESS=php-xdebug:9000

COPY app.conf /etc/apache2/sites-available/app.conf
COPY apache2-foreground /usr/local/bin/apache2-foreground
RUN chmod +x /usr/local/bin/apache2-foreground \
    && a2ensite app \
    && apache2ctl configtest

EXPOSE 80
STOPSIGNAL SIGWINCH
CMD ["apache2-foreground"]

FROM base AS ci

COPY --from=application /app/www /app/www
