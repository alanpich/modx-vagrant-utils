#!/usr/bin/env bash
################################################################################
#  MODX LAMP Stack provisioning script
#
#    Author: Alan Pich <alan.pich@gmail.com>
#    Date:   June 2014
#
################################################################################


################################################################################
## Update aptitude and install core packages
#
sudo apt-get update
sudo apt-get install -y         \
    python-software-properties  \
    git                         \
    zip                         \
    unzip                       \
    curl                        \
    python-software-properties  \
    python                      \
    g++                         \
    make


################################################################################
## Add repository for PHP and Nodejs latest versions
#
sudo add-apt-repository ppa:ondrej/php5 -y
sudo add-apt-repository ppa:chris-lea/node.js -y
sudo apt-get update


################################################################################
## Set the MySQL root password to 'password' so the
## installer doesnt need to prompt during install
#
sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password password password'
sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password password'


################################################################################
## Install development packages
#
sudo apt-get install -y \
    php5                \
    php5-curl           \
    php5-mysql          \
    php-pear            \
    php5-mcrypt         \
    php5-dev            \
    nodejs              \
    mysql-server


################################################################################
## Install some cli utilities
#

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Grunt & Bower
sudo npm install -g grunt-cli bower

# Phing
pear channel-discover pear.phing.info
pear install --alldeps phing/phing

# xdebug
sudo pecl install xdebug
XDEBUG_CONF =<< EOF
zend_extension=xdebug.so
xdebug.remote_enable=1
xdebug.remote_port=9000
xdebug.profiler_enable=1
xdebug.profiler_output_dir=/dev/null
EOF
echo $XDEBUG_CONF > /etc/php5/apache2/conf.d/xdebug.ini


################################################################################
## Run the MODX provisioner
#
php /vagrant/_box_tools/tester.php

