<?php

/*

	go through each entry
	only audio, video, or image
	with html5 not set
	and not pending
	
	get appropriate file for html5
	get random ID to use
	add symlink in /median/files/html5/ to that file
	

*/

// where are we gonna save these HTML5 symlinks
$html5_base_path = '/median/files/html5/';

// a helper function to create the pretty random hex string
function random_hash() {
	return bin2hex(openssl_random_pseudo_bytes(16));
}

// connect to mongodb to save our updates
require_once(__DIR__.'/../includes/dbconn_mongo.php');

// get all of the video, audio, or image entries in median that don't have HTML5 info already
$get_mids = $mdb->media->find( array( 'html5' => array('$exists' => false), 'mt' => array('$in' => array('video', 'audio', 'image')), 'en' => true ), array( 'mid' => true, 'mt' => true, 'pa' => true ) )->sort( array('mid' => 1) );

// go through em all
foreach ($get_mids as $entry) {
	echo 'checking '.$entry['mid']."\n";
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
		echo 'source file for '.$entry['mid'].': '.$source_file_path."\n";
		echo 'new HTML5 path for '.$entry['mid'].': '.$html5_entry_path."\n";
		// source file exists?
		if (file_exists($source_file_path)) {
			// cool, let's do this
			$symlink_result = symlink($source_file_path, $html5_entry_path);
			if ($symlink_result) {
				// ok awesome, now update the mongo record
				$doc_updates = array( '$set' => array( 'html5' => $html5_entry_path ) );
				$update = $mdb->media->update( array('mid' => $entry['mid']), $doc_updates );
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

// all done, yay
