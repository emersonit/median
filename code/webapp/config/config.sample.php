<?php

/*

    median configuration for PHP scripts

*/

// the base URL for this installation of median
// requires a trailing slash
$median_base_url = 'https://median.emerson.edu/';

// the name of your institution, it'll show up in page titles
$median_institution_name = 'Emerson College';

// a URL to a place for help/support requests
$median_outside_help = 'http://it.emerson.edu/help/';

// link to your electronic information policy
$electronic_policy_page_url = 'http://www.emerson.edu/policy/electronic-information';

// link to your copyright policy
$copyright_policy_page_url = 'http://www.emerson.edu/policy/intellectual-property';

// the full path to the median error page
$error_page_path = '/median-webapp/www/error.php';

// where to keep median log files
$logs_dir = '/median/logs/';

// farming/video version presets...
// video bitrate, width, height, audio bitrate
$tiers = array(
	'ultra' => array('vb' => 1700, 'vw' => 1280, 'vh' => 720, 'ab' => 128),
	'high' => array('vb' => 1200, 'vw' => 1280, 'vh' => 720, 'ab' => 128),
	'medium' => array('vb' => 600, 'vw' => 720, 'vh' => 480, 'ab' => 96),
	'small' => array('vb' => 300, 'vw' => 400, 'vh' => 260, 'ab' => 64)
);

// the file path for where median saves its thumbnails
$thumbs_path = '/median/thumbs/';

// the base URL path for where median serves thumbnails
$thumbs_base_url = '/files/thumb/';

// the file path to where HTML5 symlinks are stored
$html5_file_base = '/median/files/html5/';

// the URL base path for HTML5 playback
$html5_url_base = $median_base_url.'files/html5/';

// the URL to the load-balanced VOD service
$median_rtmp_base = 'rtmp://median.emerson.edu/vod/';

// the URLs to median's streaming nginx-rtmp servers
$nginx_rtmp_servers = array('m6-streaming-1.your-server.com', 'm6-streaming-2.your-server.com');

// the file path for the median file sandbox
$sandbox_base_path = '/median/files/sandbox/';

// the URL base path for sandbox downloads
$sandbox_base_url = $median_base_url.'files/sandbox/';

// the regular expressions used to capture the timecodes used by each format
$srt_timecode_regex = '/(\d{2}:\d{2}:\d{2},\d+) --> (\d{2}:\d{2}:\d{2},\d+)/i';
$vtt_timecode_regex = '/(\d{2}:\d{2}:\d{2}\.\d+) --> (\d{2}:\d{2}:\d{2}\.\d+)/i';

// any entry with a copyright owner in this array will be allowed over HTML5
// despite the normal security restrictions
// useful if you want to copyright your college's work but still want HTML5 access
$acceptable_html5_copyright_bypasses = array('emerson college', 'emerson', 'emerson channel');

// a Median group ID to control access to the /itg/ tools
// at Emerson, this was the Instructional Technology Group, hence "itg"
$itg_group_id = 12;

// email settings
// the email address to use as FROM
$mail_from = 'median@your-site.com';

// the email address to send warnings/info to
// for example, if a median farmer goes down, this is notified
$mail_to = 'median-problems@your-site.com';

// the SMTP mail server to use to send emails
$mail_smtp_server = 'smtp.your-site.com';

/*

    akamai config
	
	if you don't use akamai, don't worry about it
    
*/

// if you are using akamai -- your FTP information
$akamai_ftp_host = 'upload.akamai.com';
$akamai_ftp_user = 'your-username';
$akamai_ftp_pass = 'your-password';
$akamai_ftp_path_prefix = '/file/base/path';

// the URL to your akamai service
$akamai_server_url = 'rtmp://xxx.edgefcs.net/ondemand/';

// these live stream IDs are reserved for akamai
// this is something custom you may have to set up
$akamai_streams = array(); // array of IDs, i.e. array(10, 38, 81)

// the group ID of a median group that controls akamai access
$akamai_group_id = 50;

/*

    amara config
	
	if you don't use amara, don't worry about it

*/

// amara uses these in the header for authentication
$amara_api_user = 'your_username'; // the API username
$amara_api_key = 'xxx'; // this is tied to the above user name
$amara_headers = array('X-api-username: '.$amara_api_user, 'X-apikey: '.$amara_api_key);

// new api URL as of february 2015
$amara_base_url = 'https://amara.org'; // the base URL for eventual amara API calls

// the group ID of a median group that controls amara access
$amara_group_id = 51;

/*

	Basic LTI / Canvas config

*/

// the shared secret for use with Basic LTI
$blti_secret = "supersecret";