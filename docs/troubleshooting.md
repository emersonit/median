# Troubleshooting Median

**A video entry plays for a second and then stops, or does not play at all, but other entries do play!**

The most likely scenario is that one of the "versions" of the video isn't compatible with Median's streaming server. To check, go to the entry's page on Median, log in as admin, go to the Advanced Info section under the video, and check to see if one of the quality versions is "Original File". You should also see a "Mobile", "SD", and maybe even "HD" and more versions. If there are more than one version, as in the "Original File" is NOT the only one, then try deleting it. That'll probably fix it. Click "remove this version" next to whichever one is the "Original File".

**All videos won't play**

Try restarting nginx-rtmp on the streaming servers. Logged in as root, run `restart nginx-rtmp` and wait a second, it should be fairly instantaneous. Or if you're using Fabric, run `fab restart_streaming`.

**Pages won't load or error out**

Try restarting `lighttpd` on the webapp servers. Logged in as root, run `service lighttpd restart` and `service php5-fpm restart` and wait to see if any errors pop up. Check the PHP error logs, too. Or if you're using Fabric, run `fab restart_webapp_services`.

**Users can't upload files via the Uploader**

Most likely the upload node.js scripts aren't running on the file API servers. On them, type in `forever list` to see if the node.js scripts are running. You can restart the process, if it's running, by typing in `forever restart 0`, or start from scratch by typing `forever stopall` and then `forever start /median-file-api/m6-file-api.js`. Or if you're using Fabric, run `fab restart_file_api`.

**People cannot log in or there are other login-related issues**

Median uses simpleSAML single sign-on for authentication, so make sure the identity provider server is working and configured correctly. If it's still failing, simpleSAMLphp is installed in `/median-webapp/lib/simplesaml/` on the web app layer servers if you want to check the local service provider.

**People aren't seeing their classes**

Class information is loaded to a database on the MongoDB cluster to act as a cache, which updates daily. It's possible that the sync did not take place or does not have the latest data.

**General tips and dependencies**

The file and streaming servers rely on an NFS mount for file storage, so make sure that's full of files by doing a simple <code>ls -lah /median</code> on a streaming or file API server. If there isn't anything within /median, that's probably the problem. run <code>mount -a</code> on the streaming/file servers and see if anything happens. If that doesn't work, there's probably something wrong with the NFS share.