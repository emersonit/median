#!/bin/bash

# this does everything to build a webapp server

# where do we store the application code
BIN_DIRECTORY="/median-webapp"

echo "Setting up directory $BIN_DIRECTORY"
sleep 1

# make $BIN_DIRECTORY if it doesn't already exist
if [ ! -d "$BIN_DIRECTORY" ]; then
	mkdir $BIN_DIRECTORY
fi

# ok cool

echo "Installing prerequisites..."
sleep 1

# install prerequisites
apt-get update
apt-get -y install python-software-properties
add-apt-repository -y ppa:nathan-renniewaldock/ppa
add-apt-repository -y ppa:ondrej/php5
apt-get update

echo "Installing lighttpd and php5-fpm"
sleep 1

# probably will need to add more here
apt-get -y install lighttpd curl php5-fpm php5-dev php-pear php5-curl php5-mcrypt php5-mysql php5-sqlite

# probably want to make our own custom php.ini
# with correct error logging and whatnot
if grep -Fxq "cgi.fix_pathinfo = 1;" /etc/php5/fpm/php.ini; then
	echo "php5-fpm php.ini already patched with cgi.fix_pathinfo"
else
	echo "cgi.fix_pathinfo = 1;" >> /etc/php5/fpm/php.ini
fi

echo "date.timezone = 'US/Eastern'" >> /etc/php5/cli/php.ini
echo "date.timezone = 'US/Eastern'" >> /etc/php5/fpm/php.ini

echo "Installing php5 Mail lib via PEAR"
sleep 1
pear install Auth_SASL Net_SMTP Net_Socket Mail Mail_Mime

echo "Installing php5-mongo via PECL"
sleep 1

# need to use the printf prefix to accept the default option
printf "\n" | pecl install mongo
echo "extension=mongo.so" > /etc/php5/mods-available/mongo.ini
ln -s /etc/php5/mods-available/mongo.ini /etc/php5/fpm/conf.d/30-mongo.ini
ln -s /etc/php5/mods-available/mongo.ini /etc/php5/cli/conf.d/30-mongo.ini

#
# move our custom lighttpd.conf to replace the one that came with lighttpd
#

echo "Moving our custom lighttpd configuration to override the original..."
sleep 1

if [ ! -f "lighttpd.conf" ]; then
	echo "missing local custom lighttpd.conf file which should be here with this script!"
	exit 1
fi

mv /etc/lighttpd/lighttpd.conf /etc/lighttpd/lighttpd.conf.orig
cp lighttpd.conf /etc/lighttpd/lighttpd.conf
lighty-enable-mod fastcgi
cp lighty-php5-fpm.conf /etc/lighttpd/conf-enabled/15-php5-fpm.conf
cp lighty-simplesaml.conf /etc/lighttpd/conf-enabled/20-simplesaml.conf

# ok, all done
echo "All done! Set up!"
echo "Now you should add this server's SSH key to the Webapp Code repo"
echo "  and use git to clone the latest code into $BIN_DIRECTORY"
echo "Then run some 'service lighttpd restart' and 'service php5-fpm restart'"
echo "And you should delete this deployment directory..."
