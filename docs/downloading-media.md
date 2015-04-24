# Downloading Media backend workflow...

1. User goes to the media entry player page, clicks "download", OR has a direct link to https://median.emerson.edu/download/10101/
2. Webapp-side download.php script checks to see if the user has permission to download
3. If they don't, error out and end here. If they do, generate a unique token (ex: a9181-191fd-addj22) and put it in the database.
4. Forward user to https://median.emerson.edu/files/download/10101/a9181-191fd-addj22/ which goes to the file API
5. File API checks the download token, sees it's legit, and allows the download. Deletes the token from the database.

That's it!

## References

- v4 UUID in PHP: http://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid