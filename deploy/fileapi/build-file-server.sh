#!/bin/bash

# this does everything to build a file server
# EXCEPT the file storage mounting, which will be custom for you

#
# set up fstab entry for file storage if it doesn't already exist
#
#   for /etc/fstab:
#   your-storage-server.com:/share/median /median-files nfs nfsvers=3,rw,hard,intr,rsize=32768,wsize=32768,noatime,tcp,timeo=20  0 0
#

# what's the local mount point for all the media
FILE_DIRECTORY="/median"
CODE_DIRECTORY="/median-file-api"
SRC_DIRECTORY="/usr/src"
BIN_DIRECTORY="/usr/local/bin"

#
# if the fstab file doesn't already hold an entry for
# the NFS share for all the media, add it
#
# since this is a file server, it should be read/write
#

echo "Setting up directories $FILE_DIRECTORY, $CODE_DIRECTORY, $SRC_DIRECTORY, and $BIN_DIRECTORY"
sleep 1

# make $FILE_DIRECTORY if it doesn't already exist
if [ ! -d "$FILE_DIRECTORY" ]; then
	mkdir $FILE_DIRECTORY
fi

# make $BIN_DIRECTORY if it doesn't already exist
if [ ! -d "$CODE_DIRECTORY" ]; then
	mkdir $CODE_DIRECTORY
fi

# make $SRC_DIRECTORY if it doesn't already exist
if [ ! -d "$SRC_DIRECTORY" ]; then
	mkdir $SRC_DIRECTORY
fi

# make $BIN_DIRECTORY if it doesn't already exist
if [ ! -d "$BIN_DIRECTORY" ]; then
	mkdir $BIN_DIRECTORY
fi

#
# we need NFS for the file share
#
echo "Making sure nfs-common is installed..."
sleep 1

apt-get update
apt-get -y install nfs-common

# install node.js

# i wish i could use the latest node, but the "formidable" module is broken with it
echo "Setting up Node.js v0.8.27 (for compatibility)"
sleep 1

cd $SRC_DIRECTORY
wget http://nodejs.org/dist/v0.8.27/node-v0.8.27.tar.gz
tar zxf node-v0.8.27.tar.gz
cd node-v0.8.27/
./configure
make
make install

# install forever for running the file server API
npm -g install forever@0.11.1

# install file API npm deps
cd $CODE_DIRECTORY
npm install mime@1.2.11
npm install formidable@1.0.15
npm install node-uuid@1.4.1

# install php5-cli

echo "Setting up php5-cli with extensions"
sleep 1

apt-get update
apt-get -y install python-software-properties
add-apt-repository -y ppa:ondrej/php5
apt-get update
apt-get -y install curl php5-cli php5-json php5-dev php5-curl php5-mcrypt php-pear

echo "date.timezone = 'US/Eastern'" >> /etc/php5/cli/php.ini

# install PHP Mongo driver
# need to use the printf prefix to accept the default option
printf "\n" | pecl install mongo
echo "extension=mongo.so" > /etc/php5/mods-available/mongo.ini
ln -s /etc/php5/mods-available/mongo.ini /etc/php5/cli/conf.d/30-mongo.ini

# install python imaging library
echo "Installing python imaging library"
sleep 1

apt-get -y install python-imaging

# install ffprobe

echo "Install ffmpeg and ffprobe from scratch"
echo "See: ffmpeg-from-scratch.txt"
sleep 1

# ok, all done
echo "All done! Set up!"
echo "Now you should add this server's SSH key to the File API Code repo"
echo "  and use git to clone the latest code into $CODE_DIRECTORY"
echo "Then run 'pm2 start $BIN_DIRECTORY/m6-file-api.js' to get it running"
echo "And you should delete this deployment directory..."
