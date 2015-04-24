#!/bin/bash

# this does everything to build a streaming server
# EXCEPT the file storage mounting, which will be custom for you

#
# set up fstab entry for file storage if it doesn't already exist
#
#   for /etc/fstab:
#   your-storage-server.com:/share/median /median-files nfs nfsvers=3,ro,hard,intr,rsize=32768,wsize=32768,noatime,tcp,timeo=20  0 0
#

# what's the local mount point for all the media
FILE_DIRECTORY="/median"
NGINX_BUILD_DIRECTORY="/usr/src"
CURRENT_DIRECTORY=`pwd`

#
# if the fstab file doesn't already hold an entry for
# the NFS share for all the media, add it
#
# since this is just a streaming server, it should be read-only
#

echo "Setting up read-only file directory $FILE_DIRECTORY"
sleep 1

# for now, just make $FILE_DIRECTORY if it doesn't already exist
if [ ! -d "$FILE_DIRECTORY" ]; then
	mkdir $FILE_DIRECTORY
fi

#
# we need NFS for the file share
#
echo "Making sure nfs-common is installed..."
sleep 1

apt-get update
apt-get -y install nfs-common

#
# install nginx prereqs and then compile nginx with nginx-rtmp-module
# documentation: https://github.com/arut/nginx-rtmp-module
# documentation: http://wiki.nginx.org/InstallOptions
#

echo "Installing nginx with RTMP support"
sleep 1

#
# add a check here to see if nginx already exists
#

if [ -f /usr/local/nginx/sbin/nginx ]; then
   echo "nginx already set up, skipping"
else
   	apt-get update
	apt-get -y install build-essential libpcre3 libpcre3-dev libssl-dev zlib1g-dev unzip wget
	cd $NGINX_BUILD_DIRECTORY
	wget --no-check-certificate -q http://nginx.org/download/nginx-1.5.12.tar.gz
	wget --no-check-certificate -q https://github.com/arut/nginx-rtmp-module/archive/master.zip
	tar -zxf nginx-1.5.12.tar.gz
	unzip -qq master.zip

	echo "apply this patch by the way:"
	echo "https://github.com/arut/nginx-rtmp-module/pull/437.patch"
	echo "download that and run `patch < whatever.patch` inside nginx-rtmp-module-master"
	read -p "Press [Enter] key to when ready..."

	cd nginx-1.5.12
	./configure --with-http_ssl_module --add-module=../nginx-rtmp-module-master
	make
	make install
	cd $CURRENT_DIRECTORY
fi

#
# move our custom nginx.conf to replace the one that came with the nginx source
#

echo "Moving our custom nginx configuration to override the original..."
sleep 1

if [ ! -f "nginx.conf" ]; then
	echo "missing local custom nginx.conf file which should be here with this script!"
	exit 1
fi

mv nginx.conf /usr/local/nginx/conf/nginx.conf

# replace the default nginx index page with just "ok" for health checking
echo "ok" > /usr/local/nginx/html/index.html

#
# move our custom nginx-server.conf upstart script
#

echo "Installing upstart /etc/init/ script for nginx..."
sleep 1

# check to see if we even have the custom script
if [ ! -f "nginx-init-script.conf" ]; then
	echo "missing upstart script nginx-server.conf file which should be here with this script!"
	exit 1
fi

# if it's already installed, don't bother overriding it
if [ -f /etc/init/nginx-rtmp.conf ]; then
	echo "upstart script already installed, skipping"
else
	mv nginx-init-script.conf /etc/init/nginx-rtmp.conf
fi

# ok, all done
echo "All done! Set up!"
echo "Make sure you apply that patch to nginx-rtmp!!!"
echo "And make sure to set up the actual file storage share in /etc/fstab"
echo "You can run nginx-rtmp now via 'start nginx-rtmp' if it is not already running"
echo "And you should delete this setup directory..."
