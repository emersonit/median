#!/bin/bash

# this does everything to build an auth state storage server

# install mariadb

echo "Setting up MariaDB (MySQL drop-in replacement, it's better)"
echo "You'll need to input a root password..."
sleep 1
apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xcbcb082a1bb943db
add-apt-repository 'deb http://mirrors.syringanetworks.net/mariadb/repo/10.0/ubuntu trusty main'
apt-get update
apt-get install mariadb-server

# ok, all done
echo "All done! Set up!"
echo "Go into '/etc/mysql/my.cnf' and comment-out the 'bind-address' line"
echo "  and then run 'service mysql restart' to make it available to the webapp hosts."
echo "You should set a password inside 'setup-saml-db.sql' and then run it like this:"
echo "  # mysql -u root -p < setup-saml-db.sql"