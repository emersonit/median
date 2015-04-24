# Data schemas in MongoDB for monitoring services

The median-monitor keeps these documents up-to-date in MongoDB.

## median6.servers collection

A sample document:

    {
        "_id": ObjectId("xxx"),
        "hostname": "m6-files-1.dev.emerson.edu",
        "ip": "10.94.80.101",
        "port": 9090,
        "port2": 80,
        "e": true,
        "t": "files",
        "tsc": 1396970866,
        "hb_c": 1396970890,
        "hb_m": 1396970890
    }

Keys:

- `_id` is the built-in MongoDB unique document ID
- `hostname` is the hostname of the server being monitored
- `ip` is the IP address associated with the server being monitored
- `port` is the port the "main" service is listening on, if necessary
- `port2` is an alternate port a secondary service is listening on, if necessary
- `e` can be true/false, keeps track of whether this server is enabled or disabled for use
- `t` is what type of server it's registered as. can be "unknown", "files", "streaming", or "webapp"
- `tsc` is the unix timestamp for when the server was first recognized
- `hb_c` is the unix timestamp for the latest heartbeat sent from the client (a passive check)
- `hb_m` is the unix timestamp for the latest heartbeat ping from the monitoring server that got a response from the client (an active check)

That's all! The web application servers use this information to figure out what servers are available.