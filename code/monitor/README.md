# Median Monitor Service

The Median monitor service keeps track of file API servers, streaming servers, and web application tier servers.

It also keeps track of farming transcode nodes.

This helps auto-adjust the environment on-the-fly. The monitoring service uses port 7070 by default.

## Installation + Configuration

See the Median Monitor Deployment documentation/scripts for detailed info on deploying a new monitor server.

Once that's done, edit `m6-monitor.js` to match its config options to your environment.

## Running the Monitoring Service

I recommend using an always-on Node.js daemon like [forever](https://github.com/foreverjs/forever) or [pm2](https://github.com/Unitech/pm2) to keep the monitoring service running.

Otherwise, run `node m6-monitor.js` at the command line.

## Registering New Servers

When setting up a new file API, web tier, or streaming server, you must "register" it with the monitor, by performing a command on that server like:

    curl https://median-monitor.your-server.com:7070/heartbeat

Then, within the MongoDB database of servers, you can change the "type" (key is "t") of server it is, either "webapp", "files", or "streaming". Inside the MongoDB shell, run a command like:

    use median6
	db.servers.update({"ip":"server-IP-here"}, {$set: { "e": true, "t": "webapp", "hostname": "your-webapp-server.com" } })

Do that for each server in your cluster, and the monitoring service will be able to keep track of them.