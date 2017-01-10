#!/bin/bash
TEMP_DIR=/var/www/html/nette-temp
chown www-data:www-data $TEMP_DIR -R
chmod 777 $TEMP_DIR

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
