<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH FILES
		introduced for median 5
        continued use in median 6
		cyle gage, emerson college, 2012-2014


	getVideoFileInfo($path, $debug) -- done
	getAudioFileInfo($path, $debug) -- done
	getHashPath($mid, $type) -- done
	isVideoFlashCompatible($video_info) -- done
	makeVideoThumbnails($mid, $random, $duration, $file_path_in) -- done
	makeImageThumbnail($mid, $file_path_in) -- done
	reTranscodeMedia($mid) -- done

*/

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/dbconn_mongo.php');
require_once(__DIR__.'/farm_functions.php');
require_once(__DIR__.'/media_functions.php');

// use ffprobe to get the video info, return a PHP array
function getVideoFileInfo($path = '', $debug = false) {
	// run ffprobe and get video file info for the path provided

	if (!isset($path) || trim($path) == '') {
		return false;
	}

    global $ffprobe_path;

	$path = trim($path);

	if (!file_exists($path)) {
		return -100;
	}

    // to use ffprobe's JSON output
    // ffprobe -v quiet -print_format json -show_format -show_streams /path/to/file.mp4

	$ffprobe_result = shell_exec($ffprobe_path.' -v error -print_format json -show_format -show_streams '.$path);

	if (trim($ffprobe_result) == '') {
		return -106; // ffprobe error
	}

	$file_info = json_decode(trim($ffprobe_result), true);

	if ($file_info == null) {
		// there was an error parsing the JSON...

		// return -104 if it's a quicktime reference file...
		// which may have been indicated by the ffprobe error output
		if (preg_match('/error opening alias/i', $ffprobe_result)) {
			return -104; // quicktime ref file, most likely
		}

		if (preg_match('/stream 0, error opening file/i', $ffprobe_result)) {
			return -104; // quicktime ref file, most likely
		}

		if ($debug) {
			return $ffprobe_result;
		} else {
			return -106; // ffprobe error
		}
	}

	if (!isset($file_info['streams']) || count($file_info['streams']) == 0) {
		// there are no streams in this media...
		return -106; // ffprobe error
	}

	if (!isset($file_info['format'])) {
		// there is no format info for this media...
		return -106; // ffprobe error
	}

	$media_info = array();
	$media_info['f'] = null; // container format
	$media_info['d'] = null; // duration
	$media_info['b'] = null; // total bitrate (kbps)
	$media_info['vb'] = null; // video bitrate (kbps)
	$media_info['ab'] = null; // audio bitrate (kbps)
	$media_info['vc'] = null; // video codec
	$media_info['ac'] = null; // audio codec
	$media_info['vw'] = null; // video width
	$media_info['vh'] = null; // video height
	if ($debug) {
		$media_info['debug'] = $file_info;
	}

	if (isset($file_info['format']['duration'])) {
		$media_info['d'] = round($file_info['format']['duration']);
	} else {
		return -101; // no duration!
	}

	if (isset($file_info['format']['bit_rate'])) {
		// comes in as bps, convert to kbps
		$media_info['b'] = round($file_info['format']['bit_rate']/1024);
	}

	if (isset($file_info['format']['tags']) && isset($file_info['format']['tags']['major_brand'])) {
		$media_info['f'] = strtolower(trim($file_info['format']['tags']['major_brand']));
	}

	foreach ($file_info['streams'] as $file_stream) {
		if ($file_stream['codec_type'] == 'video') {
			// comes in as bps, convert to kbps
			$media_info['vb'] = round($file_stream['bit_rate']/1024);
			$media_info['vc'] = strtolower(trim($file_stream['codec_name']));
			$media_info['vw'] = round($file_stream['width']);
			$media_info['vh'] = round($file_stream['height']);
			if (isset($file_stream['tags']) && isset($file_stream['tags']['rotate'])) {
				$media_info['rotation'] = (int) $file_stream['tags']['rotate'] * 1;
			}
			if (!isset($file_stream['pix_fmt'])) {
				// if it has no pixel format, that's usually because it's a quicktime reference file
				return -104;
			}
		} else if ($file_stream['codec_type'] == 'audio') {
			$media_info['ab'] = round($file_stream['bit_rate']/1024);
			$media_info['ac'] = strtolower(trim($file_stream['codec_name']));
		}
	}

	if ($media_info['vb'] == null || $media_info['vc'] == null) {
		return -102; // no video info!
	}

	if (preg_match('/prores/i', $media_info['vc']) || strtolower($media_info['vc']) == 'apple intermediate codec' || preg_match('/xdcam/i', $media_info['vc']) || strtolower($media_info['vc']) == 'hdv 720p24') {
		// apple-proprietary footage, no good
		//return -103;
	}

	if ($media_info['ac'] == null) {
		// it's okay if a video has no audio
		$media_info['ac'] = 'none';
	}

	return $media_info;

}

// use ffprobe to get the audio info, return a PHP array
function getAudioFileInfo($path = '', $debug = false) {
	// run ffprobe and get video file info for the path provided

	if (!isset($path) || trim($path) == '') {
		return false;
	}

    global $ffprobe_path;

	$path = trim($path);

	if (!file_exists($path)) {
		return -100;
	}

    // to use ffprobe's JSON output
    // ffprobe -v quiet -print_format json -show_format -show_streams /path/to/file.mp4

	$ffprobe_result = shell_exec($ffprobe_path.' -v error -print_format json -show_format -show_streams '.$path);

	if (trim($ffprobe_result) == '') {
		return -106; // ffprobe error
	}

	$file_info = json_decode(trim($ffprobe_result), true);

	if ($file_info == null) {
		// there was an error parsing the JSON...

		// return -104 if it's a quicktime reference file...
		// which may have been indicated by the ffprobe error output
		if (preg_match('/error opening alias/i', $ffprobe_result)) {
			return -104; // quicktime ref file, most likely
		}

		if (preg_match('/stream 0, error opening file/i', $ffprobe_result)) {
			return -104; // quicktime ref file, most likely
		}

		if ($debug) {
			return $ffprobe_result;
		} else {
			return -106; // ffprobe error
		}
	}

	if (!isset($file_info['streams']) || count($file_info['streams']) == 0) {
		// there are no streams in this media...
		return -106; // ffprobe error
	}

	if (!isset($file_info['format'])) {
		// there is no format info for this media...
		return -106; // ffprobe error
	}

	$media_info = array();
	$media_info['d'] = null; // duration
	$media_info['b'] = null; // total bitrate
	$media_info['sr'] = null; // sample rate
	$media_info['ab'] = null; // audio bitrate (redundant? probably.)
	$media_info['ac'] = null; // audio codec
	if ($debug) {
		$media_info['debug'] = $ffprobe_result;
	}

	if (isset($file_info['format']['duration'])) {
		$media_info['d'] = round($file_info['format']['duration']);
	} else {
		return -101; // no duration!
	}

	if (isset($file_info['format']['bit_rate'])) {
		// comes in as bps, convert to kbps
		$media_info['b'] = round($file_info['format']['bit_rate']/1024);
	}

	foreach ($file_info['streams'] as $file_stream) {
		if ($file_stream['codec_type'] == 'audio') {
			$media_info['ab'] = round($file_stream['bit_rate']/1024);
			$media_info['ac'] = strtolower(trim($file_stream['codec_name']));
			$media_info['sr'] = (int) $file_stream['sample_rate'] * 1;
		}
	}

	if ($media_info['ab'] == null || $media_info['ac'] == null) {
		return -102; // no audio info!
	}

	return $media_info;

}

// use the median ID to create a hash for the file's eventual path bucket
// to be used if splitting up the file storage using LVM or something
function getHashPath($mid = 0, $type = '') {
	if (!isset($mid) || !is_numeric($mid)) {
		return false;
	}
	$mid = (int) $mid * 1;
	if ($mid < 10) {
		$mid_string = '0'.$mid;
	} else {
		$mid_string = ''.$mid.'';
	}
	$salt = '';
	switch ($type) {
		case 'u':
		case 'i':
		// it's an upload
		$salt = 'u';
		break;
		case 'o':
		// it's for output
		$salt = 'o';
		break;
		case 'h':
		// it's for HTML5 output
		$salt = 'h';
		break;
		default:
		$salt = '0';
	}
	$path = substr(hash('sha512', $salt.substr($mid_string, -2)), 0, 10);
	return $path;
}

// check if the video is compatible with immediate streaming playback
// used to determine if the original upload can be used for streaming
function isVideoFlashCompatible($video_info = array()) {
	// the input needs to be the video info from getFFProbeInfo()
	// codecs that are flash-compatible
	$instant_formats = array('mp42');
	$instant_video_codecs = array('h264', 'h264 (baseline)', 'h264 (high)', 'h264 (main)', 'h264 (constrained baseline)');
	$instant_audio_codecs = array('mpeg4aac', 'faac', 'none', 'libfaad', 'aac');

	$instant_available = false;
	$flash_compatible = false;

	if ($video_info['f'] != null && in_array($video_info['f'], $instant_formats)) {
		if (in_array($video_info['vc'], $instant_video_codecs) || preg_match('/h264/', $video_info['vc']) > 0) {
			// it is among the instant-available compatible video codecs...
			if (in_array($video_info['ac'], $instant_audio_codecs) || preg_match('/aac/', $video_info['ac']) > 0) {
				// it is among the instant-available compatible audio codecs...
				// and at least it's flash-compatible
				$flash_compatible = true;
				if ($video_info['b'] < 1900) {
					// it's an acceptable bitrate...
					if ($video_info['vw'] > 0 && $video_info['vw'] <= 1280 && $video_info['vh'] > 0 && $video_info['vh'] <= 720) {
						// frame size is OK...
						// it's compatible for immediate viewing!
						$instant_available = true;
					}
				}
			}
		}
	}

	$info = array();
	$info['instant'] = $instant_available;
	$info['flash'] = $flash_compatible;
	return $info;

}

// use ffmpeg to take a screencap of a video
// then use the python imaging library to make a thumbnail of that
function makeVideoThumbnails($mid = 0, $random = false, $duration = 0, $file_path_in = '') {
	// make thumbnails!

	if (!isset($mid) || !is_numeric($mid)) {
		return false;
	}

	$mid = (int) $mid * 1;

	if (!isset($random)) {
		$random = false;
	}

	if (!is_numeric($duration) || $duration == 0) {
		return false;
	}

	global $m, $mdb, $thumbs_path, $thumbs_base_url, $ffmpeg_path, $thumbmaker_path;
	$media_collection = $mdb->media;
	$thumb_log = $m->median5_log->thumb;

	// destroy old ones if they exist...
	$current_thumbs = getThumbnails($mid);
	if ($current_thumbs != false) {
		if (isset($current_thumbs['big']) && $current_thumbs['big'] != '/thumbs/nothumb.jpg') {
			$current_thumb_path_big = str_replace($thumbs_base_url, $thumbs_path, $current_thumbs['big']);
			if (file_exists($current_thumb_path_big)) {
				unlink($current_thumb_path_big);
				$thumb_log->insert( array('mid' => $mid, 'ts' => time(), 't' => 'video', 'm' => 'Deleting old big thumb.') );
			}
		}
		if (isset($current_thumbs['small']) && $current_thumbs['small'] != '/thumbs/nothumb.jpg') {
			$current_thumb_path_small = str_replace($thumbs_base_url, $thumbs_path, $current_thumbs['small']);
			if (file_exists($current_thumb_path_small)) {
				unlink($current_thumb_path_small);
				$thumb_log->insert( array('mid' => $mid, 'ts' => time(), 't' => 'video', 'm' => 'Deleting old small thumb.') );
			}
		}
	}

	// add in some kind of unique hash...?
	$unique_hash = uniqid();

	$file_path_thumb = $thumbs_path.$mid.'_'.$unique_hash.'.jpg';
	$file_path_thumb_big = $thumbs_path.$mid.'_'.$unique_hash.'_big.jpg';

	$thumb_log->insert( array('mid' => $mid, 'ts' => time(), 't' => 'video', 'm' => 'New path will be '.$file_path_thumb) );

	$url_path_thumb = $thumbs_base_url.$mid.'_'.$unique_hash.'.jpg';
	$url_path_thumb_big = $thumbs_base_url.$mid.'_'.$unique_hash.'_big.jpg';

	if (!isset($file_path_in) || trim($file_path_in) == '') {
		$media_paths = getMediaPaths($mid);
		//$thumb_log->insert( array('mid' => $mid, 'ts' => time(), 't' => 'video', 'm' => 'Media paths info: '.print_r($media_paths, true)) );
		if (isset($media_paths['c']) && count($media_paths['c']) > 0) {
			$file_path_in = $media_paths['c'][0]['p'];
		} else {
			$file_path_in = $media_paths['in'];
		}
	}

	$thumb_log->insert( array('mid' => $mid, 'ts' => time(), 't' => 'video', 'm' => 'File path in is '.$file_path_in) );

	$video_info = getVideoFileInfo($file_path_in);

	$thumb_log->insert( array('mid' => $mid, 'ts' => time(), 't' => 'video', 'm' => 'Input duration is '.$duration.', ffprobe says '.$video_info['d']) );

	$duration = $video_info['d'] * 1;

	if ($random) {
		$thumb_time = ceil(rand(1, $duration));
	} else {
		$thumb_time = ceil($duration * 0.33);
	}

	$thumb_log->insert( array('mid' => $mid, 'ts' => time(), 't' => 'video', 'm' => 'Screenshot time will be '.$thumb_time) );

	// first get the big thumbnail from FFMPEG
	$thumb_command = "$ffmpeg_path -ss $thumb_time -i $file_path_in -r 1 -vframes 1 -an -vcodec mjpeg -f mjpeg -y -deinterlace $file_path_thumb_big";

	$thumb_log->insert( array('mid' => $mid, 'ts' => time(), 't' => 'video', 'm' => 'Thumbnail ffmpeg call is: '.$thumb_command) );

	$thumb_result = shell_exec($thumb_command . ' 2>&1 1> /dev/null');

	$thumb_log->insert( array('mid' => $mid, 'ts' => time(), 't' => 'video', 'm' => 'Thumbnail ffmpeg result is: '.$thumb_result) );

	if (preg_match('/no such file/', $thumb_result) == 0) {
		// then get the smaller thumb from thumbmaker.py
		$output = `python $thumbmaker_path $file_path_thumb_big $file_path_thumb 100 100`;

		$thumb_log->insert( array('mid' => $mid, 'ts' => time(), 't' => 'video', 'm' => 'Python thumbmaker result is: '.$output) );

		if (trim($output) != 'done') {
			return false;
		}
	} else {
		return false;
	}

	// update document with new thumb info!
	$new_thumb_info = array('th' => array('s' => $url_path_thumb, 'b' => $url_path_thumb_big));

	$thumb_log->insert( array('mid' => $mid, 'ts' => time(), 't' => 'video', 'm' => 'Success! Updating mongo record.') );

	try {
		$update = $media_collection->update(array('mid' => $mid), array('$set' => $new_thumb_info), array('w'=>1));
	} catch(MongoCursorException $e) {
		return false;
	}

	return true;

}

// use the python imaging library to make a thumbnail of an image
function makeImageThumbnail($mid = 0, $file_path_in = '') {
	// make thumbnail for an image -- super easy, right?

	if (!isset($mid) || !is_numeric($mid)) {
		return false;
	}

	$mid = (int) $mid * 1;

	global $m, $mdb, $thumbs_path, $thumbs_base_url, $thumbmaker_path;
	$media_collection = $mdb->media;
	$thumb_log = $m->median5_log->thumb;

	// destroy old ones if they exist...
	$current_thumbs = getThumbnails($mid);
	if ($current_thumbs != false) {
		if (isset($current_thumbs['big']) && $current_thumbs['big'] != '/thumbs/nothumb.jpg') {
			$current_thumb_path_big = str_replace($thumbs_base_url, $thumbs_path, $current_thumbs['big']);
			if (file_exists($current_thumb_path_big)) {
				unlink($current_thumb_path_big);
			}
		}
		if (isset($current_thumbs['small']) && $current_thumbs['small'] != '/thumbs/nothumb.jpg') {
			$current_thumb_path_small = str_replace($thumbs_base_url, $thumbs_path, $current_thumbs['small']);
			if (file_exists($current_thumb_path_small)) {
				unlink($current_thumb_path_small);
			}
		}
	}

	// add in some kind of unique hash...?
	$unique_hash = uniqid();

	$file_path_thumb = $thumbs_path.$mid.'_'.$unique_hash.'.jpg';
	$file_path_thumb_big = $thumbs_path.$mid.'_'.$unique_hash.'_big.jpg';

	$url_path_thumb = $thumbs_base_url.$mid.'_'.$unique_hash.'.jpg';
	$url_path_thumb_big = $thumbs_base_url.$mid.'_'.$unique_hash.'_big.jpg';

	if (!isset($file_path_in) || trim($file_path_in) == '') {
		$media_paths = getMediaPaths($mid);
		$file_path_in = $media_paths['in'];
	}

	$output = `python $thumbmaker_path $file_path_in $file_path_thumb 100 100`;
	if (strtolower(trim($output)) != 'done') {
		return false;
	}

	// for the big thumbnail, just copy the actual image
	copy($file_path_in, $file_path_thumb_big);

	// update document with new thumb info!
	$new_thumb_info = array('th' => array('s' => $url_path_thumb, 'b' => $url_path_thumb_big));

	try {
		$update = $media_collection->update(array('mid' => $mid), array('$set' => $new_thumb_info), array('w'=>1));
	} catch(MongoCursorException $e) {
		return false;
	}

	return true;

}

// remove old transcoded versions of a video entry
// and then create new farming jobs to retranscode it
// helpful for old videos that don't have all their versions for some reason
function reTranscodeMedia($mid = 0) {

	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}

	$mid = (int) $mid * 1;

	global $m, $mdb, $tiers;

	/*

		- get media info
		- is it not video? forget it
		- get original "in" file
		- delete current "out" file(s)
		- go through and see what versions you can make
		- add jobs and media info

	*/

	$media_info = getMediaInfo($mid);

	if ($media_info == false) { // media entry does not exist!
		return false;
	}

	if ($media_info['mt'] != 'video') {
		return false;
	}

	if (!isset($media_info['pa']) || !isset($media_info['pa']['in'])) {
		return false;
	}

	if (!file_exists($media_info['pa']['in'])) {
		return false;
	}

	$file_path_in = $media_info['pa']['in'];

	$video_info = getVideoFileInfo($file_path_in);

	if (!is_array($video_info)) {
		return false;
	}

	if (isset($media_info['pa']['c']) && count($media_info['pa']['c']) > 0) {
		foreach ($media_info['pa']['c'] as $media_path) {
			if (file_exists($media_path['p'])) {
				$delete_old_out = unlink($media_path['p']);
			}
		}
	}

	$updated_entry = array(); // this is what we'll be updating the media document with
	$uploaded_extension = strtolower(strrchr($file_path_in, '.'));
	$out_hash = getHashPath($mid, 'o');
	$folder_path_out = '/median/files/out/'.$out_hash.'/';

	if (!file_exists($folder_path_out)) {
		mkdir($folder_path_out, 0775, true);
		chmod($folder_path_out, 0775);
	}

	// there will be an array of media paths, not just one
	$media_paths = array();

	// check if we can use the original source media
	$compatibility_info = isVideoFlashCompatible($video_info);
	if ($compatibility_info['flash']) { // original is flash-compatible, cool
		// make the file out path for it
		$file_path_original_out = $folder_path_out.$mid.'_'.$video_info['b'].'kbps'.$uploaded_extension;
		$fms_path_original_out = 'mp4:welp/'.$out_hash.'/'.$mid.'_'.$video_info['b'].'kbps'.$uploaded_extension;
		// link it!
		link($file_path_in, $file_path_original_out);
		// get filesize
		error_reporting(0);
		$filesize_original = filesize($file_path_original_out) * 1;
		error_reporting(1);
		// add a media path entry for it
		$media_paths[] = array('b' => $video_info['b'], 'p' => $file_path_original_out, 'f' => $fms_path_original_out, 'w' => $video_info['vw'], 'h' => $video_info['vh'], 'e' => true, 'fs' => $filesize_original);
	}

	// check how many versions to make
	// if audio bitrate is higher than the video, keep the audio bitrate at 256kbps max

	$video_make_ultra = true;
	$video_make_high = true;
	$video_make_medium = true;
	$video_make_small = true;
	$convert_original = false;

	if ($video_info['vw'] < 1280) {
		$video_make_ultra = false;
		$video_make_high = false;
	} else {
		if ($video_info['b'] < 1800) {
			$video_make_ultra = false;
		}
	}

	if (!$compatibility_info['flash'] && ($video_info['vw'] > 720 && $video_info['vw'] < 1280) && ($video_info['vh'] > 480 && $video_info['vh'] < 720) && ($video_info['b'] > 700 && $video_info['b'] < 1900)) {
		$convert_original = true;
	}

	$media_original_ratio = $video_info['vw']/$video_info['vh'];

	if ($video_make_ultra) {
		//writeToLog('Making an ULTRA version.', $swap_log, $uid, $mid);
		$tier_ultra_bitrate = $tiers['ultra']['vb'] + $tiers['ultra']['ab'];
		$file_path_ultra_out = $folder_path_out.$mid.'_'.$tier_ultra_bitrate.'kbps.mp4';
		$media_ultra_height = round($tiers['ultra']['vw']/$media_original_ratio);
		$fms_path_ultra_out = 'mp4:welp/'.$out_hash.'/'.$mid.'_'.$tier_ultra_bitrate.'kbps.mp4';
		$farm_result = addFarmingJob($mid, array('in' => $file_path_in, 'out' => $file_path_ultra_out), 'ultra');
		if (!$farm_result) {
			//writeToLog('Could not add ULTRA farming job. Weird.', $swap_log, $uid, $mid);
		} else {
			$media_paths[] = array('b' => $tier_ultra_bitrate, 'p' => $file_path_ultra_out, 'f' => $fms_path_ultra_out, 'w' => $tiers['ultra']['vw'], 'h' => $media_ultra_height, 'e' => false);
		}
	}

	if ($video_make_high) {
		//writeToLog('Making an HIGH version.', $swap_log, $uid, $mid);
		$tier_high_bitrate = $tiers['high']['vb'] + $tiers['high']['ab'];
		$file_path_high_out = $folder_path_out.$mid.'_'.$tier_high_bitrate.'kbps.mp4';
		$media_high_height = round($tiers['high']['vw']/$media_original_ratio);
		$fms_path_high_out = 'mp4:welp/'.$out_hash.'/'.$mid.'_'.$tier_high_bitrate.'kbps.mp4';
		$farm_result = addFarmingJob($mid, array('in' => $file_path_in, 'out' => $file_path_high_out), 'high');
		if (!$farm_result) {
			//writeToLog('Could not add HIGH farming job. Weird.', $swap_log, $uid, $mid);
		} else {
			$media_paths[] = array('b' => $tier_high_bitrate, 'p' => $file_path_high_out, 'f' => $fms_path_high_out, 'w' => $tiers['high']['vw'], 'h' => $media_high_height, 'e' => false);
		}
	}

	if ($convert_original) {
		//writeToLog('Converting the original to a Flash-compatible one of the same parameters.', $swap_log, $uid, $mid);
		$file_path_conv_out = $folder_path_out.$mid.'_'.$video_info['b'].'kbps.mp4';
		$fms_path_conv_out = 'mp4:welp/'.$out_hash.'/'.$mid.'_'.$video_info['b'].'kbps.mp4';
		$custom_tier = array('vb' => 800, 'vw' => $video_info['vw'], 'vh' => $video_info['vh'], 'ab' => 128);
		$farm_result = addFarmingJob($mid, array('in' => $file_path_in, 'out' => $file_path_conv_out), $custom_tier);
		if (!$farm_result) {
			//writeToLog('Could not add CUSTOM farming job. Weird.', $swap_log, $uid, $mid);
		} else {
			$media_paths[] = array('b' => $video_info['b'], 'p' => $file_path_conv_out, 'f' => $fms_path_conv_out, 'w' => $video_info['vw'], 'h' => $video_info['vh'], 'e' => false);
		}
	}

	if ($video_make_medium) {
		//writeToLog('Making an MEDIUM version.', $swap_log, $uid, $mid);
		$tier_medium_bitrate = $tiers['medium']['vb'] + $tiers['medium']['ab'];
		$file_path_medium_out = $folder_path_out.$mid.'_'.$tier_medium_bitrate.'kbps.mp4';
		$media_medium_height = round($tiers['medium']['vw']/$media_original_ratio);
		$fms_path_medium_out = 'mp4:welp/'.$out_hash.'/'.$mid.'_'.$tier_medium_bitrate.'kbps.mp4';
		$farm_result = addFarmingJob($mid, array('in' => $file_path_in, 'out' => $file_path_medium_out), 'medium');
		if (!$farm_result) {
			//writeToLog('Could not add MEDIUM farming job. Weird.', $swap_log, $uid, $mid);
		} else {
			$media_paths[] = array('b' => $tier_medium_bitrate, 'p' => $file_path_medium_out, 'f' => $fms_path_medium_out, 'w' => $tiers['medium']['vw'], 'h' => $media_medium_height, 'e' => false);
		}
	}

	if ($video_make_small) {
		//writeToLog('Making an SMALL version.', $swap_log, $uid, $mid);
		$tier_small_bitrate = $tiers['small']['vb'] + $tiers['small']['ab'];
		$file_path_small_out = $folder_path_out.$mid.'_'.$tier_small_bitrate.'kbps.mp4';
		$media_small_height = round($tiers['small']['vw']/$media_original_ratio);
		$fms_path_small_out = 'mp4:welp/'.$out_hash.'/'.$mid.'_'.$tier_small_bitrate.'kbps.mp4';
		$farm_result = addFarmingJob($mid, array('in' => $file_path_in, 'out' => $file_path_small_out), 'small');
		if (!$farm_result) {
			//writeToLog('Could not add SMALL farming job. Weird.', $swap_log, $uid, $mid);
		} else {
			$media_paths[] = array('b' => $tier_small_bitrate, 'p' => $file_path_small_out, 'f' => $fms_path_small_out, 'w' => $tiers['small']['vw'], 'h' => $media_small_height, 'e' => false);
		}
	}

	// add the paths to the updated entry
	$updated_entry['pa'] = array();
	$updated_entry['pa']['in'] = $media_info['pa']['in'];
	$updated_entry['pa']['c'] = $media_paths;

	// update timestamp, lol
	$updated_entry['tsu'] = time();

	// update media document with media paths!
	try {
		$update = $mdb->media->update(array('mid' => $mid), array('$set' => $updated_entry), array('w' => 1));
	} catch(MongoCursorException $e) {
		return false;
	}

	return true;

}
