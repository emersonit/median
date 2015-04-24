#!/bin/bash

# this does everything to build a farmer node on Ubuntu 12.04/14.04

CODE_DIRECTORY="/opt/farmer"
SRC_DIRECTORY="/usr/src"

echo "Setting up directory $CODE_DIRECTORY"
sleep 1

# make $CODE_DIRECTORY if it doesn't already exist
if [ ! -d "$CODE_DIRECTORY" ]; then
	mkdir $CODE_DIRECTORY
fi

echo "Setting up Node.js"
sleep 1

cd $SRC_DIRECTORY
wget http://nodejs.org/dist/v0.8.27/node-v0.8.27.tar.gz
tar zxf node-v0.8.27.tar.gz
cd node-v0.8.27/
./configure
make
make install

echo "Setting up handbrake-cli"
sleep 1

apt-get -y install python-software-properties
add-apt-repository ppa:stebbins/handbrake-snapshots
apt-get update
apt-get -y install libssl-dev curl handbrake-cli

echo "All set! Now install the farmer script in $CODE_DIRECTORY"
echo " and run it with node or forever"