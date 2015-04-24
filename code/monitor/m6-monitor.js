/*

    Median 6 Internal Monitoring and Configuration Data

    1. keep key/value store of current config options for median cluster
        - what file-api servers are active
        - store in mongodb
    2. respond to incoming requests for config option values
    3. respond to incoming heartbeat info from servers
    4. do basic monitoring checks on existing servers

*/

// configuration settings

// what IP range should be inherently trusted?
// this range is typically where all of your servers live
var trusted_ip_prefix = '10.94.80.';

// what port to listen on for heartbeats
var listen_port = 7070;

// seconds between active server checks
var check_servers_interval = 60;

// seconds between active farmer checks
var check_farmers_interval = 5 * 60;

// connection string for your mongodb server/cluster
var mdb_connect = 'mongodb://ackbar.emerson.edu:27017,wedge.emerson.edu:27017,lando.emerson.edu:27017/median6?replicaSet=rebelfleet&w=1';

// the name of the collection in your database for monitoring data
var monitor_collection = 'servers';

// the URL of the farming check script on the web tier
var farming_check_script = 'https://median.emerson.edu/farm/check.php';

// done config -- now load modules

var http = require('http');
var https = require('https');
var url = require('url');
var qs = require('querystring');
var dns = require('dns');
var MongoClient = require('mongodb').MongoClient;

function updateHeartbeat(server_ip, client_heartbeat) {
    console.log('updating TS for server ' + server_ip);
    MongoClient.connect(mdb_connect, function(err, db) {
        if (err) {
            console.error('Error connecting to MongoDB...');
            throw err;
        }
        var the_collection = db.collection(monitor_collection);
        // get current unix timestamp
        var current_ts = Math.round((new Date().getTime()) / 1000);
        // check if this server is already being tracked...
        var find_server = the_collection.find({ 'ip': server_ip });
        find_server.count(function(err, count) {
            if (err) {
                console.error('Error getting count...');
                throw err;
            }
            if (count == 0) {
                // insert!
                var new_server = {};
                new_server.hostname = '';
                new_server.ip = server_ip;
                new_server.e = false;
                new_server.t = 'unknown';
                new_server.tsc = current_ts;
                if (client_heartbeat == true) {
                    new_server.hb_c = current_ts;
                    new_server.hb_m = 0;
                } else {
                    new_server.hb_c = 0;
                    new_server.hb_m = current_ts;
                }
                the_collection.insert(new_server, function(err, server) {
                    if (err) { throw err; }
                    console.log('successfully inserted new record for server ' + server_ip);
                    db.close();
                });
            } else {
                // update!
                var new_ts_record = { '$set': {} };
                // by default, assume we're updating the monitoring server's heartbeat TS
                // but if this was a heartbeat sent by a client, update that
                if (client_heartbeat == true) {
                    new_ts_record['$set']['hb_c'] = current_ts;
                } else {
                    new_ts_record['$set']['hb_m'] = current_ts;
                }
                the_collection.update({ 'ip': server_ip }, new_ts_record, {}, function(err, num_modified) {
                    if (err) { throw err; }
                    console.log('successfully updated TS for server ' + server_ip);
                    db.close();
                });
            }
        });
    });
}



http.createServer(function(req, res) {
    var the_url = url.parse(req.url, true);
    if (the_url.pathname == '/favicon.ico') { // ignore favicon requests
        res.writeHead(404);
        res.end();
        return;
    }
    if (the_url.pathname == '/') { // don't do anything with / requests
        res.writeHead(200, {'Content-Type': 'text/plain'});
        res.end('hi');
        return;
    }
    var the_ip = req.connection.remoteAddress;
    if (the_ip.substr(0, trusted_ip_prefix.length) != trusted_ip_prefix) {
        console.log('invalid request source');
        res.writeHead(403, {'Content-Type': 'text/plain'});
        res.end('no thx');
        return;
    }
    console.log('## new incoming request');
    console.log(the_url.pathname);
    //console.log(the_url.query);
    console.log(the_ip);
    /*
    dns.reverse(the_ip, function(err, domains) {
        if (err) {
            // error? has no hostname? eject.
            console.log(err);
            return;
        }
        //console.log(domains);
        // use the first result, should always be right
        // can be corrected manually, if not
        var the_hostname = domains[0];
        console.log(the_hostname);
    });
    */
    if (the_url.pathname == '/heartbeat') {
        // a heartbeat from a server
        // update mongodb
        // use IP address as key
        // if it doesn't already exist, add it
        // but disabled and using "unknown" type
        updateHeartbeat(the_ip, true);
        res.writeHead(200, {'Content-Type': 'text/plain'});
        res.end('ok thx');
        return;
    } else {
        res.writeHead(403, {'Content-Type': 'text/plain'});
        res.end('no thx');
        return;
    }
}).listen(listen_port);

/*

    median servers check

*/
setInterval(function() {
    console.log('checking servers...');
    // go through all the servers in mongodb
    // and try to check them with http
    // depending on what "type" they are

    MongoClient.connect(mdb_connect, function(err, db) {
        if (err) {
            console.error('Error connecting to MongoDB...');
            throw err;
        }
        var the_collection = db.collection(monitor_collection);
        the_collection.find({}).toArray(function(err, servers) {
            //console.log(servers);
            for (var i = 0; i < servers.length; i++) {
                //console.log('checking server ' + servers[i].ip);
                // do a simple HTTP check to see if it's alive?
                // update the heartbeat 'hb_m' if so
                var http_port_check = 80;
                if (servers[i].t == 'webapp') {
                    http_port_check = servers[i].port;
                } else if (servers[i].t == 'files') {
                    http_port_check = servers[i].port;
                } else if (servers[i].t == 'streaming') {
                    http_port_check = servers[i].port2;
                } else {
                    // do nothing? lol
                    console.log('dunno how to check, skipping...');
                    continue;
                }
                console.log('Checking server via HTTP on http://' + servers[i].ip + ':' + http_port_check);
                var req_check = http.request({ hostname: servers[i].ip, port: http_port_check, method: 'HEAD', agent: false }, function(res) {
                    if (res.statusCode == 200) {
                        //updateHeartbeat(servers[i].ip, false);
                        var checked_ip = '';
                        if (res.req._headers.host.indexOf(':') != -1) {
                            checked_ip = res.req._headers.host.substring(0, res.req._headers.host.indexOf(':'));
                        } else {
                            checked_ip = res.req._headers.host;
                        }
                        console.log('got an HTTP response back from ' + checked_ip);
                        updateHeartbeat(checked_ip, false);
                    } else {
                        console.log('responded with ' + res.statusCode);
                    }
                });
                req_check.on('error', function(e) {
                    console.log("Got error: " + e.message);
                });
                req_check.setTimeout(300, function() {
                    req_check.abort();
                });
                req_check.end();
            }
            db.close();
        });
    });

    // collect stats for streaming servers?

}, check_servers_interval * 1000);

/*

    farmer check

*/
setInterval(function() {
    // run a GET for the farmer check script on median
    https.get(farming_check_script, function(res) {
        console.log('status code from farmer check: ', res.statusCode);
    }).on('error', function(e) {
        console.error('error when trying to do farmer check');
        console.error(e);
    });
}, check_farmers_interval * 1000);

console.log('Median 6 Monitor running at http://0.0.0.0:' + listen_port + '/');
