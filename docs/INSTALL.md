# Median Installation

This INSTALL doc is split into a different section for each service layer, and overall steps that apply to all layers. It's a very involved process, as each layer is built so it can scale independently of the other layers, if need be.

While you can set up all of these services on one machine, it's not recommended in production. For production, _at least_ separate out the video transcoding layer, as it can consume large amounts of CPU.

Ideal production setup:

- 1 or 2 web layer servers, load balanced
- 1 or 2 file API layer servers, load balanced
- 1 or 2 video streaming servers, load balanced
- 1 cluster monitoring server
- a 3-node MongoDB replica set
- 1 fast file storage server
- 3-10 video transcoder/"farmer" nodes

All of the servers used when building and running Median in production use Ubuntu 12.04 or 14.04. Instructions are written for that platform.

If you look inside the `deploy` folder, there are individual deployment scripts and server-side configuration files tied to each layer. I encourage you to follow the steps in each folder and do each piece yourself at the command line. Once you're confident in how Median works, you can use the scripts as they are to deploy more servers.

## Database (MongoDB) Layer

Median uses MongoDB to store all entry metadata, user info, captions, transcoding jobs, logs, etc. For this reason, having a fast and robust MongoDB instance is critical. I recommend a three-node replica set. Follow MongoDB's guide for [Installing a Replica Set](http://docs.mongodb.org/manual/tutorial/deploy-replica-set/) to set that up.

Once set up, pre-fill the `median5` database with the collection JSON sets in the `mongodb/median5` directory included with this documentation. You can use the `mongoimport` command, like so:

    mongoimport --db median5 --collection helpfiles --file helpfiles.json

Do that for all of the included JSON files, they're all named the relevant collection name.

Also, in `schemas/mongodb-indexes.md`, is a list of commands to ensure indexes across the Median databases in MongoDB.

## File Storage Layer

Median requires the use of fast file storage for video streaming, transcoding, and uploading. In an academic environment, students often upload uncompressed videos of large file sizes (2-10GB), which Median's upload processing can easily handle -- if the disk is fast enough. One of the biggest scaling problems for Median was getting fast enough disk to handle the incoming file load.

All file storage begins with a root `/median` folder. This folder should be exported as an NFS mount.

After creating the file storage layer, you will be setting up NFS mounts at `/median` on the file API, streaming, and transcoding layers that point to that central `/median` share. The streaming layer only needs read access; it never writes files. The file API and transcoding layers require read/write access. The web layer is segregated from the file system itself to increase reliability of the site if the file-based layers become unstable.

## Web Layer

The web layer consists of lighttpd, php5-fpm, and several PHP extensions such as the Mongo driver. The web layer expects PHP 5.5+.

See `deploy/webapp/build-webapp-server.sh` for detailed instructions on setting up the web layer. There's also additional configuration steps in the `README.md` file included with the code.

Once set up, the code for the web layer is within `code/webapp/` -- the actual server root should be what's inside `code/webapp/www/`

## File API Layer

The file API layer consists of Node.js (v0.8.7, for compatibility), php5-cli, and several PHP extensions such as the Mongo driver. The file API layer expects PHP 5.5+.

Node.js handles all incoming file operation requests such as uploads, sharing entry file information, and downloads. It also runs periodic file operations and cleanup jobs. Many of these operations are contained with the `m6-cli.php` file, which can also be run by an administrator via command line.

The file API layer requires read/write access to the file storage share.

See `deploy/file-api/build-file-server.sh` for detailed instructions on setting up the file API layer. More information about the file API layer is included with the source code. Once set up, the code for the file API layer is within `code/fileapi/`

## Load Balancing the Web, File API, and Streaming Layers

For a load balancer, Median at Emerson College uses an [A10](https://www.a10networks.com/products/server-load-balancing), but you should be able to replicate it using open source/free alternatives like [HAProxy](http://www.haproxy.org/). The load balancer should also do SSL offloading, if you set up Median behind HTTPS, which I recommend.

The only routing that needs to happen is the following:

- All HTTP requests to /files/* must go to the File API server pool.
- Every other HTTP request should go to the Web App server pool.
- Every RTMP request should go to the Streaming server pool.

That's it!

## Video Streaming Layer

The video streaming layer utilizes nginx-rtmp. It only requires read-only access to the file storage share.

See `deploy/streaming/build-streaming-server.sh` for detailed instructions on setting up a streaming server.

## Transcoding Layer

The transcoding/farming layer utilizes Node.js and handbrake-cli. It requires read/write access to the file storage share.

See `deploy/farmers/build-farmer-node.sh` for detailed instructions on setting up a farming node/server.

See also the `install-farmers.md` and `transcode-farm.md` documentation on how the whole thing works.

## Monitoring Layer

The monitoring layer utilizes Node.js to continuously monitor the cluster of Median servers. After the monitoring service is set up, you'll want to go to each server in the cluster and send the monitoring service a "heartbeat" to register it as a member of the cluster. More information on this is included with the monitoring source code.

See `deploy/monitor/build-monitor-server.sh` for detailed instructions on setting up a monitoring server.

## Authentication Layer

The authentication layer utilizes SimpleSAMLphp as a service provider, which means you will have to set up a separate identity provider instance, if you do not already have one. Detailed info on how to configure authentication for your environment is included in the `user-authentication.md` documentation. You're encouraged to set up a MySQL/MariaDB instance somewhere to share user authentication state between the web application servers.

See `deploy/auth/build-auth-server.sh` for detailed instructions on setting up the shared authentication state server. This is relatively low-impact, so you can probably build this on top of the Monitoring server.