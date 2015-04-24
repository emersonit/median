#!/usr/bin/env php
<?php

function bailout($message) {
	fwrite(STDERR, $message."\n");
	exit(1);
}

if (php_sapi_name() != 'cli') {
	bailout("Not using CLI!");
}

// median 6 file CLI functions...
// use this to test stuff

if (!isset($argv[1]) || trim($argv[1]) == '') {
	echo 'THE MEDIAN FILE API CLI TOOL, OH BOY. HELP? OH DEAR.

Available arguments:

get-media-info [mid]
get-media-paths [mid]
get-thumbs [mid] [json?]
get-file-info [mid] [json?]
get-video-info [mid or path] [debug?]
get-audio-info [mid or path] [debug?]
new-thumb [mid] [random|standard]
custom-thumb [mid] [path]
retranscode [mid]
verify-download [mid] [download-code]
do-file-queue
fix-and-cleanup-mids
get-mid-filesize [mid]
';
	exit(0);
}

require_once(__DIR__.'/includes/config.php');
require_once(__DIR__.'/includes/dbconn_mongo.php');
require_once(__DIR__.'/includes/media_functions.php');
require_once(__DIR__.'/includes/file_functions.php');

// a helper function to create the pretty random hex string
function random_hash() {
	return bin2hex(openssl_random_pseudo_bytes(16));
}

$first_arg = strtolower(trim($argv[1]));

switch ($first_arg) {

	/*

		get media info

	*/
	case 'get-media-info':
	if (!isset($argv[2]) || !is_numeric($argv[2])) {
		bailout('Invalid media ID given.');
	}
	$mid = (int) $argv[2] * 1;
	$media_info = getMediaInfo($mid);
	if ($media_info == false) {
		bailout('No media entry found with that ID, sorry!');
	}
	if (isset($media_info['pa'])) { unset($media_info['pa']); }
	print_r($media_info);
	break;
	/*

		get media paths

	*/
	case 'get-media-paths':
	if (!isset($argv[2]) || !is_numeric($argv[2])) {
		bailout('Invalid media ID given.');
	}
	$mid = (int) $argv[2] * 1;
	$media_paths = getMediaPaths($mid);
	if ($media_paths == false) {
		bailout('No media entry found with that ID, sorry!');
	}
	print_r($media_paths);
	break;
	/*

		get thumbs

	*/
	case 'get-thumbs':
	if (!isset($argv[2]) || !is_numeric($argv[2])) {
		bailout('Invalid media ID given.');
	}
	$mid = (int) $argv[2] * 1;
	$thumb_paths = getThumbnails($mid);
	if ($thumb_paths == false) {
		bailout('No media entry found with that ID, sorry!');
	}
	if (isset($argv[3]) && (trim($argv[3]) == 'json' || trim($argv[3]) == 'yes')) {
		$return_as_json = true;
	} else {
		$return_as_json = false;
	}
	if ($return_as_json) {
		echo json_encode($thumb_paths);
	} else {
		print_r($thumb_paths);
	}
	break;
	/*

		make a new thumbnail for mid

	*/
	case 'new-thumb':
	if (!isset($argv[2]) || !is_numeric($argv[2])) {
		bailout('Invalid media ID given.');
	}
	$mid = (int) $argv[2] * 1;
	// make sure this mid is a video...
	$media_info = getMediaInfo($mid);
	if ($media_info == false) {
		bailout('No media entry found with that ID, sorry!');
	}
	if ($media_info['mt'] != 'video' && $media_info['mt'] != 'image') {
		bailout('That media entry is not a video or image!');
	}
	// make a new thumbnail using a method...
	$thumb_method = 'standard';
	if (isset($argv[3])) {
		if (strtolower(trim($argv[3])) == 'standard') {
			$thumb_method = 'standard';
		} else if (strtolower(trim($argv[3])) == 'random') {
			$thumb_method = 'random';
		}
	}
	$done = false;
	if ($thumb_method == 'random') {
		if ($media_info['mt'] == 'video') {
			$done = makeVideoThumbnails($mid, true, $media_info['du']);
		} else if ($media_info['mt'] == 'image') {
			$done = makeImageThumbnail($mid);
		}
	} else if ($thumb_method == 'standard') {
		if ($media_info['mt'] == 'video') {
			$done = makeVideoThumbnails($mid, false, $media_info['du']);
		} else if ($media_info['mt'] == 'image') {
			$done = makeImageThumbnail($mid);
		}
	} else {
		bailout('uhhh');
	}
	if ($done) {
		echo 'done';
	} else {
		bailout('There was an error creating the thumbnails.');
	}
	break;
	/*

		set custom thumbnail for mid

	*/
	case 'custom-thumb':
	if (!isset($argv[2]) || !is_numeric($argv[2])) {
		bailout('Invalid media ID given.');
	}
	$mid = (int) $argv[2] * 1;
	// make sure file exists,
	// then make a small thumb out of it
	// and replace the entry's metadata accordingly
	if (!isset($argv[3]) || trim($argv[3]) == '') {
		bailout('No custom file path specified.');
	}
	$thumb_path = trim($argv[3]);
	if (!file_exists($thumb_path)) {
		bailout('Specified custom file path does not exist.');
	}
	$thumb_result = makeImageThumbnail($mid, $thumb_path);
	if ($thumb_result) {
		echo 'done';
	} else {
		bailout('There was an error creating the thumbnails.');
	}
	break;
	/*

		get file info

	*/
	case 'get-file-info':
	if (!isset($argv[2]) || trim($argv[2]) == '') {
		bailout('You must provide either a media ID to check.');
	}
	if (is_numeric($argv[2]) && $argv[2] * 1 > 0) {
		// it's a media ID, get its path
		$mid = (int) $argv[2] * 1;
		$media_info = getMediaInfo($mid);
		if ($media_info == false) {
			bailout('No media entry found with that ID, sorry!');
		}
		if ($media_info['mt'] == 'video') {
			$media_type = 'video';
		} else if ($media_info['mt'] == 'audio') {
			$media_type = 'audio';
		} else {
			bailout('Not sure how to check this, it is not a video or audio entry.');
		}
		if (!isset($media_info['pa']) || !isset($media_info['pa']['in'])) {
			bailout('No paths available to get info from!');
		}
		$media_path = $media_info['pa']['in'];
	} else {
		bailout('You must provide either a media ID to check.');
	}
	if (file_exists($media_path) == false) {
		bailout('That file does not exist!');
	}
	if (isset($argv[3]) && (trim($argv[3]) == 'json' || trim($argv[3]) == 'yes')) {
		$return_as_json = true;
	} else {
		$return_as_json = false;
	}
	if ($media_type == 'video') {
		$video_info = getVideoFileInfo($media_path);
		if (is_numeric($video_info)) {
			switch ($video_info) {
				case -100:
				bailout('File not found!');
				break;
				case -101:
				bailout('No duration or bitrate info found!');
				break;
				case -102:
				bailout('No video codec info found!');
				break;
				case -103:
				bailout('Video codec is apple-proprietary!');
				break;
				case -104:
				bailout('Video has no stream, probably Quicktime Ref file.');
				break;
				default:
				bailout('There was some kind of error!');
			}
		} else {
			if ($return_as_json) {
				echo json_encode($video_info);
			} else {
				print_r($video_info);
			}
		}
	} else if ($media_type == 'audio') {
		$audio_info = getAudioFileInfo($media_path);
		if (is_numeric($audio_info)) {
			switch ($audio_info) {
				case -100:
				bailout('File not found!');
				break;
				case -101:
				bailout('No duration or bitrate info found!');
				break;
				default:
				bailout('There was some kind of error!');
			}
		} else {
			if ($return_as_json) {
				echo json_encode($audio_info);
			} else {
				print_r($audio_info);
			}
		}
	} else {
		bailout('Not sure how to check this, it is not a video or audio entry.');
	}
	break;
	/*

		get video info

	*/
	case 'get-video-info':
	if (!isset($argv[2]) || trim($argv[2]) == '') {
		bailout('You must provide either a media ID or a file path to check.');
	}
	if (is_numeric($argv[2]) && $argv[2] * 1 > 0) {
		// it's a media ID, get its path
		$mid = (int) $argv[2] * 1;
		$media_info = getMediaInfo($mid);
		if ($media_info == false) {
			bailout('No media entry found with that ID, sorry!');
		}
		if ($media_info['mt'] != 'video') {
			bailout('That media entry is not a video!');
		}
		if (!isset($media_info['pa']) || !isset($media_info['pa']['in'])) {
			bailout('No paths available to get info from!');
		}
		$media_path = $media_info['pa']['in'];
	} else {
		// assume it's a path, then
		$media_path = trim($argv[2]);
	}
	if (file_exists($media_path) == false) {
		bailout('That file does not exist!');
	}
	if (isset($argv[3]) && (trim($argv[3]) == 'debug' || trim($argv[3]) == 'yes')) {
		$debug = true;
	} else {
		$debug = false;
	}
	$video_info = getVideoFileInfo($media_path, $debug);
	if (is_numeric($video_info)) {
		switch ($video_info) {
			case -100:
			bailout('File not found!');
			break;
			case -101:
			bailout('No duration or bitrate info found!');
			break;
			case -102:
			bailout('No video codec info found!');
			break;
			case -103:
			bailout('Video codec is apple-proprietary!');
			break;
			case -104:
			bailout('Video has no stream, probably Quicktime Ref file.');
			break;
			default:
			bailout('There was some kind of error!');
		}
	} else {
		print_r($video_info);
	}
	break;
	/*

		get audio info

	*/
	case 'get-audio-info':
	if (!isset($argv[2]) || trim($argv[2]) == '') {
		bailout('You must provide either a media ID or a file path to check.');
	}
	if (is_numeric($argv[2]) && $argv[2] * 1 > 0) {
		$mid = (int) $argv[2] * 1;
		$media_info = getMediaInfo($mid);
		if ($media_info == false) {
			bailout('No media entry found with that ID, sorry!');
		}
		if ($media_info['mt'] != 'audio') {
			bailout('That media entry is not an audio entry!');
		}
		if (!isset($media_info['pa']) || !isset($media_info['pa']['in'])) {
			bailout('No paths available to get info from!');
		}
		$media_path = $media_info['pa']['in'];
	} else {
		// assume it's a path, then
		$media_path = trim($argv[2]);
	}
	if (file_exists($media_path) == false) {
		bailout('That file does not exist!');
	}
	if (isset($argv[3]) && (trim($argv[3]) == 'debug' || trim($argv[3]) == 'yes')) {
		$debug = true;
	} else {
		$debug = false;
	}
	$audio_info = getAudioFileInfo($media_path, $debug);
	if (is_numeric($audio_info)) {
		switch ($audio_info) {
			case -100:
			bailout('File not found!');
			break;
			case -101:
			bailout('No duration or bitrate info found!');
			break;
			default:
			bailout('There was some kind of error!');
		}
	} else {
		print_r($audio_info);
	}
	break;
	/*

		retranscode entry

	*/
	case 'retranscode':
	if (!isset($argv[2]) || !is_numeric($argv[2])) {
		bailout('Invalid media ID given.');
	}
	$mid = (int) $argv[2] * 1;
	$media_info = getMediaInfo($mid);
	if ($media_info == false) {
		bailout('Media entry does not exist, sorry!');
	}
	$retranscode = reTranscodeMedia($mid);
	if ($retranscode == false) {
		bailout('Could not retranscode this for some reason, sorry!');
	} else {
		echo 'Done. Should be transcoding now.';
	}
	break;
	/*

		verify download code for media ID

	*/
	case 'verify-download':
	if (!isset($argv[2]) || !is_numeric($argv[2])) {
		bailout('Invalid media ID given.');
	}
	$mid = (int) $argv[2] * 1;
	if (!isset($argv[3]) || trim($argv[3]) == '') {
		bailout('No download hash code given.');
	}
	$download_code = trim($argv[3]);
	$media_info = getMediaInfo($mid);
	if ($media_info == false) {
		bailout('Media entry does not exist, sorry!');
	}
	// verify the code
	$verify_code = verifyAndUseDownloadCode($mid, $download_code);
	if ($verify_code !== false) {
		// ok it's cool, return the path to download
		echo $verify_code; // this will be the path to the download file
	} else {
		bailout('Download code failed, sorry.');
	}
	break;
	/*

		go through the file operations queue

	*/
	case 'do-file-queue':
	$jobs_done = 0;
	$get_jobs = $m6db->file_ops->find();
	if ($get_jobs->count() > 0) {
		$get_jobs->sort( array( 'tsc' => 1 ) );
		foreach ($get_jobs as $job) {
			// do the job
			if (!isset($job['action'])) {
				// ignore it
				continue;
			}
			$job_done = false;
			// what action do we take?
			if ($job['action'] == 'delete') {
				if (isset($job['path']) && file_exists($job['path'])) {
					$job_done = unlink($job['path']);
				}
			} else if ($job['action'] == 'symlink') {
				if (isset($job['source']) && file_exists($job['source']) && isset($job['target'])) {
					$job_done = symlink($job['source'], $job['target']);
				}
			} else {
				// uhh not sure what to do
			}
			// ok, if the job ws done, we do things now
			if ($job_done) {
				// delete the job
				try {
					$delete_job = $m6db->file_ops->remove( array('_id' => $job['_id']) );
				} catch(MongoCursorException $e) {
					bailout('Could not delete job from the queue!');
				}
				if (isset($delete_job['n']) && $delete_job['n'] == 1) {
					$jobs_done++;
				}
			}
		}
	}
	echo 'completed '.$jobs_done.' job(s)';
	break;
	/*

		fix entries that are disabled but should be enabled
		also clean up any media entries that should be deleted

	*/
	case 'fix-and-cleanup-mids':
	$get_media = $mdb->media->find( array('en' => false, 'pending' => array('$exists' => false)) )->sort(array('mid' => -1));
	if ($get_media->count() > 0) {
		foreach ($get_media as $entry) {
			if (!isset($entry['mt'])) {
				continue;
			} else if ($entry['mt'] != 'video' && $entry['mt'] != 'audio') {
				continue;
			}
			$mid = (int) $entry['mid'] * 1;
			if (isset($entry['pa']) && isset($entry['pa']['c']) && count($entry['pa']['c']) > 0) {
				$path_enabled = false;
				$path_works = false;
				if ($entry['mt'] == 'audio') {
					if (isset($entry['pa']['c']['e']) && $entry['pa']['c']['e'] == true) {
						$path_enabled = true;
					}
					if (isset($entry['pa']['c']['p']) && file_exists($entry['pa']['c']['p'])) {
						$path_works = true;
					}
				} else if ($entry['mt'] == 'video') {
					foreach ($entry['pa']['c'] as $media_path) {
						if (isset($media_path['e']) && $media_path['e'] == true) {
							$path_enabled = true;
						}
						if (isset($media_path['p']) && file_exists($media_path['p'])) {
							$path_works = true;
						}
					}
				}
				if ($path_enabled && $path_works) {
					// entry should be enabled...
					echo 'enabling mid #'.$mid."\n";
					try {
						$update = $mdb->media->update( array('mid' => $mid), array('$set' => array('en' => true)), array('w' => 1) );
					} catch(MongoCursorException $e) {
						bailout('error updating mongo record!');
					}
				} else if (!$path_enabled && $path_works) {
					// A path should be enabled...
				} else if ($path_enabled && !$path_works) {
					// A path that is enabled does not work...
				} else {
					// disbaled and should be
				}
			} else {
				// has no media paths...
			}
		}
		unset($entry);
	} else {
		// nothing to work on, cool
	}
	// get media IDs that have no files or are perpetually pending, delete them
	$find_pending = $mdb->media->find(array('pending' => true, 'tsc' => array('$lte' => strtotime('-14 days')) ));
	if ($find_pending->count() > 0) {
		$to_delete = array();
		$find_pending->sort( array('tsc' => 1) );
		foreach ($find_pending as $entry) {
			$to_delete[] = (int) $entry['mid'] * 1;
		}
		unset($entry);
		if (count($to_delete) > 0) {
			foreach ($to_delete as $delete_mid) {
				$result = deleteMediaEntry($delete_mid);
				if ($result == false) {
					echo 'oops, could not delete #'.$delete_mid."\n";
				}
			}
		}
	}
	/*
	
		fix entries that do not have HTML5 info for some reason
	
	*/
	$get_htmlfiveless_mids = $mdb->media->find( array( 'html5' => array('$exists' => false), 'mt' => array('$in' => array('video', 'audio', 'image')), 'en' => true ), array( 'mid' => true, 'mt' => true, 'pa' => true ) )->sort( array('mid' => 1) );
	// go through em all
	foreach ($get_htmlfiveless_mids as $entry) {
		//echo 'checking '.$entry['mid']."\n";
		$html5_entry_path = ''; // hopefully we'll come up with an HTML5 path
		$source_file_path = ''; // hopefully we'll figure out where the source file is
		if ($entry['mt'] == 'audio') { // start with audio files, these are easy
			if (isset($entry['pa']['c']['p'])) {
				$source_file_path = $entry['pa']['c']['p'];
				$html5_entry_path = $html5_base_path.$entry['mid'].'_'.random_hash().'.mp3';
			}
		} else if ($entry['mt'] == 'image') { // images should be pretty easy too, may have variable extensions
			if (isset($entry['pa']['c'])) {
				$source_file_path = $entry['pa']['c'];
				// get file extension
				$image_extension = strtolower(strrchr($source_file_path, '.'));
				$html5_entry_path = $html5_base_path.$entry['mid'].'_'.random_hash().$image_extension;
			}
		} else if ($entry['mt'] == 'video') { // videos are a little more tricky
			// create the HTML5 path
			$html5_entry_path = $html5_base_path.$entry['mid'].'_'.random_hash().'.mp4';
			// check to make sure all the paths are enabled so that we can pick wisely
			$all_versions_enabled = true;
			foreach ($entry['pa']['c'] as $media_path) {
				if (!isset($media_path['e']) || $media_path['e'] == false) {
					$all_versions_enabled = false;
				}
			}
			if ($all_versions_enabled == false) {
				continue; // can't do this one yet...
			}
			// go through all of the video versions available, pick the best one
			// first see if there's an SD copy to use
			foreach ($entry['pa']['c'] as $media_path) {
				if (!isset($media_path['e']) || $media_path['e'] == false) {
					continue;
				}
				if ($media_path['b'] >= 500 && $media_path['b'] <= 1000) {
					$source_file_path = $media_path['p'];
				}
			}
			// next see if there's a mobile copy to use
			if ($source_file_path == '') {
				foreach ($entry['pa']['c'] as $media_path) {
					if (!isset($media_path['e']) || $media_path['e'] == false) {
						continue;
					}
					if ($media_path['b'] <= 500) {
						$source_file_path = $media_path['p'];
					}
				}
			}
			// if we still can't find one, just use the first one available
			if ($source_file_path == '') {
				foreach ($entry['pa']['c'] as $media_path) {
					if (!isset($media_path['e']) || $media_path['e'] == false) {
						continue;
					}
					$source_file_path = $media_path['p'];
					break; // found one, eject
				}
			}
		}
		// ok now we should have a source file to use and the path where to put the symlink
		if (trim($html5_entry_path) != '' && trim($source_file_path) != '') {
			//echo 'source file for '.$entry['mid'].': '.$source_file_path."\n";
			//echo 'new HTML5 path for '.$entry['mid'].': '.$html5_entry_path."\n";
			// source file exists?
			if (file_exists($source_file_path)) {
				// cool, let's do this
				$symlink_result = symlink($source_file_path, $html5_entry_path);
				if ($symlink_result) {
					// ok awesome, now update the mongo record
					$doc_updates = array( '$set' => array( 'html5' => $html5_entry_path ) );
					$htmlfive_update = $mdb->media->update( array('mid' => $entry['mid']), $doc_updates );
					echo 'added html5 path '.$html5_entry_path.' to entry '.$entry['mid']."\n";
				} else {
					// wuh oh
					echo 'warning: could not create html5 symlink for '.$entry['mid']."\n";
				}
			} else {
				// wuh oh
				echo 'warning: source file does not exist for '.$entry['mid']."\n";
			}
		} else {
			// wuh oh
			echo 'warning: no valid path info in entry for '.$entry['mid']."\n";
		}
	}
	
	echo 'done';
	break;
	/*

		get the total amount of space a media entry takes up

	*/
	case 'get-mid-filesize':
	if (!isset($argv[2]) || !is_numeric($argv[2])) {
		bailout('Invalid media ID given.');
	}
	$mid = (int) $argv[2] * 1;
	$media_info = getMediaInfo($mid);
	if ($media_info == false) {
		bailout('Media entry does not exist, sorry!');
	}
	$media_paths = getMediaPaths($mid);
	$filesize_in_bytes = 0;
	if (isset($media_paths['in']) && file_exists($media_paths['in'])) {
		$filesize_in_bytes += filesize($media_paths['in']);
	}
	if ($media_info['mt'] == 'video') {
		foreach ($entry['pa']['c'] as $media_path) {
			if (isset($media_path['p']) && file_exists($media_path['p'])) {
				$filesize_in_bytes += filesize($media_path['p']);
			}
		}
	} else if ($media_info['mt'] == 'audio') {
		if (isset($entry['pa']['c']['p']) && file_exists($entry['pa']['c']['p'])) {
			$filesize_in_bytes += filesize($entry['pa']['c']['p']);
		}
	} else if ($media_info['mt'] == 'image' || $media_info['mt'] == 'doc') {
		if (isset($entry['pa']['c']) && file_exists($entry['pa']['c'])) {
			$filesize_in_bytes += filesize($entry['pa']['c']);
		}
	}
	echo $filesize_in_bytes;
	break;
	/*

		otherwise, complain

	*/
	default:
	bailout('Uhhh I do not know that command, sorry.');
}
