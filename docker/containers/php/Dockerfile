FROM thecodingmachine/php:8.3-v4-fpm-node20

USER root
USER docker

RUN git config --global --add safe.directory /app

ENV PATH="/app/vendor/bin:/app/bin:${PATH}"
