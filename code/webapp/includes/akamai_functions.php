<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH USING AKAMAI
		cyle gage, emerson college, 2012


	getAkamaiEntries()
	getAkamaiFiles()
	addMediaToAkamai($mid, $uid)
	removeMediaFromAkamai($mid)

	akamai record in mongo:

	Array (
		'mid' => 10101,
		'uid' => 1,
		'tsc' => 1282221324,
		'a_path' => '/path/on/akamai',
		'm_path' => '/path/on/median',
		'fms_path' => 'mp4:whatever', // fms path on akamai, not median
		'b' => 1280, // bitrate
	)

*/

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/dbconn_mongo.php');
require_once('/median-webapp/includes/media_functions.php');

// fetch current akamai entries from database
function getAkamaiEntries() {
	global $mdb;
	$mids = array();
	$get_akamai_entries = $mdb->akamai->find();
	if ($get_akamai_entries->count() > 0) {
		foreach ($get_akamai_entries as $entry) {
			$mids[] = $entry['mid'];
		}
	}
	return $mids;
}

// fetch a listing of the files currently on akamai
function getAkamaiFiles() {

	global $akamai_ftp_host, $akamai_ftp_user, $akamai_ftp_pass, $akamai_ftp_path_prefix;

	$ftp_conn = ftp_connect($akamai_ftp_host);
	$ftp_login = ftp_login($ftp_conn, $akamai_ftp_user, $akamai_ftp_pass);
	if ($ftp_login == false) {
		return false;
	}
	ftp_pasv($ftp_conn, true);
	$buffer = ftp_rawlist($ftp_conn, $akamai_ftp_path_prefix);
	ftp_close($ftp_conn);

	$files = array();

	if (is_array($buffer) && count($buffer) > 0) {
		foreach ($buffer as $line) {
			//echo '<div>'.$line.'</div>';
			// don't bother with directories...
			if (substr($line, 0, 1) != 'd') {
				preg_match_all('([-\w\:\.]+)', $line, $matches);
				//echo '<pre>'.print_r($matches, true).'</pre>';
				$files[] = array( 'size' => $matches[0][4], 'date' => ($matches[0][5].' '.$matches[0][6].' '.$matches[0][7]), 'name' => $matches[0][8] );
			}
		}
	}

	return $files;

}

// add a median entry/file to akamai's storage
function addEntryToAkamai($mid = 0, $uid = 0) {

	if (!isset($mid) || !is_numeric($mid)) {
		return false;
	}

	$mid = (int) $mid * 1;
	$uid = (int) $uid * 1;

	global $mdb, $akamai_ftp_host, $akamai_ftp_user, $akamai_ftp_pass, $akamai_ftp_path_prefix;

	// check if it's already there
	$check_duplicate = $mdb->akamai->find( array('mid' => $mid) );
	if ($check_duplicate->count() > 0) {
		return false;
	}

	// get media paths
	$media_paths = getMediaPaths($mid);
	if ($media_paths == false || !isset($media_paths['c']) || !is_array($media_paths['c'])) {
		return false;
	}

	// pick a path
	$median_file_path = '';
	$bitrate = 0;
	foreach ($media_paths['c'] as $path) {
		if (!isset($path['e']) || $path['e'] != true) {
			continue;
		}
		// take the first one
		if ($median_file_path == '') {
			$median_file_path = $path['p'];
			$bitrate = $path['b'];
		}
		// but always prefer the one with the sweet spot bitrate
		if ($path['b'] >= 1000 && $path['b'] < 2000) {
			$median_file_path = $path['p'];
			$bitrate = $path['b'];
		}
	}

	if ($median_file_path == '' || $bitrate == 0) {
		return false;
	}

	$file_name = strrchr($median_file_path, '/');
	$akamai_ftp_path = $akamai_ftp_path_prefix.$file_name;
	$akamai_fms_path = 'mp4:_definst_'.$akamai_ftp_path;

	$ftp_conn = ftp_connect($akamai_ftp_host);
	$ftp_login = ftp_login($ftp_conn, $akamai_ftp_user, $akamai_ftp_pass);
	ftp_pasv($ftp_conn, true);
	$buffer = ftp_put($ftp_conn, $ftp_path, $file_path, FTP_BINARY);
	ftp_close($ftp_conn);
	if ($buffer == false) {
		return false;
	}

	$new_entry = array();
	$new_entry['tsc'] = time();
	$new_entry['mid'] = $mid;
	$new_entry['uid'] = $uid;
	$new_entry['b'] = $bitrate;
	$new_entry['a_path'] = $akamai_ftp_path;
	$new_entry['m_path'] = $median_file_path;
	$new_entry['fms_path'] = $akamai_fms_path;

	try {
		$insert = $mdb->akamai->insert($new_entry, array('w'=>1));
	} catch(MongoCursorException $e) {
		return false;
	}

	return true;

}

// remove an entry/file from akamai's servers
function removeMediaFromAkamai($mid = 0) {

	if (!isset($mid) || !is_numeric($mid)) {
		return false;
	}

	$mid = (int) $mid * 1;

	global $mdb, $akamai_ftp_host, $akamai_ftp_user, $akamai_ftp_pass, $akamai_ftp_path_prefix;

	$get_info = $mdb->akamai->findOne( array('mid' => $mid) );
	if (!isset($get_info)) {
		return true; // it's not even there...
	}

	$ftp_conn = ftp_connect($akamai_ftp_host);
	$ftp_login = ftp_login($ftp_conn, $akamai_ftp_user, $akamai_ftp_pass);
	$buffer = ftp_delete($ftp_conn, $get_info['a_path']);
	ftp_close($ftp_conn);
	if ($buffer == false) {
		return false;
	}
	try {
		$remove = $mdb->akamai->remove(array('mid' => $mid), array('w'=>1));
	} catch(MongoCursorException $e) {
		return false;
	}
	return true;

}
