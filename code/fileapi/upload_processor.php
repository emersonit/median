#!/usr/bin/env php
<?php

if (php_sapi_name() != 'cli') {
  fwrite(STDERR, "error: not using CLI!\n");
  exit(1);
}

/*

	THE UPLOAD PROCESSOR
		cyle gage, emerson college, 2012-2014

*/

require_once(__DIR__.'/includes/config.php');
require_once(__DIR__.'/includes/dbconn_mongo.php');
require_once(__DIR__.'/includes/error_functions.php');
require_once(__DIR__.'/includes/log_functions.php');

// database to use
$media_collection = $mdb->media;

// valid acceptable file extensions
$image_extensions = array('.png', '.gif', '.jpg', '.jpeg');
$video_extensions = array('.flv', '.avi', '.wmv', '.divx', '.dv', '.mov', '.mp4', '.mpeg', '.mpg');
$audio_extensions = array('.mp3');
$doc_extensions = array('.doc', '.docx', '.txt', '.rtf', '.pdf', '.xls', '.xlsx', '.ppt', '.pptx');

// a helper function to create the pretty random hex string
function random_hash() {
	return bin2hex(openssl_random_pseudo_bytes(16));
}

/*

    1. take incoming entry/file info as JSON via stdin
    2. parse through it, checking for videos, audio, whatever
    3. according to media type, process further
    4. make new MID numbers for them...
    5. return success/errors as JSON object to stdout

*/

// problem: cannot get cookie data from this, obviously, since the request is coming from nodejs and not the user.
// solution: pass the UID via jQuery to nodejs which then sends it along with this. insecure, maybe, but better than nothing.

$text_input = '';
while (!feof(STDIN)) {
  $text_input .= fgets(STDIN, 4096);
}

$batch_info = json_decode($text_input, true);

if ($batch_info == null) {
  bailout('Invalid JSON data given.', null, null, $text_input);
}

/*

  incoming $batch_info array should look like...

  $batch_info['user_id'] => median user ID of who is uploading
  $batch_info['files'] => array of files
    $file in $batch_info['files'] => 'path', 'size', and 'title'

*/

if (!isset($batch_info['files']) || count($batch_info['files']) == 0) {
  bailout('It looks like no files were actually uploaded.', null, null, $batch_info);
}

if (!isset($batch_info['user_id']) || !is_numeric($batch_info['user_id'])) {
  bailout('No valid user ID provided with the uploads.', null, null, $batch_info);
}

// who's doing the uploading
$uid = (int) $batch_info['user_id'] * 1;

// ok now we can include these
require_once(__DIR__.'/includes/file_functions.php');
require_once(__DIR__.'/includes/farm_functions.php');
require_once(__DIR__.'/includes/media_functions.php');

// is this a file swap?
// we do some key different things if it is
$is_file_swap = false;
$mid_to_swap = 0;
$media_info = array();
if (isset($batch_info['is_swap']) && $batch_info['is_swap'] == true) {
    $is_file_swap = true;
    if (!isset($batch_info['mid']) || !is_numeric($batch_info['mid']) || $batch_info['mid'] * 1 == 0) {
        bailout('No valid media ID given to swap files for.', $uid, null, $batch_info);
    }
    $mid_to_swap = (int) $batch_info['mid'] * 1;
    // let's make sure the MID they're trying to replace exists
    $media_info = getMediaInfo($mid_to_swap);

    if ($media_info == false) {
    	bailout('That media ID does not exist.', $uid, $mid_to_swap);
    }

    writeToLog('New upload processor session started for swap!', false, $uid, $mid_to_swap, $batch_info);
} else {
    writeToLog('New upload processor session started, '.count($batch_info['files']).' files.', false, $uid, null, $batch_info);
}

// this'll hold what we send back to the user
$return_info = array();

// from here on out we do not abort the whole process if something fails, we move on
// and we send any failure conditions back to the client once done going through each file

// go through each file and figure out what to do with it
foreach ($batch_info['files'] as $batched) {
  // each $batched should have keys 'path', 'size', and 'title'

  // if no title, make it "Untitled"
  if (isset($batched['title']) && trim($batched['title']) != '') {
    $batch_title = trim($batched['title']);
  } else {
    $batch_title = 'Untitled';
  }

  writeToLog('Entry title: '.$batch_title, false, $uid);

  // make sure path is set for each
  if (!isset($batched['path']) || trim($batched['path']) == '') {
    writeToLog('No path was set!', true, $uid);
    $result_status = 200;
    $result_message = 'The file was not uploaded successfully.';
    $return_info[] = array('title' => $batch_title, 'mid' => 0, 'status' => $result_status, 'status_message' => $result_message);
    continue; // go on to the next one
  }

  $batch_path = trim($batched['path']);

  // clear some variables
  $result_status = 0; // the final status of the upload
  $result_message = 'Nothing yet.'; // the message to go along with the status

  // get the file extension
  $uploaded_extension = strtolower(strrchr($batch_path, '.'));

  // check and set the metatype
  if (in_array($uploaded_extension, $image_extensions)) {
    $media_metatype = 'image';
  } else if (in_array($uploaded_extension, $audio_extensions)) {
    $media_metatype = 'audio';
  } else if (in_array($uploaded_extension, $video_extensions)) {
    $media_metatype = 'video';
  } else if (in_array($uploaded_extension, $doc_extensions)) {
    $media_metatype = 'doc';
  } else {
    $media_metatype = 'unknown';
    writeToLog('Unknown media type, weird: '.$batch_path, true, $uid);
    $result_status = 200;
    $result_message = 'The file uploaded is an unknown or unsupported file type.';
    $return_info[] = array('title' => $batch_title, 'mid' => 0, 'status' => $result_status, 'status_message' => $result_message);
    continue;
  }
  // ok so at least it's a valid type

  writeToLog('Entry metatype: '.$media_metatype, false, $uid);

    if ($is_file_swap && $media_info['mt'] != $media_metatype) {
        writeToLog('ERROR: The file type is not the same as the original file type!', true, $uid, $mid_to_swap);
    	$result_status = 200;
    	$result_message = 'The file type is not the same as the original file type.';
    	$return_info[] = array('title' => $batch_title, 'mid' => $mid_to_swap, 'status' => $result_status, 'status_message' => $result_message);
    	echo json_encode($return_info);
    	die();
    }

  // let's run some checks first before we actually accept the file
  if ($media_metatype == 'video') {
    $video_info = getVideoFileInfo($batch_path);
    if (!is_array($video_info)) {
      // there was an error of some kind!
      $video_error = $video_info;
      switch ($video_error) {
        case -100: // no such file
        $result_status = 200;
        $result_message = 'There was an error checking the file metadata, please try again.';
        break;
        case -101: // no duration or bitrate info
        $result_status = 200;
        $result_message = 'I could not retrieve duration or bitrate information, so I cannot process the file.';
        break;
        case -102: // no video info
        $result_status = 200;
        $result_message = 'The file seems to be video, but I could not retrieve any video information.';
        break;
        case -103: // apple proprietary footage
        $result_status = 200;
        $result_message = 'The file was encoded with Apple Proprietary codecs (like XDCAM or ProRes), so it cannot be used by Median. Please try encoding it to a friendlier format, like H264 or DivX.';
        break;
        case -104: // quicktime reference file, or otherwise no streams
        $result_status = 200;
        $result_message = 'I could not retrieve any video information, possibly because the file is a Quicktime Reference file. Please check and try again.';
        break;
      }
      writeToLog($result_message, true, $uid);
      $return_info[] = array('title' => $batch_title, 'mid' => 0, 'status' => $result_status, 'status_message' => $result_message);
      continue;
    }
  } else if ($media_metatype == 'audio') {
    $audio_info = getAudioFileInfo($batch_path);
    if (!is_array($audio_info)) {
      // there was an error of some kind!
      $audio_error = $audio_info;
      switch ($audio_error) {
        case -100: // no such file
        $result_status = 200;
        $result_message = 'There was an error checking the file metadata, please try again.';
        break;
        case -101: // no duration or bitrate info
        $result_status = 200;
        $result_message = 'I could not retrieve duration or bitrate information, so I cannot process the file.';
        break;
      }
      writeToLog($result_message, true, $uid);
      $return_info[] = array('title' => $batch_title, 'mid' => 0, 'status' => $result_status, 'status_message' => $result_message);
      continue;
    }
  } else if ($media_metatype == 'image') {
    // uhhh -- no preflight checks for images yet
  } else if ($media_metatype == 'doc') {
    // uhhh -- no preflight checks for documents yet
  }

  /*

      if it survived that, let's actually do things with it!

  */

  $updated_entry = array(); // this is what we'll be updating the median entry document with
  $updated_entry['mt'] = $media_metatype; // set the metatype

  // these are the default success messages which'll be used at the end of this loop
  $result_status = 100; // totally cool, we can use the file!
  $result_message = 'File uploaded successfully.';

  if ($is_file_swap) {
    $mid = $mid_to_swap; // go with this
    // delete the originals to make way for the new
    if ($media_info['mt'] == 'video') {
		// go though and delete each
		foreach ($media_info['pa']['c'] as $media_path) {
			if (file_exists($media_path['p'])) {
				$delete_old_out = unlink($media_path['p']);
			}
		}
	} else if ($media_info['mt'] == 'audio') {
		if (file_exists($media_info['pa']['c']['p'])) {
			$delete_old_out = unlink($media_info['pa']['c']['p']);
		}
	} else {
		if (file_exists($media_info['pa']['c'])) {
			$delete_old_out = unlink($media_info['pa']['c']);
		}
	}
	if (file_exists($media_info['pa']['in'])) {
		$delete_old_in = unlink($media_info['pa']['in']);
	}
  } else {
      // generate a new media ID for this
      $mid = generateNewMedianEntry($uid, $batch_title);
      writeToLog('Generated Media ID: '.$mid, false, $uid);
  }


  // determine hashes and file in location
  $in_hash = getHashPath($mid, 'u');
  $out_hash = getHashPath($mid, 'o');
  $folder_path_in = $files_in_basepath.$in_hash.'/';
  $folder_path_out = $files_out_basepath.$out_hash.'/';
  $file_path_in = $folder_path_in.$mid.$uploaded_extension;

  writeToLog('File path in: '.$file_path_in, false, $uid, $mid);

  // if the folders don't exist, make it!
  // this script must have write permission to base dirs and all subdirs
  if (!file_exists($folder_path_in)) {
    mkdir($folder_path_in, 0775, true);
    chmod($folder_path_in, 0775);
  }

  if (!file_exists($folder_path_out)) {
    mkdir($folder_path_out, 0775, true);
    chmod($folder_path_out, 0775);
  }

  // ok, now move the file
  $move_file = rename($batch_path, $file_path_in);

  if (!$move_file) {
    // uh oh, could not move the file for some reason!
    $result_status = 200;
    $result_message = 'There was an error initially moving the file, uh oh.';
    writeToLog($result_message, true, $uid, $mid);
    $return_info[] = array('title' => $batch_title, 'mid' => 0, 'status' => $result_status, 'status_message' => $result_message);
    continue;
  }

  // okay start setting up the info for where these files go and whatnot
  if ($media_metatype == 'video') {
    // we already have video info from before, $video_info

    writeToLog('Video info from ffprobe: '.oneLinePrintArray($video_info), false, $uid, $mid);

    // there will be an array of media paths, not just one
    $media_paths = array();

    // check if we can use the original source media
    $compatibility_info = isVideoFlashCompatible($video_info);
    if ($compatibility_info['flash']) { // original is flash-compatible, cool
      // make the file out path for it
      $file_path_original_out = $folder_path_out.$mid.'_'.$video_info['b'].'kbps'.$uploaded_extension;
      $fms_path_original_out = 'mp4:welp/'.$out_hash.'/'.$mid.'_'.$video_info['b'].'kbps'.$uploaded_extension;
      // link it!
      $link_original = link($file_path_in, $file_path_original_out);
      if ($link_original) {
        $filesize_original = @filesize($file_path_original_out) * 1;
        // add a media path entry for it
        $media_paths[] = array('b' => $video_info['b'], 'p' => $file_path_original_out, 'f' => $fms_path_original_out, 'w' => $video_info['vw'], 'h' => $video_info['vh'], 'e' => true, 'fs' => $filesize_original);
      }
    }
    if ($compatibility_info['instant']) {
      $result_status = 101; // 101 means it's instantly available and should be enabled when the upload wizard is submitted
      $result_message = 'File uploaded successfully. It can be instantly available for viewing after submitting the Uploader.';
      writeToLog('Cool, the video can be instantly available.', false, $uid, $mid);
    } else {
      $result_status = 102; // 102 means it's NOT instantly available and the video will need to be transcoded
      $result_message = 'File uploaded successfully. It needs to be transcoded at least once before being viewable after submitting the Uploader. (<a href="/help/videofaq/" target="_blank">What does that mean?</a>)';
      writeToLog('The video needs to be transcoded before viewing.', false, $uid, $mid);
    }

    // now we figure out how many "versions" of the video to make
    // this is to enable dynamic streaming for any client connection speed
    // if audio bitrate is higher than the video,
    // keep the audio bitrate at 256kbps max

    // right now these are the tiers
    // the tier specs themselves are in farm_functions.php
    $video_make_ultra = true; // big HD
    $video_make_high = true; // regular HD
    $video_make_medium = true; // SD
    $video_make_small = true; // mobile
    $convert_original = false; // wildcard

    // if the video is already less than 720p or 1080p, can't make HD versions
    if ($video_info['vw'] < 1280) {
      $video_make_ultra = false;
      $video_make_high = false;
    } else {
      if ($video_info['b'] < 1800) {
        $video_make_ultra = false;
      }
    }

    // if the original file is within a certain threshold, convert it, too
    // could be useful
    if (!$compatibility_info['flash'] && ($video_info['vw'] > 720 && $video_info['vw'] < 1280) && ($video_info['vh'] > 480 && $video_info['vh'] < 720) && ($video_info['b'] > 700 && $video_info['b'] < 1900)) {
      $convert_original = true;
    }

    // we'll be resizing the smaller versions of the video, use original ratio
    $media_original_ratio = $video_info['vw']/$video_info['vh'];

    // take care of videos that are rotated
    if (isset($video_info['rotation'])) {

      /*

      right about here is where i need to figure out
      how to preserve the "rotation" of a video
      maybe an additional job entry field?

      */

    }

    // oh snap, make an ULTRA version job for the transcode farm
    if ($video_make_ultra) {
      writeToLog('Making an ULTRA version.', false, $uid, $mid);
      $tier_ultra_bitrate = $tiers['ultra']['vb'] + $tiers['ultra']['ab'];
      $file_path_ultra_out = $folder_path_out.$mid.'_'.$tier_ultra_bitrate.'kbps.mp4';
      $media_ultra_height = round($tiers['ultra']['vw']/$media_original_ratio);
      $fms_path_ultra_out = 'mp4:welp/'.$out_hash.'/'.$mid.'_'.$tier_ultra_bitrate.'kbps.mp4';
      $farm_result = addFarmingJob($mid, array('in' => $file_path_in, 'out' => $file_path_ultra_out), 'ultra');
      if (!$farm_result) {
        writeToLog('Could not add ULTRA farming job. Weird.', true, $uid, $mid);
      } else {
        $media_paths[] = array('b' => $tier_ultra_bitrate, 'p' => $file_path_ultra_out, 'f' => $fms_path_ultra_out, 'w' => $tiers['ultra']['vw'], 'h' => $media_ultra_height, 'e' => false);
      }
    }

    // oh snap, make a HIGH version job for the transcode farm
    if ($video_make_high) {
      writeToLog('Making an HIGH version.', false, $uid, $mid);
      $tier_high_bitrate = $tiers['high']['vb'] + $tiers['high']['ab'];
      $file_path_high_out = $folder_path_out.$mid.'_'.$tier_high_bitrate.'kbps.mp4';
      $media_high_height = round($tiers['high']['vw']/$media_original_ratio);
      $fms_path_high_out = 'mp4:welp/'.$out_hash.'/'.$mid.'_'.$tier_high_bitrate.'kbps.mp4';
      $farm_result = addFarmingJob($mid, array('in' => $file_path_in, 'out' => $file_path_high_out), 'high');
      if (!$farm_result) {
        writeToLog('Could not add HIGH farming job. Weird.', true, $uid, $mid);
      } else {
        $media_paths[] = array('b' => $tier_high_bitrate, 'p' => $file_path_high_out, 'f' => $fms_path_high_out, 'w' => $tiers['high']['vw'], 'h' => $media_high_height, 'e' => false);
      }
    }

    // oh snap, make a convert-original job for the transcode farm
    if ($convert_original) {
      writeToLog('Converting the original to a Flash-compatible one of the same parameters.', false, $uid, $mid);
      $file_path_conv_out = $folder_path_out.$mid.'_'.$video_info['b'].'kbps.mp4';
      $fms_path_conv_out = 'mp4:welp/'.$out_hash.'/'.$mid.'_'.$video_info['b'].'kbps.mp4';
      $custom_tier = array('vb' => 800, 'vw' => $video_info['vw'], 'vh' => $video_info['vh'], 'ab' => 128);
      $farm_result = addFarmingJob($mid, array('in' => $file_path_in, 'out' => $file_path_conv_out), $custom_tier);
      if (!$farm_result) {
        writeToLog('Could not add CUSTOM farming job. Weird.', true, $uid, $mid);
      } else {
        $media_paths[] = array('b' => $video_info['b'], 'p' => $file_path_conv_out, 'f' => $fms_path_conv_out, 'w' => $video_info['vw'], 'h' => $video_info['vh'], 'e' => false);
      }
    }

    // oh snap, make a MEDIUM version job for the transcode farm
    if ($video_make_medium) {
      writeToLog('Making an MEDIUM version.', false, $uid, $mid);
      $tier_medium_bitrate = $tiers['medium']['vb'] + $tiers['medium']['ab'];
      $file_path_medium_out = $folder_path_out.$mid.'_'.$tier_medium_bitrate.'kbps.mp4';
      $media_medium_height = round($tiers['medium']['vw']/$media_original_ratio);
      $fms_path_medium_out = 'mp4:welp/'.$out_hash.'/'.$mid.'_'.$tier_medium_bitrate.'kbps.mp4';
      $farm_result = addFarmingJob($mid, array('in' => $file_path_in, 'out' => $file_path_medium_out), 'medium');
      if (!$farm_result) {
        writeToLog('Could not add MEDIUM farming job. Weird.', true, $uid, $mid);
      } else {
        $media_paths[] = array('b' => $tier_medium_bitrate, 'p' => $file_path_medium_out, 'f' => $fms_path_medium_out, 'w' => $tiers['medium']['vw'], 'h' => $media_medium_height, 'e' => false);
      }
    }

    // oh snap, make a SMALL version job for the transcode farm
    if ($video_make_small) {
      writeToLog('Making an SMALL version.', false, $uid, $mid);
      $tier_small_bitrate = $tiers['small']['vb'] + $tiers['small']['ab'];
      $file_path_small_out = $folder_path_out.$mid.'_'.$tier_small_bitrate.'kbps.mp4';
      $media_small_height = round($tiers['small']['vw']/$media_original_ratio);
      $fms_path_small_out = 'mp4:welp/'.$out_hash.'/'.$mid.'_'.$tier_small_bitrate.'kbps.mp4';
      $farm_result = addFarmingJob($mid, array('in' => $file_path_in, 'out' => $file_path_small_out), 'small');
      if (!$farm_result) {
        writeToLog('Could not add SMALL farming job. Weird.', true, $uid, $mid);
      } else {
        $media_paths[] = array('b' => $tier_small_bitrate, 'p' => $file_path_small_out, 'f' => $fms_path_small_out, 'w' => $tiers['small']['vw'], 'h' => $media_small_height, 'e' => false);
      }
    }

    // add duration to the media entry
    $updated_entry['du'] = $video_info['d'];

    // add the paths to the updated entry
    $updated_entry['pa'] = array();
    $updated_entry['pa']['in'] = $file_path_in;
    $updated_entry['pa']['c'] = $media_paths;

    // make thumbnails
    $make_thumb_result = makeVideoThumbnails($mid, false, $video_info['d'], $file_path_in);
    if ($make_thumb_result == false) {

      // wat do?

      writeToLog('Could not make thumbnails. Weird.', true, $uid, $mid);
    } else {
      writeToLog('Made video thumbnails!', false, $uid, $mid);
    }

  } else if ($media_metatype == 'audio') {

    // bitrate and duration were got from getAudioFileInfo() above
    $file_path_out = $folder_path_out.$mid.'.mp3';
    $fms_path_out = 'mp3:welp/'.$out_hash.'/'.$mid.'.mp3';
    $link_files = link($file_path_in, $file_path_out);
    if ($link_files == false) {
      // uh oh, could not link the file for some reason!
      $result_status = 200;
      $result_message = 'There was an error linking the file for viewing, uh oh.';
      writeToLog($result_message, true, $uid, $mid);
      $return_info[] = array('title' => $batch_title, 'mid' => 0, 'status' => $result_status, 'status_message' => $result_message);
      continue;
    }
    // save an HTML5 symlink
    $html5_entry_path = $html5_base_path.$mid.'_'.random_hash().'.mp3';
    $symlink_result = symlink($file_path_out, $html5_entry_path);
    $updated_entry['html5'] = $html5_entry_path;
    // ok now get the filesize of the original file
    $filesize_original = @filesize($file_path_out) * 1;
    // it just has one path
    $media_paths = array('p' => $file_path_out, 'f' => $fms_path_out, 'b' => $audio_info['b'], 'e' => true, 'fs' => $filesize_original);
    // add duration to the media entry
    $updated_entry['du'] = $audio_info['d'];
    // add the paths to the updated entry
    $updated_entry['pa'] = array();
    $updated_entry['pa']['in'] = $file_path_in;
    $updated_entry['pa']['c'] = $media_paths;

  } else if ($media_metatype == 'image') {

    $file_path_out = $folder_path_out.$mid.$uploaded_extension;
    $link_files = link($file_path_in, $file_path_out);
    if ($link_files == false) {
      // uh oh, could not link the file for some reason!
      $result_status = 200;
      $result_message = 'There was an error linking the file for viewing, uh oh.';
      writeToLog($result_message, true, $uid, $mid);
      $return_info[] = array('title' => $batch_title, 'mid' => 0, 'status' => $result_status, 'status_message' => $result_message);
      continue;
    }
    // save an HTML5 symlink
    $html5_entry_path = $html5_base_path.$mid.'_'.random_hash().$uploaded_extension;
    $symlink_result = symlink($file_path_out, $html5_entry_path);
    $updated_entry['html5'] = $html5_entry_path;
    // ok now get the filesize of the original file
    $filesize_original = @filesize($file_path_out) * 1;
    // add the paths to the updated entry
    $updated_entry['pa'] = array();
    $updated_entry['pa']['in'] = $file_path_in;
    $updated_entry['pa']['c'] = $file_path_out;
    $updated_entry['pa']['fs'] = $filesize_original;

    // make thumbnail
    $make_thumb_result = makeImageThumbnail($mid, $file_path_in);
    if ($make_thumb_result == false) {

      // WAT DO?

    }

  } else if ($media_metatype == 'doc') {

    $file_path_out = $folder_path_out.$mid.$uploaded_extension;
    $link_files = link($file_path_in, $file_path_out);
    if ($link_files == false) {
      // uh oh, could not link the file for some reason!
      $result_status = 200;
      $result_message = 'There was an error linking the file for viewing, uh oh.';
      writeToLog($result_message, true, $uid, $mid);
      $return_info[] = array('title' => $batch_title, 'mid' => 0, 'status' => $result_status, 'status_message' => $result_message);
      continue;
    }
    $filesize_original = @filesize($file_path_out) * 1;
    // add the paths to the updated entry
    $updated_entry['pa'] = array();
    $updated_entry['pa']['in'] = $file_path_in;
    $updated_entry['pa']['c'] = $file_path_out;
    $updated_entry['pa']['fs'] = $filesize_original;

  }

  // based on if it's a file swap or not, determine whether to immediately allow access
    if ($is_file_swap) {
        // can it be enabled already?
        if ($result_status == 100 || $result_status == 101) {
        	$updated_entry['en'] = true;
        } else {
        	$updated_entry['en'] = false;
        }
    } else {
        // this is waiting to be submitted, so hide it entirely...
        $updated_entry['pending'] = true;
        // make this temporary thing only viewable by owners
        // of which it has none (so nobody can see it yet)
        $updated_entry['ul'] = 0;
    }

  // update timestamp, lol
  $updated_entry['tsu'] = time();

  writeToLog('Updated entry info: '.oneLinePrintArray($updated_entry), false, $uid, $mid);

  // update median entry document with all of this updated info
  try {
    $update = $media_collection->update(array('mid' => $mid), array('$set' => $updated_entry), array('w' => 1));
  } catch(MongoCursorException $e) {
    // error writing to the database!
    $result_status = 200;
    $result_message = 'Error updating the database record.';
    writeToLog('Could not update Mongo for some reason!', true, $uid, $mid);
  }

  // ok, all done successfully, add the resulting info to the array
  // this'll be returned to the end user after this loop is done processing all files
  $return_info[] = array('title' => $batch_title, 'mid' => $mid, 'status' => $result_status, 'status_message' => $result_message);

}

// return an array of media IDs and resulting info for each
// to the end user, which'll be interpreted by client-side javascript
if (count($return_info) == 0) {
  echo json_encode(array('error' => 'No media actually uploaded.'));
  writeToLog('No media actually uploaded, weird.', true, $uid, $mid);
} else {
  echo json_encode($return_info);
  writeToLog('Info returned to client: '.oneLinePrintArray($return_info), false, $uid, $mid);
}
