<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH FILES
		cyle gage, emerson college, 2014

	most of these functions rely heavily on the file API servers
	because the file storage itself isn't available on the web tier

	makeNewThumbnail($mid, $random = false)
	addDeleteFileToOperationsQueue($path)
	addSymlinkToOperationsQueue($path_source, $path_target)
	sendImageToFileAPI($local_path, $intended_path, $type)
	deleteVideoVersion($mid, $bitrate)

*/

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/dbconn_mongo.php');
require_once('/median-webapp/includes/config_functions.php');
require_once('/median-webapp/includes/media_functions.php');

function makeNewThumbnail($mid, $random = false) {
	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}

	$mid = (int) $mid * 1;

	// send request to file API and return true/false

	// get whatever the current best file API endpoint URL is
	$fileapi_url = getFileAPI();

	// if there is no file API to use, panic
	if ($fileapi_url == false) {
		return false;
	}

	// we are just making a new thumb
	$fileapi_url .= 'make-thumb/'.$mid.'/';
	if (isset($random) && $random == true) {
		$fileapi_url .= 'random/';
	} else {
		$fileapi_url .= 'standard/';
	}

	// ok send it along to the file API
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $fileapi_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	$result = curl_exec($ch);
	curl_close($ch);

	// result should be JSON, parse it
	$json_result = json_decode($result, true);
	if (isset($json_result['success']) && $json_result['success'] == 'yes') {
		return true;
	} else {
		return false;
	}
}

// this function adds a "delete" file operation to the queue for the file API
function addDeleteFileToOperationsQueue($path) {
	if (!isset($path) || trim($path) == '') {
		return false;
	}
	global $m6db, $thumbs_path;

	$path = trim($path);

	// correct for certain incoming paths
	if (substr($path, 0, 4) == 'art/') {
		$path = $thumbs_path . $path;
	} else if (substr($path, 0, 8) == '/thumbs/') {
		$path = str_replace('/thumbs/', $thumbs_path, $path);
	}

	try {
		$insert = $m6db->file_ops->insert(array('tsc' => time(), 'path' => $path, 'action' => 'delete'), array('w' => 1));
	} catch(MongoCursorException $e) {
		return false;
	}
	return true;
}

// this function adds a "symlink" file operation to the queue for the file API
function addSymlinkToOperationsQueue($path_source, $path_target) {
	if (!isset($path_source) || trim($path_source) == '') {
		return false;
	}
	if (!isset($path_target) || trim($path_target) == '') {
		return false;
	}
	global $m6db;

	try {
		$insert = $m6db->file_ops->insert(array('tsc' => time(), 'action' => 'symlink', 'source' => $path_source, 'target' => $path_target), array('w' => 1));
	} catch(MongoCursorException $e) {
		return false;
	}
	return true;
}

// send an image from the local filesystem to the file API
function sendImageToFileAPI($local_path, $intended_path, $type, $mid = 0) {

	if (!isset($local_path) || trim($local_path) == '') {
		return false;
	}

	if (!isset($intended_path) || trim($intended_path) == '') {
		return false;
	}

	if (!isset($type) || trim($type) == '') {
		return false;
	}

	$acceptable_types = array('art', 'thumb');
	$type = strtolower(trim($type));

	if (!in_array($type, $acceptable_types)) {
		return false;
	}

	global $thumbs_path;

	$local_path = trim($local_path);
	$intended_path = $thumbs_path . trim($intended_path);

	// get whatever the current best file API endpoint URL is
	$fileapi_url = getFileAPI();

	// if there is no file API to use, panic
	if ($fileapi_url == false) {
		return false;
	}

	// we are uploading a custom image
	$fileapi_url .= 'custom-image/';

	// here's what we'll send along to the file API
	$post_data = array('path' => $intended_path, 'type' => $type, 'the_file' => '@'.$local_path);
	if ($type == 'thumb') {
		if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
			return false;
		}
		$post_data['mid'] = $mid;
	}

	// ok send it along to the file API
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $fileapi_url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	$result = curl_exec($ch);
	curl_close($ch);

	// delete the local file regardless
	unlink($local_path);

	// did it work?
	if ($result == 'ok') {
		return true;
	} else {
		return false;
	}

}

// delete a certain version of a video
function deleteVideoVersion($mid, $bitrate) {
	
	global $mdb;
	
	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}

	if (!isset($bitrate) || !is_numeric($bitrate) || $bitrate * 1 == 0) {
		return false;
	}
	
	// we need the mid and the bitrate
	$mid = (int) $mid * 1;
	$bitrate = (int) $bitrate * 1;
	
	// get the media paths for this entry
	$paths = getMediaPaths($mid);
	
	// make sure there even are content paths and that it's an array
	// otherwise we're probably dealing with a video
	if (!isset($paths['c']) || !is_array($paths['c'])) {
		return false;
	}
	
	$file_to_delete = '';
	
	// go through each content path, find the bad one
	for ($i = 0; $i < count($paths['c']); $i++) {
		// once found, remove it
		if ($paths['c'][$i]['b'] * 1 == $bitrate) {
			$file_to_delete = $paths['c'][$i]['p']; // save the file to delete
			unset($paths['c'][$i]);
		}
	}
	
	// reindex it properly
	$paths['c'] = array_values($paths['c']);
	
	// save the new paths to the entry
	try {
		$set_new_paths = $mdb->media->update(array('mid' => $mid), array('$set' => array('pa' => $paths)), array('w' => 1));
	} catch(MongoCursorException $e) {
		return false;
	}
	
	// add the output file to the queue to be deleted
	if (trim($file_to_delete) != '') {
		$queue_result = addDeleteFileToOperationsQueue($file_to_delete);
	}
	
	// cool, done
	return true;
	
}