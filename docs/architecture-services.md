# Median Service Architecture

Median is built to be deployed in a cluster of at least four nodes. It can be deployed on only one for testing, too (if you really want).

- A load balancer should live on port 443 waiting for incoming HTTPS connections.
- A streaming service should live on port 1935 waiting for incoming RTMP requests, and port 1930 for HTTP requests.
- A monitoring service should live on port 7070 waiting for incoming HTTP requests from other nodes.
- A web app service should live on port 8080 waiting for incoming HTTP requests from the load balancer.
- A file API endpoint should live on port 9090 waiting for incoming HTTP requests from either the load balancer or the web app layer.

If these are all on the same server, then it'll all just share information over its own ports via 127.0.0.1 and only port 443 should be open to the outside world.

If these are all on different nodes, then they should all be kept within a secure network, with only allowed connections being between each node and the load balancer.

Median is also built to use MongoDB as its central metadata database. I recommend using at least a three-node replica set.

Median also depends on fast storage for file uploading, downloading, and streaming. I recommend hosting Median on at least an array of SSDs.