/*

	Median 6 Files API

	handle...
	- file uploads (batch uploads and file swaps)
		/new-upload-id/
		/upload/
		/upload-status/[uuid]/
	- file downloads (just static content, yay)
		/download/[mid]/[unique one-time download token]/
	- HTML5 downloads
		/html5/[path]
	- sandbox downloads
		/sandbox/[filename]
	- thumbnail retrieval (just static content, yay)
		/thumb/[filename]
	- thumbnail generation (via python imaging library CLI tool?)
		/make-thumb/[mid]/['random' or 'standard']/
		/custom-image/ (has POST options)
	- other misc file operations
		/file-info/[mid]/ (file info, codecs, etc)
		/mid-filesize/[mid]/ (total filesize in bytes)

*/

// config -- needs to be checked and configured to match the PHP config

// what port to listen on, default is 9090
var listen_port = 9090;

// the temporary path for new incoming uploads
var tmp_path = '/median/upload_tmp/';

// the base path for all files, used for old median entries, if needed
var all_files_base = '/median/';

// the file path for where thumbnails are saved
var thumb_path_base = '/median/thumbs/';

// the file path for where HTML5 symlinks will be created and referenced from
var html5_path_base = '/median/files/html5/';

// the file path for the sandbox where files may live temporarily
var sandbox_path_base = '/median/files/sandbox/';

// configuration of interval tasks performed by the file API
// you can tune these up and down depending on server load
// what's here are the recommended defaults

// run local upload array garbage collection every 5 seconds
var upload_garbage_collection_frequency = 5 * 1000;

// run the file operations queue processor every 10 seconds
var file_ops_queue_frequency = 10 * 1000;

// run median entry cleanup jobs every 5 minutes
var cleanup_ops_queue_frequency = 5 * 60 * 1000;

// no more config here -- this is all operational

var uploads = [];
var upload_regex = /^\/upload\/([-_A-Za-z0-9]+)\/$/i;
var upload_status_regex = /^\/upload-status\/([-_A-Za-z0-9]+)\/$/i;
var download_regex = /^\/download\/([0-9]+)\/([-_A-Za-z0-9]+)\/$/i;
var thumb_regex = /^\/thumb\/([\.-_A-Za-z0-9]+)$/i;
var html5_regex = /^\/html5\/([\.-_A-Za-z0-9]+)$/i;
var sandbox_regex = /^\/sandbox\/([\.-_A-Za-z0-9]+)$/i;
var make_thumb_regex = /^\/make-thumb\/([0-9]+)\/([-_A-Za-z0-9]+)\/$/i;
var custom_image_regex = /^\/custom-image\/$/i;
var fileinfo_regex = /^\/file-info\/([0-9]+)\/$/i;
var mid_filesize_regex = /^\/mid-filesize\/([0-9]+)\/$/i;

// docs: http://nodejs.org/api/
var http = require('http');
var url = require('url');
var qs = require('querystring');
var util = require('util');
var fs = require('fs');
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
	var currentdate = new Date();
	console.log('== new request from ' + the_ip + ' on ' + currentdate.toString());
	//console.log(req.headers.host);
	//console.log(the_url.pathname);

	// if it's coming through the load balancer,
	// the URL path will start with /files
	// remove that
	if (the_url.pathname.substr(0, 6) == '/files') {
		the_url.pathname = the_url.pathname.substr(6);
	}
	console.log(req.method.toLowerCase() + ' ' + the_url.pathname);

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
			console.log('user is trying to upload with a UUID that does not exist');
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
		var is_swap = false;
		var mid_to_swap = 0;

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
			if (name == 'upload-title[]') {
				titles.push(value);
			} else if (name == 'user_id') {
				user_id = value * 1;
			} else if (name == 'is_swap') {
				if (value == 'yeah') {
					is_swap = true;
				}
			} else if (name == 'mid') {
				mid_to_swap = value * 1;
			}
		});
		form.on('aborted', function() {
			console.log('oops, upload aborted by user!');
			res.writeHead(404, {'content-type': 'application/json'});
			res.end(JSON.stringify({ 'error': 'User aborted the upload!' }));
		});
		form.on('error', function(err) {
			console.log('error with uploading');
			console.log(err);
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
					if (is_swap) { // if this was a file swap, indicate as much
						uploads[i]['is_swap'] = true;
						uploads[i]['mid'] = mid_to_swap;
					}
					this_session_info = uploads[i];
					break;
				}
			}

			// send it along to upload processing
			console.log('sending this info to the upload processor:');
			console.log(this_session_info);

			var upload_proccessor = spawn('php', ['-f', 'upload_processor.php'], { env: process.env });

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
					console.log('there was an error of some kind with the upload processor');
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
		//console.log('upload uuid to check: ' + uuid_to_check);
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
			console.log('UUID not found...');
			res.writeHead(404, {'Content-Type': 'application/json'});
			res.end(JSON.stringify({ 'error': 'Upload ID not found' }));
		}

	/*

		handle /download path

	*/
	} else if (download_regex.test(the_url.pathname)) {
		var download_matches = the_url.pathname.match(download_regex);
		var mid_to_download = download_matches[1];
		var download_token = download_matches[2];

		/*

			send MID and download token to PHP script, see if it's allowed

		*/

		var download_verify_script = spawn('php', ['-f', 'm6-cli.php', 'verify-download', mid_to_download, download_token], { env: process.env });

		var download_verify_script_stdout = '';
		var download_verify_script_stderr = '';

		download_verify_script.stdout.on('data', function(data) {
			download_verify_script_stdout += data;
		});

		download_verify_script.stdout.on('end', function() {
			console.log('download verify script final stdout: ' + download_verify_script_stdout);
		});

		download_verify_script.stderr.on('data', function(data) {
			download_verify_script_stderr += data;
		});

		download_verify_script.stderr.on('end', function() {
			console.log('download verify script final stderr: ' + download_verify_script_stderr);
		});

		download_verify_script.on('close', function(code) {
			console.log('download verify script exited with code ' + code);
			if (code == 0) {
				// everything is cool enough, send resulting data along to client
				//console.log('sending this data to the client');
				//console.log(download_verify_script_stdout);

				var file_to_download = download_verify_script_stdout;

				// if the file path has the old /median/ file base, replace it
				file_to_download = file_to_download.replace('/median/', all_files_base);

				fs.exists(file_to_download, function(exists) {
					if (exists) {
						console.log('sending along file for download: ' + file_to_download);
						var filename = path.basename(file_to_download);
						var mimetype = mime.lookup(file_to_download);
						var filestat = fs.statSync(file_to_download);
						res.writeHead(200, { 'Content-disposition': 'attachment; filename=' + filename, 'Content-type': mimetype, 'Content-length': filestat.size });
						var filestream = fs.createReadStream(file_to_download);
						filestream.pipe(res);
					} else {
						console.log('File for download does not seem to actually exist...');
						res.writeHead(404, {'Content-Type': 'text/plain'});
						res.end(JSON.stringify({ 'error': 'File not found' }));
					}
				});
			} else {
				// there was an error that we need to take care of
				console.log('there was an error of some kind with the download verify script');
				res.writeHead(500, {'Content-Type': 'application/json'});
				res.end('There was an error retrieving your download, please try again.');
			}
		});

		download_verify_script.stdin.end();

	/*

		handle /html5 path

	*/
	} else if (html5_regex.test(the_url.pathname)) {
		var html5_matches = the_url.pathname.match(html5_regex);
		//console.log(thumb_matches);
		var html5_path = html5_matches[1];
		var html5_full_path = html5_path_base + html5_path;
		var start = 0;
		var end = 0;

		/*

			send along file if it exists

		*/
		fs.exists(html5_full_path, function(exists) {
			if (exists) {
				console.log('sending along file for HTML5: ' + html5_full_path);
				var filename = path.basename(html5_full_path);
				var mimetype = mime.lookup(html5_full_path);
				var filestat = fs.statSync(html5_full_path);
				if (req.headers.range != undefined) {
					var range = req.headers.range;
					var positions = range.replace(/bytes=/, "").split("-");
					start = parseInt(positions[0], 10);
					end = positions[1] ? parseInt(positions[1], 10) : filestat.size - 1;
				} else {
					end = filestat.size - 1;
				}
      			var chunksize = (end - start) + 1;
				res.writeHead(206, {
					"Content-Range": "bytes " + start + "-" + end + "/" + filestat.size,
					"Accept-Ranges": "bytes",
					"Content-Length": chunksize,
					"Content-Type": mimetype
				});
				//res.writeHead(200, { 'Content-disposition': 'attachment; filename=' + filename, 'Content-type': mimetype, 'Content-length': filestat.size, 'Accept-Ranges': 'bytes' });
				var filestream = fs.createReadStream(html5_full_path, { start: start, end: end });
				filestream.pipe(res);
			} else {
				console.log('File for HTML5 does not seem to actually exist...');
				res.writeHead(404, {'Content-Type': 'text/plain'});
				res.end(JSON.stringify({ 'error': 'File not found' }));
			}
		});
		
	/*

		handle /sandbox path

	*/
	} else if (sandbox_regex.test(the_url.pathname)) {
		var sandbox_matches = the_url.pathname.match(sandbox_regex);
		//console.log(thumb_matches);
		var sandbox_path = sandbox_matches[1];
		var sandbox_full_path = sandbox_path_base + sandbox_path;
		var start = 0;
		var end = 0;

		//send along file if it exists
		fs.exists(sandbox_full_path, function(exists) {
			if (exists) {
				console.log('sending along file from sandbox: ' + sandbox_full_path);
				var filename = path.basename(sandbox_full_path);
				var mimetype = mime.lookup(sandbox_full_path);
				var filestat = fs.statSync(sandbox_full_path);
				if (req.headers.range != undefined) {
					var range = req.headers.range;
					var positions = range.replace(/bytes=/, "").split("-");
					start = parseInt(positions[0], 10);
					end = positions[1] ? parseInt(positions[1], 10) : filestat.size - 1;
				} else {
					end = filestat.size - 1;
				}
	  			var chunksize = (end - start) + 1;
				res.writeHead(206, {
					"Content-Range": "bytes " + start + "-" + end + "/" + filestat.size,
					"Accept-Ranges": "bytes",
					"Content-Length": chunksize,
					"Content-Type": mimetype
				});
				var filestream = fs.createReadStream(sandbox_full_path, { start: start, end: end });
				filestream.pipe(res);
			} else {
				console.log('File for sandbox does not seem to actually exist...');
				res.writeHead(404, {'Content-Type': 'text/plain'});
				res.end(JSON.stringify({ 'error': 'File not found' }));
			}
		});
			
	/*

		handle /thumb path

	*/
	} else if (thumb_regex.test(the_url.pathname)) {
		var thumb_matches = the_url.pathname.match(thumb_regex);
		//console.log(thumb_matches);
		var thumb_path = thumb_matches[1];
		var thumb_full_path = thumb_path_base + thumb_path;
		/*

			send along thumbnail if it exists

		*/
		fs.exists(thumb_full_path, function(exists) {
			if (exists) {
				console.log('sending along thumb ' + thumb_full_path);
				var filename = path.basename(thumb_full_path);
				var mimetype = mime.lookup(thumb_full_path);
				var filestat = fs.statSync(thumb_full_path);
				res.writeHead(200, { 'Content-disposition': 'attachment; filename=' + filename, 'Content-type': mimetype, 'Content-length': filestat.size });
				var filestream = fs.createReadStream(thumb_full_path);
				filestream.pipe(res);
			} else {
				console.log('Thumbnail does not seem to actually exist...');
				res.writeHead(404, {'Content-Type': 'text/plain'});
				res.end(JSON.stringify({ 'error': 'Thumbnail not found' }));
			}
		});


	/*

		handle /make-thumb path

	*/
	} else if (make_thumb_regex.test(the_url.pathname)) {
		var make_thumb_matches = the_url.pathname.match(make_thumb_regex);
		var make_thumb_mid = make_thumb_matches[1];
		var make_thumb_method = make_thumb_matches[2];
		console.log('making new thumb for entry '+make_thumb_mid+' with method '+make_thumb_method);
		/*

			make a new thumbnail via m6-cli.php

		*/
		var newthumb_script = spawn('php', ['-f', 'm6-cli.php', 'new-thumb', make_thumb_mid, make_thumb_method], { env: process.env });

		var newthumb_script_stdout = '';
		var newthumb_script_stderr = '';

		newthumb_script.stdout.on('data', function(data) {
			newthumb_script_stdout += data;
		});

		newthumb_script.stdout.on('end', function() {
			console.log('newthumb script final stdout: ' + newthumb_script_stdout);
		});

		newthumb_script.stderr.on('data', function(data) {
			newthumb_script_stderr += data;
		});

		newthumb_script.stderr.on('end', function() {
			console.log('newthumb script final stderr: ' + newthumb_script_stderr);
		});

		newthumb_script.on('close', function(code) {
			console.log('newthumb script exited with code ' + code);
			if (code == 0) {
				// everything is cool enough, send resulting data along to client
				console.log('sending success JSON data to the client');
				res.writeHead(200, {'Content-Type': 'application/json'});
				res.end(JSON.stringify({'success':'yes'}));
				console.log('all done !!!');
			} else {
				// there was an error that we need to take care of
				console.log('there was an error of some kind with the newthumb script');
				res.writeHead(500, {'Content-Type': 'application/json'});

				// what gets sent to the client?

				res.end(JSON.stringify({ 'error': 'there was a problem making the thumbnails' }));
				console.log('all done !!!');
			}
		});

		//newthumb_script.stdin.write();
		newthumb_script.stdin.end();

	/*

		handle /custom-image path

	*/
	} else if (custom_image_regex.test(the_url.pathname) && req.method.toLowerCase() == 'post') {
		// uploading a custom thumbnail or art? must be small (<200kb)
		// they must supply the intended path for it, save it there, add all_files_base to it
		// send back an "ok" when done

		// expect at least $_POST['path'] and $_POST['type']
		// if type == 'art', just put the file where it should go
		// if type == 'thumb', expect $_POST['mid'], and run the CLI custom-thumb command

		console.log('new custom image operation starting!');

		var custom_image_form = new formidable.IncomingForm();
		custom_image_form.keepExtensions = true;
		custom_image_form.uploadDir = tmp_path;
		var custom_image_file;
		var custom_image_type = '';
		var custom_image_path = '';
		var custom_image_mid = 0;

		custom_image_form.on('progress', function(brec, btotal) {
			// don't think i care about this...
		});

		custom_image_form.on('fileBegin', function(name, file) {
			//console.log('new custom image incoming: ' + name);
			//console.log(util.inspect(file, false, null));
		});

		custom_image_form.on('file', function(name, file) {
			//console.log('new custom image received: ' + name);
			//console.log(util.inspect(file, false, null));
			custom_image_file = file;
		});

		custom_image_form.on('field', function(name, value) {
			//console.log('new field detected: "'+name+'": '+value);
			if (name == 'path') {
				custom_image_path = value;
			} else if (name == 'type') {
				custom_image_type = value;
			} else if (name == 'mid') {
				custom_image_mid = value * 1;
			}
		});

		custom_image_form.on('aborted', function() {
			console.log('oops, upload aborted by user!');
			res.writeHead(404, {'content-type': 'text/plain'});
			res.end('User aborted the upload!');
		});

		custom_image_form.on('error', function(err) {
			console.log('error with uploading');
			console.log(err);
			res.writeHead(404, {'content-type': 'text/plain'});
			res.end(err);
		});

		custom_image_form.on('end', function() {

			// all done...?

			console.log('all done, what now?');

			if (custom_image_file == undefined) {
				console.log('no image was actually uploaded!');
				res.writeHead(500, {'Content-Type': 'text/plain'});
				res.end('no image was actually uploaded');
				return;
			}

			if (custom_image_type == '') {
				console.log('no image type was provided');
				res.writeHead(500, {'Content-Type': 'text/plain'});
				res.end('no image type was provided');
				return;
			}

			if (custom_image_path == '') {
				console.log('no image path was provided');
				res.writeHead(500, {'Content-Type': 'text/plain'});
				res.end('no image path was provided');
				return;
			}

			if (custom_image_file.size > 204800) {
				// too big!
				res.writeHead(500, {'Content-Type': 'text/plain'});
				res.end('uploaded image is too big!');
				return;
			}


			if (custom_image_type == 'art') {
				// move to where it should go
				console.log('dealing with art of some kind');
				console.log('moving from ' + custom_image_file.path + ' to ' + custom_image_path);
				fs.rename(custom_image_file.path, custom_image_path, function(err) {
					if (err) { console.log(err); }
					console.log('moved! done!');
					res.writeHead(200, {'Content-Type': 'text/plain'});
					res.end('ok');
				});
			} else if (custom_image_type == 'thumb') {
				console.log('dealing with a custom thumbnail');
				if (custom_image_mid == 0) {
					console.log('no image MID was provided');
					res.writeHead(500, {'Content-Type': 'text/plain'});
					res.end('no image MID was provided');
					return;
				}

				// ok so run m6-cli.php custom-thumb mid path
				// then delete the original file uploaded

				var custom_image_cli = spawn('php', ['-f', 'm6-cli.php', 'custom-thumb', custom_image_mid, custom_image_file.path], { env: process.env });

				var custom_image_cli_stdout = '';
				var custom_image_cli_stderr = '';

				custom_image_cli.stdout.on('data', function(data) {
					custom_image_cli_stdout += data;
				});

				custom_image_cli.stdout.on('end', function() {
					console.log('custom image CLI final stdout: ' + custom_image_cli_stdout);
				});

				custom_image_cli.stderr.on('data', function(data) {
					custom_image_cli_stderr += data;
				});

				custom_image_cli.stderr.on('end', function() {
					console.log('custom image CLI final stderr: ' + custom_image_cli_stderr);
				});

				custom_image_cli.on('close', function(code) {
					console.log('custom image CLI exited with code ' + code);
					if (code == 0) {
						// everything is cool
						console.log('success!');
						// delete original uploaded file now that it's done
						console.log('deleting uploaded custom thumbnail original image');
						fs.unlink(custom_image_file.path, function(err) {
							if (err) { console.log(err); }
						});
						res.writeHead(200, {'Content-Type': 'text/plain'});
						res.end('ok');
					} else {
						// there was an error that we need to take care of
						console.log('there was an error of some kind with processing the image');
						res.writeHead(500, {'Content-Type': 'text/plain'});
						res.end('there was a problem...');
					}
				});

				custom_image_cli.stdin.end();

			} else {
				// uhhhh
				res.writeHead(500, {'Content-Type': 'text/plain'});
				res.end('wat?');
			}
		});

		custom_image_form.parse(req);

	/*

		handle /file-info path

	*/
	} else if (fileinfo_regex.test(the_url.pathname)) {
		var fileinfo_matches = the_url.pathname.match(fileinfo_regex);
		var fileinfo_mid = fileinfo_matches[1] * 1;

		console.log('getting file info for mid #'+fileinfo_mid);

		var fileinfo_script = spawn('php', ['-f', 'm6-cli.php', 'get-file-info', fileinfo_mid, 'json'], { env: process.env });

		var fileinfo_script_stdout = '';
		var fileinfo_script_stderr = '';

		fileinfo_script.stdout.on('data', function(data) {
			fileinfo_script_stdout += data;
		});

		fileinfo_script.stdout.on('end', function() {
			console.log('fileinfo script final stdout: ' + fileinfo_script_stdout);
		});

		fileinfo_script.stderr.on('data', function(data) {
			fileinfo_script_stderr += data;
		});

		fileinfo_script.stderr.on('end', function() {
			console.log('fileinfo script final stderr: ' + fileinfo_script_stderr);
		});

		fileinfo_script.on('close', function(code) {
			console.log('fileinfo script exited with code ' + code);
			if (code == 0) {
				// everything is cool enough, send resulting data along to client
				console.log('sending this data to the client');
				console.log(fileinfo_script_stdout);
				res.writeHead(200, {'Content-Type': 'application/json'});
				res.end(fileinfo_script_stdout);
				console.log('all done !!!');
			} else {
				// there was an error that we need to take care of
				console.log('there was an error of some kind with the fileinfo script');
				res.writeHead(500, {'Content-Type': 'application/json'});

				// what gets sent to the client?

				res.end(JSON.stringify({ 'error': 'there was a problem getting the file info' }));
				console.log('all done !!!');
			}
		});

		//fileinfo_script.stdin.write();
		fileinfo_script.stdin.end();

	/*

		handle /mid-filesize path

	*/
	} else if (mid_filesize_regex.test(the_url.pathname)) {
		var filesize_matches = the_url.pathname.match(mid_filesize_regex);
		var filesize_mid = filesize_matches[1] * 1;

		console.log('getting total file size for mid #'+filesize_mid);

		var filesize_script = spawn('php', ['-f', 'm6-cli.php', 'get-mid-filesize', filesize_mid], { env: process.env });

		var filesize_script_stdout = '';
		var filesize_script_stderr = '';

		filesize_script.stdout.on('data', function(data) {
			filesize_script_stdout += data;
		});

		filesize_script.stdout.on('end', function() {
			//console.log('filesize script final stdout: ' + filesize_script_stdout);
		});

		filesize_script.stderr.on('data', function(data) {
			filesize_script_stderr += data;
		});

		filesize_script.stderr.on('end', function() {
			//console.log('filesize script final stderr: ' + filesize_script_stderr);
		});

		filesize_script.on('close', function(code) {
			console.log('filesize script exited with code ' + code);
			if (code == 0) {
				// everything is cool enough, send resulting data along to client
				console.log('sending this data to the client: ');
				console.log(filesize_script_stdout);
				res.writeHead(200, {'Content-Type': 'text/plain'});
				res.end(filesize_script_stdout);
				console.log('all done !!!');
			} else {
				// there was an error that we need to take care of
				console.log('there was an error of some kind with the filesize script');
				res.writeHead(500, {'Content-Type': 'text/plain'});
				res.end('error');
			}
		});

		//filesize_script.stdin.write();
		filesize_script.stdin.end();

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
}, upload_garbage_collection_frequency);

// run file operation queue
setInterval(function() {

	console.log('starting file operations queue processing script');

	var file_queue_script = spawn('php', ['-f', 'm6-cli.php', 'do-file-queue'], { env: process.env });

	var file_queue_script_stdout = '';
	var file_queue_script_stderr = '';

	file_queue_script.stdout.on('data', function(data) {
		file_queue_script_stdout += data;
	});

	file_queue_script.stdout.on('end', function() {
		//console.log('file ops queue script final stdout: ' + file_queue_script_stdout);
	});

	file_queue_script.stderr.on('data', function(data) {
		file_queue_script_stderr += data;
	});

	file_queue_script.stderr.on('end', function() {
		//console.log('file ops queue script final stderr: ' + file_queue_script_stderr);
	});

	file_queue_script.on('close', function(code) {
		console.log('file ops queue script exited with code ' + code);
		if (code == 0) {
			// everything is cool enough, good
			console.log(file_queue_script_stdout);
			console.log('all done !!!');
		} else {
			// there was an error that we need to take care of
			console.log('there was an error of some kind with the file ops queue script');
			console.log(file_queue_script_stderr);
		}
	});

	//file_queue_script.stdin.write();
	file_queue_script.stdin.end();

}, file_ops_queue_frequency);

// run cleanup jobs
setInterval(function() {

	console.log('starting enable-and-cleanup entries script');

	var mids_cleanup_script = spawn('php', ['-f', 'm6-cli.php', 'fix-and-cleanup-mids'], { env: process.env });

	var mids_cleanup_script_stdout = '';
	var mids_cleanup_script_stderr = '';

	mids_cleanup_script.stdout.on('data', function(data) {
		mids_cleanup_script_stdout += data;
	});

	mids_cleanup_script.stdout.on('end', function() {
		//console.log('enable-and-cleanup entries script final stdout: ' + mids_cleanup_script_stdout);
	});

	mids_cleanup_script.stderr.on('data', function(data) {
		mids_cleanup_script_stderr += data;
	});

	mids_cleanup_script.stderr.on('end', function() {
		//console.log('enable-and-cleanup entries script final stderr: ' + mids_cleanup_script_stderr);
	});

	mids_cleanup_script.on('close', function(code) {
		console.log('enable-and-cleanup script exited with code ' + code);
		if (code == 0) {
			// everything is cool enough, good
			console.log(mids_cleanup_script_stdout);
			console.log('all done !!!');
		} else {
			// there was an error that we need to take care of
			console.log('there was an error of some kind with the enable-and-cleanup entries script');
			console.log(mids_cleanup_script_stderr);
		}
	});

	//mids_cleanup_script.stdin.write();
	mids_cleanup_script.stdin.end();

}, cleanup_ops_queue_frequency);

console.log('Median 6 File API running at http://0.0.0.0:'+listen_port+'/');
