/*

    median 6 upload tester

    all this does is accept file uploads and whatnot
    then deletes files after they're uploaded
    just to see how many can be done at once

*/

var listen_port = 9090;
var tmp_path = '/median/upload_tmp/';
//var tmp_path = '/median-test/upload-tmp/';
var upload_processor_script = 'upload_test_processor.php';
var uploads = [];
var upload_regex = /^\/upload\/([-_A-Za-z0-9]+)\/$/i;
var upload_status_regex = /^\/upload-status\/([-_A-Za-z0-9]+)\/$/i;

// docs: http://nodejs.org/api/
var http = require('http');
var url = require('url');
var qs = require('querystring');
var util = require('util');
var fs = require('fs');
var os = require('os');
var path = require('path');
var spawn = require('child_process').spawn;

// docs: https://github.com/felixge/node-formidable
var formidable = require('formidable');

// docs: https://github.com/broofa/node-uuid
var uuid = require('node-uuid');

// docs: https://github.com/broofa/node-mime
var mime = require('mime');

// make sure the temp storage directory exists
if (fs.existsSync(tmp_path) == false) {
    fs.mkdirSync(tmp_path, 0777);
}

http.createServer(function (req, res) {
    var the_url = url.parse(req.url, true);
    var the_ip = req.connection.remoteAddress;
    //console.log('== new request from ' + the_ip);
    //console.log(req.headers.host);
    //console.log(the_url.pathname);

    // if it's coming through the load balancer,
    // the URL path will start with /files
    // remove that
    if (the_url.pathname.substr(0, 6) == '/files') {
        the_url.pathname = the_url.pathname.substr(6);
    }
    //console.log(req.method.toLowerCase() + ' ' + the_url.pathname);

    /*

        handle root path

    */
    if (the_url.pathname == '/') {
        res.writeHead(200, {'Content-Type': 'application/json'});
        res.end(JSON.stringify({ what: 'oh hello' }));

    /*

        handle /uploads path

    */
    } else if (the_url.pathname == '/uploads' || the_url.pathname == '/uploads/') {
        res.writeHead(200, {'Content-Type': 'application/json'});
        res.end(JSON.stringify({ 'uploads': uploads }));

    /*

        handle /new-upload-id path

    */
    } else if (the_url.pathname == '/new-upload-id' || the_url.pathname == '/new-upload-id/') {
        var new_upload_slot = {
            'uuid':  uuid.v1(),
            'ip': the_ip,
            'start': new Date(),
            'last': new Date(),
            'uploaded': false,
            'swap': false,
            'median_id': 0,
            'user_id': 0,
            'bytes_received': 0,
            'bytes_total': 0,
            'files': []
        };
        uploads.push(new_upload_slot);
        console.log('new upload ID requested, granted: ' + new_upload_slot.uuid);
        res.writeHead(200, {'Content-Type': 'application/json'});
        res.end(JSON.stringify({ 'uuid': new_upload_slot.uuid }));

    /*

        handle /upload path

    */
    } else if (upload_regex.test(the_url.pathname) && req.method.toLowerCase() == 'post') {
        var upload_matches = the_url.pathname.match(upload_regex);
        var upload_uuid = upload_matches[1];

        var found_uuid = false;
        for (var i = 0; i < uploads.length; i++) {
            if (uploads[i]['uuid'] == upload_uuid) {
                found_uuid = true;
                break;
            }
        }

        if (found_uuid == false) {
            console.error('user is trying to upload with a UUID that does not exist');
            res.writeHead(404, {'content-type': 'application/json'});
            res.end(JSON.stringify({ 'error': 'the UUID you supplied was not found' }));
            return;
        }

        console.log('new upload incoming! uuid: ' + upload_uuid);

        var form = new formidable.IncomingForm();
        form.keepExtensions = true;
        form.uploadDir = tmp_path;
        var files = [];
        var titles = [];
        var user_id = 0;

        form.on('progress', function(brec, btotal) {
            for (var i = 0; i < uploads.length; i++) {
                if (uploads[i]['uuid'] == upload_uuid) {
                    uploads[i]['bytes_received'] = brec;
                    uploads[i]['bytes_total'] = btotal;
                    uploads[i]['last'] = new Date();
                    break;
                }
            }
        });
        form.on('fileBegin', function(name, file) {
            console.log('new file incoming: ' + name);
            console.log(util.inspect(file, false, null));
        });
        form.on('file', function(name, file) {
            console.log('new file received: ' + name);
            console.log(util.inspect(file, false, null));
            files.push(file);
        });
        form.on('field', function(name, value) {
            console.log('new field detected: "'+name+'": '+value);
            if (name == 'upload-title') {
                titles.push(value);
            } else if (name == 'user_id') {
                user_id = value * 1;
            }
        });
        form.on('aborted', function() {
            console.error('oops, upload aborted by user!');
            res.writeHead(404, {'content-type': 'application/json'});
            res.end(JSON.stringify({ 'error': 'User aborted the upload!' }));
        });
        form.on('error', function(err) {
            console.error('error with uploading');
            console.error(err);
            res.writeHead(404, {'content-type': 'application/json'});
            res.end(JSON.stringify({ 'error': err }));
        });
        form.on('end', function() {
            for (var i = 0; i < uploads.length; i++) {
                if (uploads[i]['uuid'] == upload_uuid) {
                    uploads[i]['uploaded'] = true;
                    break;
                }
            }

            // ok put it all together... match files with titles
            // and do some work
            var this_session_info = {};
            var final_files_info = [];

            for (var i = 0; i < files.length; i++) {
                var tmp_info = { 'title': '', 'path': '', 'size': 0 };
                tmp_info.path = files[i].path;
                tmp_info.size = files[i].size;
                tmp_info.title = titles[i];
                final_files_info.push(tmp_info);
            }

            // add final info to upload info
            for (var i = 0; i < uploads.length; i++) {
                if (uploads[i]['uuid'] == upload_uuid) {
                    uploads[i]['user_id'] = user_id;
                    uploads[i]['files'] = final_files_info;
                    this_session_info = uploads[i];
                    break;
                }
            }

            // send it along to upload processing
            console.log(this_session_info);

            var upload_proccessor = spawn('php', ['-f', upload_processor_script], { env: process.env });

            var upload_proccessor_stdout = '';
            var upload_proccessor_stderr = '';

            upload_proccessor.stdout.on('data', function(data) {
                upload_proccessor_stdout += data;
            });

            upload_proccessor.stdout.on('end', function() {
                console.log('upload proccessor final stdout: ' + upload_proccessor_stdout);
            });

            upload_proccessor.stderr.on('data', function(data) {
                upload_proccessor_stderr += data;
            });

            upload_proccessor.stderr.on('end', function() {
                console.log('upload proccessor final stderr: ' + upload_proccessor_stderr);
            });

            upload_proccessor.on('close', function(code) {
                console.log('upload proccessor exited with code ' + code);
                if (code == 0) {
                    // everything is cool enough, send resulting data along to client
                    console.log('sending this data to the client');
                    console.log(upload_proccessor_stdout);
                    res.writeHead(200, {'Content-Type': 'application/json'});
                    res.end(upload_proccessor_stdout);
                    console.log('all done !!!');
                } else {
                    // there was an error that we need to take care of
                    console.error('there was an error of some kind with the upload processor');
                    //console.log(upload_proccessor_stderr);
                    res.writeHead(500, {'Content-Type': 'application/json'});

                    // what gets sent to the client?

                    res.end(JSON.stringify({ 'error': 'there was a problem processing your upload' }));
                    console.log('all done !!!');
                }
            });

            upload_proccessor.stdin.write(JSON.stringify(this_session_info));
            upload_proccessor.stdin.end();
        });
        form.parse(req);

    /*

        handle /upload-status path

    */
    } else if (upload_status_regex.test(the_url.pathname)) {
        var status_matches = the_url.pathname.match(upload_status_regex);
        var uuid_to_check = status_matches[1];
        var return_object = false;
        //console.log('upload status request');
        //console.log('upload uuid to status check for user: ' + uuid_to_check);
        for (var i = 0; i < uploads.length; i++) {
            if (uploads[i]['uuid'] == uuid_to_check) {
                return_object = { 'bytes_received': uploads[i]['bytes_received'], 'bytes_total': uploads[i]['bytes_total'], 'uploaded': uploads[i]['uploaded'] };
                break;
            }
        }
        if (return_object != false) {
            //console.log('returning: ');
            //console.log(return_object);
            console.log( uuid_to_check + ' is ' + Math.round((uploads[i]['bytes_received']/uploads[i]['bytes_total']) * 100) + '% uploaded' );
            res.writeHead(200, {'Content-Type': 'application/json'});
            res.end(JSON.stringify(return_object));
        } else {
            console.error('UUID not found...');
            res.writeHead(404, {'Content-Type': 'application/json'});
            res.end(JSON.stringify({ 'error': 'Upload ID not found' }));
        }
    } else {
        // all else 404s
        res.writeHead(404, {'Content-Type': 'application/json'});
        res.end(JSON.stringify({ nope: 'sorry' }));
    }

}).listen(listen_port);

// garbage collection for uploads status array
setInterval(function() {
    //console.log(util.inspect(uploads, null, null));
    // run garbage collection if something hasn't been updated in... two hours?
    var rightnow = new Date();
    for (var i = 0; i < uploads.length; i++) {
        if (uploads[i]['uploaded'] == true || uploads[i]['last'].getTime() < (rightnow.getTime() - 120*60*1000)) {
            //console.log('old id, '+uploads[i]['uuid']+', removing');
            uploads.splice(i, 1);
        }
    }
}, 2000);

console.log('Median 6 File API running at http://0.0.0.0:'+listen_port+'/');
