/*

	the median live CD / PXE boot farmer node script

*/

// configuration variables, very important

// where is the NFS location of the remote working area, if we need it
var nfs_address = 'median-pxe.emerson.edu:/median-workarea';

// the base hostname to your median instance
var median_url = 'median.emerson.edu';

// the local path to where the files will end up when they're done, which will be an NFS mount, make sure it's kept active
var out_dir_base = '/median-files';

// this is the mount command to mount the median file storage NFS share
var out_dir_mount_cmd = 'mount -t nfs -o nfsvers=3,rw,hard,intr,rsize=65536,wsize=65536,noatime,tcp,timeo=20 bin.emerson.edu:/ifs/median '+out_dir_base;

// end of config variables

/*
	
	how it works:
	
	get this machine's IP, set a node name for yourself based on that
	
	check if you can reach median.emerson.edu and odin.emerson.edu and median-pxe.emerson.edu
	
	use os.totalmem() or `df --total /` to figure out whether to use local storage or remote nfs storage
		make directory /stuff
		if local is big enough, just build folders as usual
		if not, use remote, mount median-pxe.emerson.edu:/median-workarea to /stuff, then proceed as usual
	
	make work folder in /stuff based on node ID
		so work folder would be /stuff/median-node-10.24.16.222/ with subdirs in/ and out/ and log/
	
	maybe: use os.cpus() to figure out whether to take on TWO handbrake jobs at once?
		
*/

// debugmode will toggle console output
var debugmode = false;

// require a bunch of libraries and crap, all built into node.js
var http = require('http');
var https = require('https');
var os = require('os');
var fs = require('fs');
var sys = require('sys')
var exec = require('child_process').exec;
var spawn = require('child_process').spawn;
var util = require('util');
var path = require('path');

// set up a function to do the logging
function logger(wut, thelog) {
	if (debugmode) console.log(wut);
	thelog.write(wut+"\n");
}

// set up some variables that'll be used all over the place
var free_space_required = 8; // how many gigabytes of free space required to use the local filesystem?
var farmer_install_path = '/opt/farmer'; // where is the farmer script actually installed?
var working_dir_base = '/stuff'; // where is the working directory?
var working_dir_path = working_dir_base + '/'; // we'll set this up in a minute, based on this node's ID
var job_data = { 'jobs': 0 }; // this will hold the current job data
var working = false; // is this node working right now? not to start with, no.
var farmer_log = fs.createWriteStream(farmer_install_path + '/farmer.log', { flags: 'a', mode: 0660 }); // the application log
var job_log; // this'll hold where the current job's log is

// some regexs that we'll use to analyze handbrake's output
var hb_bad_title = /scan thread found 0 valid title/mgi;
var hb_stream_pattern = /(hb_stream_open:)(.+)(failed)/mgi;
var hb_zeropercent_pattern = /( 0.00 %)/gmi;
var hb_encoding_pattern = /Encoding: /gi;

// start off the logging
var rightnow = new Date();
logger('=== farmer starting at '+rightnow.toString(), farmer_log);


// oh, that's how...

/*

	set up this node's name

*/

var my_name = 'median-node-';
// get this machines IP
var current_interfaces = os.networkInterfaces();
var my_ip = '';
for (var i in current_interfaces) {
  for (var j = 0; j < current_interfaces[i].length; j++) {
    if (current_interfaces[i][j]['internal'] == false && current_interfaces[i][j]['family'] == 'IPv4') {
      my_ip = current_interfaces[i][j]['address'];
    }
  }
}
logger('my IP is: ' + my_ip, farmer_log);
my_name += my_ip; // use IP for uniqueness
working_dir_path = working_dir_path + my_name; // update this node's working dir with the name of this node
logger('my name is ' + my_name, farmer_log);

/*

	okay done setting name -- define the process with functions

*/

logger('waiting 10 seconds before starting...', farmer_log);

setTimeout(start, 10 * 1000); // wait 10 secs

function start() {
	logger('ok actually starting now', farmer_log);
	checkMedian(); // start with this
}

function checkMedian() {
	logger('checking median', farmer_log);
	// check if this machine can access median
	https.get('https://'+median_url+"/farm/ping.php", function(res) {
		if (res.statusCode == 200) {
			// okay, median is accessible, keep going
			logger('median accessible, yay', farmer_log);
			mountOutputDir(); // go on to checking if the output dir is mounted properly
		}
	}).on('error', function(e) {
		logger('Median inaccessible, cannot function...', farmer_log);
	});
}

function mountOutputDir() {
	logger('attempting to mount remote filesystem for final job output', farmer_log);
	if (fs.existsSync(out_dir_base) == false) {
		fs.mkdirSync(out_dir_base); // make the directory first
	}
	exec(out_dir_mount_cmd, function(error, stdout, stderr) {
		if (error) {
			logger('error mounting '+out_dir_mount_cmd, farmer_log);
			logger(error, farmer_log);
			logger(stderr, farmer_log);
			return;
		}
		logger('mounted final job output to '+out_dir_base, farmer_log);
		// keep going
		checkOutputDir();
	});
}

function checkOutputDir() {
	logger('checking the output directory '+out_dir_base, farmer_log);
	exec("ls "+out_dir_base, function(error, stdout, stderr) {
		if (error) {
			logger('error checking output directory', farmer_log);
			logger(error, farmer_log);
			return;
		}
		if (stdout != '') {
			logger('output dir accessible, yay', farmer_log);
			// keep going
			checkWorkingDir(); // see if we have a working directory already
		} else {
			logger('ERROR: output dir appears to be empty, something is wrong', farmer_log);
		}
	});
}

function checkDiskSpace() {
	logger('checking local disk space', farmer_log);
	exec("df --total /", function(error, stdout, stderr) {
		if (error) {
			logger('error getting root filesystem size', farmer_log);
			logger(error, farmer_log);
			return;
		}
		logger('disk space check output: ', farmer_log);
		logger(stdout, farmer_log);
		var spaceregex = /total\s+(\d+)\s+(\d+)\s+(\d+)/gi;
		var m = spaceregex.exec(stdout);
		var gb_free = (m[3] * 1) / 1024 / 1024;
		var gb_free_rounded = Math.round(gb_free * 10) / 10;
		logger(gb_free_rounded + 'GB free in root filesystem', farmer_log);
		if (gb_free_rounded > free_space_required) {
			// totally enough free space to use locally
			logger('using local filesystem', farmer_log);
			checkThisNodeDirectory(); // okay then, build out the working directories
		} else {
			// not enough free space -- go with the remote filesystem
			logger('not enough space locally, trying remote filesystem', farmer_log);
			tryRemoteSystem(); // okay then, try mounting the external NFS share
		}
	});
}

function checkWorkingDir() {
	logger('checking if '+working_dir_base+' exists', farmer_log);
	fs.exists(working_dir_base, function(exists) {
		if (exists) {
			// delete it
			deleteWorkingDir();
		} else {
			// go straight to creating it
			createBaseWorkingDir();
		}
	});
}

function deleteWorkingDir() {
	logger('deleting ' + working_dir_base, farmer_log);
	exec("rm -rf " + working_dir_base, function(error, stdout, stderr) {
		if (error) {
			logger('error deleting '+working_dir_base+' directory', farmer_log);
			logger(error, farmer_log);
			return;
		}
		logger('working dir base ' + working_dir_base + ' deleted', farmer_log);
		// keep going
		createBaseWorkingDir(); // create a new fresh working directory
	});
}

function createBaseWorkingDir() {
	logger('creating ' + working_dir_base, farmer_log);
	fs.mkdir(working_dir_base, function(error) {
		if (error) {
			logger('error creating '+working_dir_base+' directory', farmer_log);
			logger(error, farmer_log);
			return;
		}
		logger('working dir base ' + working_dir_base + ' created', farmer_log);
		// keep going
		checkDiskSpace(); // check to see disk space situation
	});
}

function checkThisNodeDirectory() {
	logger('checking for unique folder for this node in working directory base', farmer_log);
	fs.exists(working_dir_path + '/' + my_name, function(exists) {
		if (exists) {
			// delete it
			deleteThisNodeDir();
		} else {
			// go straight to creating it
			buildWorkingDirs();
		}
	});
}

function deleteThisNodeDir() {
	logger('deleting ' + working_dir_path, farmer_log);
	exec("rm -rf " + working_dir_path, function(error, stdout, stderr) {
		if (error) {
			logger('error deleting '+working_dir_path+' directory', farmer_log);
			logger(error, farmer_log);
			return;
		}
		logger('unique node working dir ' + working_dir_path + ' deleted', farmer_log);
		// keep going
		buildWorkingDirs(); // build out working paths
	});
}

function buildWorkingDirs() {
	logger('building working directory and paths under ' + working_dir_path, farmer_log);
	// first build folder named my_name inside working_dir_path
	// then build in/ out/ and log/
	fs.mkdir(working_dir_path, function(error) {
		if (error) {
			logger('error creating '+working_dir_path+' directory', farmer_log);
			logger(error, farmer_log);
			return;
		}
		logger('unique working dir ' + working_dir_path + ' created', farmer_log);
		fs.mkdir(working_dir_path + '/in', function(error) {
			if (error) {
				logger('error creating in directory', farmer_log);
				logger(error, farmer_log);
				return;
			}
			logger('in directory created', farmer_log);
			
			fs.mkdir(working_dir_path + '/out', function(error) {
				if (error) {
					logger('error creating out directory', farmer_log);
					logger(error, farmer_log);
					return;
				}
				logger('out directory created', farmer_log);
				
				fs.mkdir(working_dir_path + '/log', function(error) {
					if (error) {
						logger('error creating log directory', farmer_log);
						logger(error, farmer_log);
						return;
					}
					logger('log directory created', farmer_log);
					// keep going now
					runFarming(); // all set to start farming
				}); // end building log path
			}); // end building out path
		}); // end building in path
	}); // end building working path
}

function tryRemoteSystem() {
	logger('attempting to mount remote filesystem for working directory base', farmer_log);
	// try mounting median-pxe.emerson.edu:/median-workarea to /stuff
	// mount -t nfs median-pxe.emerson.edu:/median-workarea /stuff
	exec("mount -t nfs " + nfs_address + " " + working_dir_base, function(error, stdout, stderr) {
		if (error) {
			logger('error mounting '+nfs_address+' to '+working_dir_base, farmer_log);
			logger(error, farmer_log);
			logger(stderr, farmer_log);
			return;
		}
		logger('mounted '+nfs_address+' to '+working_dir_base, farmer_log);
		// keep going
		checkThisNodeDirectory();
	});
}

function runFarming() {
	logger('Let the farming begin!', farmer_log);
	// first set up the http server
	http.createServer(function (req, res) {
		res.writeHead(200, {'Content-Type': 'text/plain'});
		var output_data = { node_info: { name: my_name, ip: my_ip }, current_job: job_data }
		res.end(JSON.stringify(output_data));
	}).listen(80);
	logger('http server started...', farmer_log);
	// every 60 seconds, if it's not already working on something, check if there's something new to work on
	setInterval(cycle, 60000);
	// every five minutes, send a heartbeat to the server....
	setInterval(heartbeat, (5*60000));
	heartbeat(); // and send a heartbeat immediately
	logger('farming cycle started...', farmer_log);
}

function heartbeat() {
	var heart_options = {
		host: median_url,
		port: 443,
		path: '/farm/checkin/'
	};
	https.get(heart_options, function(res) {
		// ok, sent
		if (debugmode) console.log('sent heartbeat!');
	}).on('error', function(e) {
		//console.log("Got error: " + e.message);
		logger('error trying to send heartbeat to median', farmer_log);
	});
}


function cycle() {
	// this is the cycle that runs regularly to figure out if there's a job to do
	if (working) {
		return; // already working, don't need a new job!
	}
	// we send this to median to get a job
	var get_options = {
		host: median_url,
		port: 443,
		path: '/farm/getjob/'
	};
	if (debugmode) console.log('farmer, lookin for a job...');
	https.get(get_options, function(res) {
		//if (debugmode) console.log("Got response: " + res.statusCode);
		if (res.statusCode != 200) {
			//console.log('uh oh!');
			logger('error: got this code when trying to get a job: ' + res.statusCode, farmer_log);
		} else {
			var data_result = '';
			res.on('data', function(chunk) {
				data_result += chunk;
			});
			res.on('end', function() {
				// parse the returned data to get a job to do
				if (debugmode) console.log('server returned: '+data_result);
				if (data_result == 'error') {
					logger('error trying to get job from median', farmer_log);
				} else {
					// cool, no error -- it must be job data!
					// eval the job data into the job_data variable
					eval('job_data = (' + data_result + ')');
					if (job_data.jobs > 0) {
						console.log('got a job! job id ' + job_data.jid);
						working = true; // we are now working
						doJob(job_data); // do a job
					} else {
						if (debugmode) console.log('no jobs, or not allowed');
					}
				}
			});
		}
	}).on('error', function(e) {
		//console.log("Got error: " + e.message);
		logger('error trying to get job from median', farmer_log);
	});
}

function doJob(job) {
	// first of all, create a log file for this job
	job_log = fs.createWriteStream(working_dir_path+'/log/job_'+job.jid+'.log', { flags: 'a', mode: 0660 });
	// set up some variables for processing this job
	var dojob_rightnow = new Date();
	var zeropercent_count = 0;
	var error = false;
	var error_msg = '';
	logger('new job at ' + dojob_rightnow.toString(), job_log);
	logger('job data: ' + util.inspect(job), job_log);
	// now we're going to SCP the file from median to this node and work on it
	var inbound_path = job['in'].replace('/median/', '/median-files/');
	if (debugmode) console.log('inbound path: '+inbound_path);
	// now do the SCPing:
	var cp_in = spawn('cp', [ inbound_path, working_dir_path+'/in']);
	cp_in.stderr.on('data', function (data) {
		logger('cp_in stderr: ' + data, job_log);
	});
	cp_in.on('exit', function (code) {
		// okay, SCP done, what happened?
		logger('cp_in exited with code: ' + code, job_log);
		if (code == 0) {
			// everything worked out great, let's do some stuff with the file now
			var inpath = working_dir_path + '/in/' + path.basename(job['in']);
			var outpath = working_dir_path + '/out/' + job.mid + '.mp4';
			// start handbrake!
			var hb = spawn('HandBrakeCLI', ['-i', inpath, '-o', outpath, '-e', 'x264', '-E', 'faac', '-b', job.vb, '-B', job.ab, '-2', '-T', '-6', 'stereo', '-R', '44.1', '-X', job.vw, '-Y', job.vh]);
			hb.stdout.on('data', function (data) {
				// we're getting data back from handbrake, interpret it!
				logger('hb stdout: ' + data, job_log);
				var currentline = '' + data + '';
				// this is where i'd need to catch the "0.00 %" bug -- allow 75 of them before aborting
				// 0.00 % is due to handbrake not knowing what the fuck the file is
				if (zeropercent_count == 75) {
					logger('too many zero percents!', job_log);
					error = true;
					error_msg = 'There was an error transcoding: too many 0.00%.';
					hb.kill();
				} else if (hb_zeropercent_pattern.test(currentline)) {
					zeropercent_count++;
				}
				// stdout typically looks like this: Encoding: task 2 of 2, 97.28 % (11.86 fps, avg 5.93 fps, ETA 00h00m11s)
				if (hb_encoding_pattern.test(currentline)) {
					job_data.hbinfo = currentline;
				}
			});
			hb.stderr.on('data', function (data) {
				// we're getting data back from handbrake, interpret it!
				logger('hb stderr: ' + data, job_log);
				var currentline = '' + data + '';
				// this is where i'd need to catch the "hb_stream_open: open ...(path)... failed" bug
				// dunno why this bug happens...?
				if (hb_stream_pattern.test(currentline)) {
					// abort...
					logger('hb_stream_open failure!!!!', job_log);
					error = true;
					error_msg = 'There was an error transcoding: hb_stream_open failure.';
					hb.kill();
				} else if (hb_bad_title.test(currentline)) {
					logger('no valid title HB failure!', job_log);
					error = true;
					error_msg = 'There was an error transcoding: no valid title found.';
					hb.kill();
				}
			});
			hb.on('exit', function (code) {
				// either it's done or we killed it, so deal with the results
				logger('hb exited with code: ' + code, job_log);
				if (code == 0) {
					// exited cleanly, but there may have been an error
					if (error) {
						// there was an error...
						logger('there was an error with encoding!', job_log);
						if (error_msg == '') {
							error_msg = 'There was an error transcoding the file.';
						}
						// send the status update to median
						sendStatus(job.jid, 3, error_msg);
						// destroy the files
						fs.unlink(inpath);
						fs.unlink(outpath);
					} else {
						// no error! send file back to median.......
						logger('sending it back to median!', job_log);
						// use SCP to send it back
						var outbound_path = job['out'].replace('/median/', '/median-files/');
						if (debugmode) console.log('outbound cp path: '+outbound_path);
						// do the transfer
						var cp_out = spawn('scp', [outpath, outbound_path]);
						cp_out.stdout.on('data', function (data) {
							logger('cp_out stdout: ' + data, job_log);
						});
						cp_out.stderr.on('data', function (data) {
							logger('cp_out stderr: ' + data, job_log);
						});
						cp_out.on('exit', function (code) {
							// when it's done, parse the results
							logger('cp_out exited with code: ' + code, job_log);
							if (code == 0) {
								// exited cleanly, all done! update median with the job status
								var filestat = fs.statSync(outpath);
								sendStatus(job.jid, 2, filestat['size']);
								// get rid of the files on here
								fs.unlink(inpath);
								fs.unlink(outpath);
							} else {
								// error with scp_out exit
								logger('there was an error with copying the file out!', job_log);
								// update median with the error status
								sendStatus(job.jid, 3, 'There was an error copying the file out to Median.');
								// get rid of the files on here
								fs.unlink(inpath);
								if (fs.existsSync(outpath)) { fs.unlink(outpath); }
							} // end of scp_out exit code if
						}); // end scp_out process on exit
					} // end if error
				} else {
					// error with handbrake exit
					logger('there was an error with handbrake!', job_log);
					sendStatus(job.jid, 3, 'There was an error transcoding the file.');
					// remove local copies of media
					fs.unlink(inpath);
					if (fs.existsSync(outpath)) { fs.unlink(outpath); }
				} // end of handbrake exit code if
			}); // end handbrake process on exit
		} else {
			// error with scp_in exit
			logger('there was an error with copying the file in!', job_log);
			// update median
			sendStatus(job.jid, 3, 'There was an error copying the file to the farmer.');
		} // end of scp_in exit code if
	}); // end of scp_in process on exit
} // end doJob()

function sendStatus(jid, status, message) {
	// send job status to median
	// with error message if need be
	var result = { };
	result.jid = jid;
	result.s = status * 1;
	if (message != undefined && message != '') {
		result.m = message; // there's a message! include it.
	}
	// this is the actual POST data that'll be sent to median, as JSON
	var postdata = 'j='+JSON.stringify(result);
	if (debugmode) console.log('sending POST: '+postdata);
	var postlength = postdata.length; // calculate post length for the content-length HTTP header
	var sendinfo_opts = {
		method: 'POST',
		path: '/farm/setjob/',
		host: median_url,
		port: 443,
		headers: { 'Content-Length': postlength, 'Content-Type': 'application/x-www-form-urlencoded' }
	};
	// build the new HTTP request to send
	var sendinfo = https.request(sendinfo_opts, function(newres) {
		//console.log('sent to batch temp!');
		var data_result = '';
		newres.on('data', function(chunk) {
			//console.log('from batch temp: '+chunk+'');
			data_result += chunk;
		});
		newres.on('end', function() {
			// parse median's response
			if (data_result != 'ok') {
				logger("Got error back from updating status: " + data_result, job_log);
			} else {
				logger('OH MY GOD DONE WITH THAT JOB', job_log); // ALL DONE!
			}
			// get rid of log file handlers
			job_log.end();
			job_log.destroySoon();
			// we are no longer working, awesome!
			working = false;
			job_data = { 'jobs': 0 };
		});
	});
	sendinfo.write(postdata);
	sendinfo.end(); // send the damn thing to median
}
