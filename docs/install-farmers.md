# Median Farmer

The Median farmers are built to be self-contained PXE-booted nodes, but they don't have to be.

## Install

Start with a fresh Ubuntu 12.04 or 14.04 Server.

Make sure Node.js is installed:

	wget http://nodejs.org/dist/v0.8.27/node-v0.8.27.tar.gz
	tar zxf node-v0.8.27.tar.gz
	cd node-v0.8.27/
	./configure
	make
	make install

The farming script itself requires `handbrake-cli`

	apt-get -y install python-software-properties
	add-apt-repository ppa:stebbins/handbrake-snapshots
	apt-get update
	apt-get -y install libssl-dev curl handbrake-cli

The farming script itself `code/farmers/median-pxe-farmer.js` expects to be installed in `/opt/farmer`

It'll do most of the rest of the work on first run; install the init file below if you want it to run on boot.

Otherwise, run it like `node /opt/farmer/median-pxe-farmer.js` or if you use [forever](https://github.com/foreverjs/forever) run `forever start /opt/farmer/median-pxe-farmer.js`

## Scripts

`code/farmers/median-pxe-farmer.js` is the production PXE version of the farming script.

You should edit the configuration variables near the top of the file to match your environment.

The node script has no external module dependencies, but it requires `handbrake-cli` to be installed.

## Init files

`deploy/farmers/median-pxe-farmer.conf` is the Ubuntu Upstart script for the production `median-pxe-farmer.js` script. It goes in `/etc/init/`.