# Median File API

The Median file API handles all file-related functions, such as:

- handling file uploads
- handling file downloads
- thumbnail retrieval and generation (using the Python Imaging Library)
- retrieving video/audio/image info
- and MORE

The Node.js `m6-file-api.js` process sits on port `9090` waiting for file operations to perform. The API is accessed both by the webapp layer directly and by client users directly (via the `median.emerson.edu/files/` URL path).

The Node.js process takes incoming requests and performs actions itself, or spawns child processes via scripts in the same directory.

For example, uploaded files get processed through Node.js for speed and low memory footprint, and then processed by `upload_processor.php` behind-the-scenes.

There's also a folder of `tools` that may be helpful; all of them are intended for use via the command line.

Also, the `m6-cli.php` script contains many helpful file-related functions that can be used by admins at the command line; it's also used by the Node.js process to do many functions.

## Installation + Configuration

See the Median File API Deployment documentation/scripts for detailed info on deploying a new file API server.

Once that's done, do the following:

In the `includes` directory, rename `config.sample.php` to `config.php`, and edit it to mirror your environment.

In the `includes` directory, rename `dbconn_mongo.sample.php` to `dbconn_mongo.php`, and edit it to mirror your environment.

Also edit `m6-file-api.js` to match that file and your environment.

## Running the File API

I recommend using an always-on Node.js daemon like [forever](https://github.com/foreverjs/forever) or [pm2](https://github.com/Unitech/pm2) to keep the API running.

Otherwise, run `node m6-file-api.js` at the command line.