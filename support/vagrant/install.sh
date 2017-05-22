#!/bin/bash

MYSQL_PASSWORD="password"
MYSQL_USERNAME="herokuwp"

#
# End Config
#

echo "###############################"
echo "## Provisioning Heroku WP VM ##"
echo "###############################"

cd /app

#
# Add Nginx:PPA To Apt
#

add-apt-repository ppa:nginx/stable -y

#
# Update Package Manager
#

apt-get update -y

#
# Install PHP
#

apt-get install -y php7.0
apt-get install -y php7.0-gd
apt-get install -y php7.0-mysql

#
# Install MySQL
#

echo "mysql-server mysql-server/root_password password $MYSQL_PASSWORD" | \
  debconf-set-selections
echo "mysql-server mysql-server/root_password_again password $MYSQL_PASSWORD" | \
  debconf-set-selections

apt-get install -y mysql-server

echo "CREATE USER '$MYSQL_USERNAME'@'127.0.0.1' IDENTIFIED BY '$MYSQL_PASSWORD'" | \
  mysql -uroot "-p$MYSQL_PASSWORD"
echo "CREATE DATABASE herokuwp" | \
  mysql -uroot "-p$MYSQL_PASSWORD"
echo "GRANT ALL ON herokuwp.* TO '$MYSQL_USERNAME'@'127.0.0.1'" | \
  mysql -uroot "-p$MYSQL_PASSWORD"
echo "FLUSH PRIVILEGES" | \
  mysql -uroot "-p$MYSQL_PASSWORD"

#
# Install Nginx
#

apt-get install -y nginx

#
# Install Composer
#

curl -s -o /usr/local/bin/composer.phar https://getcomposer.org/composer.phar
chmod 755 /usr/local/bin/composer.phar
ln -s /usr/local/bin/composer.phar /usr/local/bin/composer

#
# Misc Utils
#

# Unzip needed to extract WP Core
apt-get install -y unzip

#
# Make Some Swap (1GB)
#

/bin/dd if=/dev/zero of=/var/swap bs=1M count=1024
chmod 600 /var/swap
/sbin/mkswap /var/swap
/sbin/swapon /var/swap

#
# Copy Config Files
#

cp -a /app/support/vagrant/root/* /

#
# Build Heroku-WP
#

sudo -H -u vagrant composer --working-dir=/app install

#
# Restart Services
#

/etc/init.d/php7.0-fpm restart
/etc/init.d/nginx restart

#
# Start Daemon To Rebuild On Change
#

start-stop-daemon \
  --start \
  --oknodo \
  --user root \
  --name rebuild \
  --pidfile /var/run/rebuild.pid \
  --startas /app/support/vagrant/rebuild \
  --chuid root \
  --make-pidfile /var/run/rebuild.pid \
  --background

#
# Stop Daemon Example:
#
# start-stop-daemon \
#   --stop \
#   --oknodo \
#   --user root \
#   --name rebuild \
#   --pidfile /var/run/rebuild.pid \
#   --retry 5
