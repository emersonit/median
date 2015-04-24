<?php

/*

    configuration variables used by the Median File API
    specifically, the PHP parts

*/

// the file path for where incoming uploaded files are saved
$files_in_basepath = '/median/files/in/';

// the file path for where uploaded files will end up
// after being analyzed/transcoded/etc
$files_out_basepath = '/median/files/out/';

// the file path for where HTML5 symlinks will be kept
$html5_base_path = '/median/files/html5/';

// the file path for where thumbnails are saved
$thumbs_path = '/median/thumbs/';

// and the URL base path for where thumbnails are saved
$thumbs_base_url = '/files/thumb/';

// the $thumbs_base_url should be an alias for $thumb_path
// within the node.js file API

// where is the ffmpeg binary?
$ffmpeg_path = '/usr/local/bin/ffmpeg';

// where is the ffprobe binary?
$ffprobe_path = '/usr/local/bin/ffprobe';

// where is the thumbmaker.py script? should be one level up from here
// or provide an absolute path if you want it somewhere else
$thumbmaker_path = __DIR__.'/../thumbmaker.py';

// farming/video version presets...
// video bitrate, width, height, audio bitrate
$tiers = array(
	'ultra' => array('vb' => 1700, 'vw' => 1280, 'vh' => 720, 'ab' => 128),
	'high' => array('vb' => 1200, 'vw' => 1280, 'vh' => 720, 'ab' => 128),
	'medium' => array('vb' => 600, 'vw' => 720, 'vh' => 480, 'ab' => 96),
	'small' => array('vb' => 300, 'vw' => 400, 'vh' => 260, 'ab' => 64)
);