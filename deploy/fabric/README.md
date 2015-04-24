# Median 6 Admin Fabfile

This allows the use of [Fabric](http://www.fabfile.org/) to more easily administrate the Median 6 server cluster.

For example, with this you can update code on all servers after you've pushed it to Gitlab.

Fabric lets you bundle lots of SSH commands together and execute them easily and cleanly across multiple hosts at once.

## Installing Fabric

I recommend installing [Homebrew](http://brew.sh/) and then using `pip` to install Fabric, like so:

`pip install fabric`

That should be it.

## Configuring for Your Cluster

Open up `fabfile.py` and edit the roles hash at the top of the file with the DNS names of your servers.

## Using the fabfile

First of all, make sure you have your SSH public keys installed on all Median 6 servers' `root` user.

The file `fabfile.py` holds the functionality that the `fab` program uses.

First, `cd` to the directory with `fabfile.py` in it. Then you can run commands with `fab`.

`cd /path/to/median-fabric/`

Commands:

- Updating webapp code from gitlab: `fab update_webapp_code`
- Updating monitor code from gitlab: `fab update_monitor_code`
- Updating file API code from gitlab: `fab update_fileapi_code`
- Get some IPs: `fab -R webapps,streaming get_ips`
- See all available Fabric commands: `fab -l`

## Adding more commands

The `fabfile.py` file is just Python, so you can add more commands as you please.

Fabric documentation is [here](http://docs.fabfile.org/en/1.8/).