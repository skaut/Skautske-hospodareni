FROM webwingscz/builder-hskauting-php85-node22

# The hosting image has no user for the checkout and no Xdebug. The docker user (UID 1000) runs the PHP-FPM
# workers and CLI tools; Xdebug stays disabled until php-xdebug or the coverage run loads it.
RUN groupadd --gid 1000 docker \
    && useradd --uid 1000 --gid docker --create-home --shell /bin/bash docker \
    && install -d -o docker -g docker /app \
    && apt-get update \
    && apt-get install -y --no-install-recommends "php${PHP_VERSION}-xdebug" \
    && rm -rf /var/lib/apt/lists/* \
    && phpdismod -v "${PHP_VERSION}" -s ALL xdebug

ENV FPM_USER=docker \
    FPM_GROUP=docker \
    PATH="/app/vendor/bin:/app/bin:${PATH}"

WORKDIR /app
USER docker
