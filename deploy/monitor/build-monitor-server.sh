#!/bin/bash

# this does everything to build a monitor server

# where do we store the application code
BIN_DIRECTORY="/median-monitor"

echo "Setting up directory $BIN_DIRECTORY"
sleep 1

# make $BIN_DIRECTORY if it doesn't already exist
if [ ! -d "$BIN_DIRECTORY" ]; then
    mkdir $BIN_DIRECTORY
fi

# install node.js
# add check to see if it's already installed and latest?

echo "Setting up Node.js"
sleep 1

apt-get update
apt-get -y install python-software-properties
add-apt-repository -y ppa:chris-lea/node.js
apt-get update
apt-get -y install nodejs

# install pm2 for running the monitoring API
npm install pm2@latest -g --unsafe-perm

# install mongodb module
cd $BIN_DIRECTORY
npm install mongodb

# ok, all done
echo "All done! Set up!"
echo "You should add this server's SSH key to the Monitor Code repo"
echo "  and use git to clone the latest code into $BIN_DIRECTORY"
echo "Then run 'pm2 start $BIN_DIRECTORY/m6-monitor.js' to get it running"
echo "And you should delete this deployment directory..."