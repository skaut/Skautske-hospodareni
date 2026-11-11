FROM webwingscz/builder-hskauting-php85-node22

# The image already has git, rsync, openssh-client, curl, tar and gzip; only Deployer is missing.
RUN curl -fsSL https://deployer.org/releases/v7.5.12/deployer.phar -o /usr/local/bin/dep \
    && chmod +x /usr/local/bin/dep
