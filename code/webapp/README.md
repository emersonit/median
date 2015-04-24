# Median Web Application tier

The web application tier runs on port *8080*.

## Installation + Configuration

See the Median Web App Deployment documentation for detailed info on deploying a new webapp server.

Once that's done, do the following:

In the `config` directory, rename `median-lighty.sample.conf` to `median-lighty.conf` and edit for your environment. Areas for config are marked with double exclamation points (!!).

In the `config` directory, rename `config.sample.php` to `config.php` and edit for your environment.

In the `includes` directory, rename `login_check.sample.php` to `login_check.php` and edit for your environment. See also the "user-authentication.md" documentation.

You'll also want to configure SimpleSAMLphp. First rename `lib/simplesaml/config/config.sample.php` to `lib/simplesaml/config/config.php` and edit at least everywhere that has a comment with double exclamation points (!!). Also, make sure `lib/simplesaml` is a symlink to `lib/simplesamlphp-1.11.0`

In the `www/js` directory, edit `median.js` to match your environment, there should just be the `home_url` variable.

## Folders

- `config` contains special configuration files for the webapp
- `includes` contains webapp application code, no views, lots of logic
- `lib` contains third-party libraries, like SimpleSAMLphp for authentication and LTI integration
- `www` contains all front-facing files, mostly views and logic

## Edit Stuff

There are links to Emerson College stuff in the `includes/header.php` + `includes/footer.php` files that I'm sure you'll want to edit. Most of the customizable/institution-specific links and text has been abstracted into `config/config.php`

But, for example, you'll want to change it so you're using Google's (or your own) CDN for the Open Sans and Lato fonts, and not the Emerson College CDN.