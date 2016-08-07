#!/bin/bash
chown www-data:www-data /var/www/html/temp -R
chmod 777 /var/www/html/temp

touch /var/www/.bash_history
chown www-data:www-data /var/www/.bash_history

if [ "$ALLOW_OVERRIDE" = "**False**" ]; then
    unset ALLOW_OVERRIDE
else
    sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf
    a2enmod rewrite
fi

source /etc/apache2/envvars
tail -F /var/log/apache2/* &

service mysql start
exec apache2 -D FOREGROUND