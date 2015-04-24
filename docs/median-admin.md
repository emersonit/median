# Median Administration

Median admins can see, edit, and delete any entry on Median. There is an admin panel with many common tools/functions located at /admin/

Access is governed by the user record in Median; the user record in MongoDB must have an `o_ul` attribute set to `1`. (This overrides a user's level to 1, which is admin.)

## Editing Things

The *Edit Help Pages*, *Edit Global Alerts*, and *Edit News* panels are pretty self-explanatory.

## Logs

There are only two relevant logs available on the Median Admin page: the *Raw New Media Entry List* which shows an unfiltered list of the most recently uploaded entries (the list on the main page of median has filters whether you're an admin or not), and the *Bailout Log*, which is a list of the most recent error messages users have seen, what URL they were at, and who saw it (if they were logged in).

Each web application server also has its own PHP and lighttpd logs, in the normal locations in /var/log.

## Other Tools

The *Fix Entries that should be Enabled* tool is a little obsolete, so don't worry about it -- every five minutes, Median is doing this automatically by itself. The tool is still here in case of emergency. What this tool does is enable without question new uploads that "should" be enabled. This can happen if Median thinks an error happened during transcoding, but no such error actually happened.. it's confusing but Handbrake is confusing.

The *Farming Admin* page allows you to enable/disable median nodes, and run the *farming check script* to try restarting farm jobs in case they get stuck.

## Median Admin Command Line Tool

If you have SSH access to a Median file server, you can go to the `/median-file-api/` directory and run `php m6-cli.php` which has some command line arguments for more low-level tasks.

For example, running `php m6-cli.php get-media-info 12456` will spit out the raw MongoDB record for that Median ID.

There a lot more options, which you can see by just running `php m6-cli.php` in the shell.
