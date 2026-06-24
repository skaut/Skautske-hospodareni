FROM php:8.3-cli
RUN apt-get update && apt-get install -y --no-install-recommends \
      ca-certificates curl git openssh-client rsync tar gzip \
    && rm -rf /var/lib/apt/lists/*
# optional: přidat dep do image
RUN curl -fsSL https://deployer.org/releases/v7.5.12/deployer.phar -o /usr/local/bin/dep \
 && chmod +x /usr/local/bin/dep
