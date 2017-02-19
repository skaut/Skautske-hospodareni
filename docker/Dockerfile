FROM php:7.1-apache

MAINTAINER Frantisek Masa <masa@egen.cz>

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update

RUN apt-get install -y libxml2-dev git zlib1g-dev --no-install-recommends

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng12-dev \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd

RUN docker-php-ext-install soap && docker-php-ext-install mysqli && docker-php-ext-install zip

# Install MySQL
RUN apt-get install mysql-server -y

RUN service mysql start && mysql -u root -e "FLUSH PRIVILEGES; SET PASSWORD FOR 'root'@'localhost' = PASSWORD(''); CREATE DATABASE hskauting"

RUN a2enmod rewrite
RUN a2enmod ssl

RUN usermod -u 1000 www-data # Now www-data (Apache) is owner of app files

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

ADD run.sh /run.sh

RUN chmod 755 /*.sh

EXPOSE 80 9000 443
CMD ["/run.sh"]
