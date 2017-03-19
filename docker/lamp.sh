#!/usr/bin/env bash

apt-get install -y libxml2-dev git zlib1g-dev --no-install-recommends

apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng12-dev \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd

docker-php-ext-install soap && docker-php-ext-install mysqli && docker-php-ext-install zip

# Install MySQL
apt-get install mysql-server -y

service mysql start && mysql -u root -e "FLUSH PRIVILEGES; SET PASSWORD FOR 'root'@'localhost' = PASSWORD(''); CREATE DATABASE hskauting"

a2enmod rewrite
a2enmod ssl

usermod -u 1000 www-data # Now www-data (Apache) is owner of app files

# Install composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
