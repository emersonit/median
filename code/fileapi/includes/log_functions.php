<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH LOGGING
		(specifically tailored for the file API)
		
		introduced for median 5
		continued use in median 6
		cyle gage, emerson college, 2012-2014

		
		writeToLog($message, $error, $uid, $mid, $additional_data) -- done

*/

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/dbconn_mongo.php');

// write a message to the mongodb log
function writeToLog($message, $error = false, $uid = 0, $mid = 0, $additional_data = '') {
	global $m6db;
	
	if (!isset($message) || trim($message) == '') {
		return false;
	}

	$new_log = array();
	$new_log['ts'] = time();
	$new_log['s'] = $_SERVER['PHP_SELF'];
	$new_log['m'] = $message;
	if (isset($error) && $error != false) {
		$new_log['e'] = true;
	} else {
		$new_log['e'] = false;
	}
	if (isset($additional_data) && is_array($additional_data)) {
		$new_log['a'] = oneLinePrintArray($additional_data);
	} else if (isset($additional_data) && is_string($additional_data) && trim($additional_data) != '') {
		$new_log['a'] = $additional_data;
	}
	if (isset($uid) && $uid * 1 > 0) {
		$new_log['uid'] = (int) $uid * 1;
	}
	if (isset($mid) && $mid * 1 > 0) {
		$new_log['mid'] = (int) $mid * 1;
	}
	
	$save_error = $m6db->log->insert($new_log);
	return true;
}

// helper function to print out an array in friendly text in one line
function oneLinePrintArray($array = array()) {
	$string = print_r($array, true);
	$string = str_replace(array("\n", "\t", "\r"), ' ', $string);
	return $string;
}