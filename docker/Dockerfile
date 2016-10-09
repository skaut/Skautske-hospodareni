FROM debian:jessie

MAINTAINER Frantisek Masa <masa@egen.cz>

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update

# Install base tools
RUN apt-get install curl git -y

# Install MySQL
RUN apt-get install mysql-server -y

RUN service mysql start && mysql -u root -e "FLUSH PRIVILEGES; SET PASSWORD FOR 'root'@'localhost' = PASSWORD(''); CREATE DATABASE hskauting"

# Install PHP (5.4)
RUN apt-get install php5 php5-cli php5-mysql php5-sqlite php5-gd php-pear php-apc php5-curl -y

COPY php.ini /etc/php5/apache2/

# Install apache
RUN apt-get install apache2  libapache2-mod-php5 -y

RUN a2enmod php5
RUN a2enmod rewrite
RUN a2enmod ssl

RUN usermod -u 1000 www-data # Now www-data (Apache) is owner of app files

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

ADD run.sh /run.sh

RUN chmod 755 /*.sh

VOLUME  ["/etc/mysql", "/var/lib/mysql"]

EXPOSE 80 9000 443
CMD ["/run.sh"]
