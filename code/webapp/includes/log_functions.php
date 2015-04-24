<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH LOGGING
		cyle gage, emerson college, 2012


	oneLinePrintArray($array)
	openLogFile($filename)
	closeLogFile($handle)
	writeToLog($message, $log, $uid, $mid)

*/

require_once('/median-webapp/config/config.php');

// print an array in a friendly way, in one line
function oneLinePrintArray($array = array()) {
	$string = print_r($array, true);
	$string = str_replace(array("\n", "\t", "\r"), ' ', $string);
	return $string;
}

function openLogFile($file = '') {
	if (!isset($file) || trim($file) == '') {
		return false;
	}
	global $logs_dir;
	if (!file_exists($logs_dir.$file)) {
		return false;
	}
	$log = fopen($logs_dir.$file, 'a+');
	return $log;
}

function closeLogFile($handle = null) {
	if (!isset($handle)) {
		return false;
	}
	fclose($handle);
	return true;
}

function writeToLog($message = 'there was an error', $log = null, $uid = 0, $mid = 0) {
	if (!isset($log) || $log == false) {
		return false;
	}

	$what = '';
	$what .= date('m-d-y G:i:s');
	if (isset($uid) && is_numeric($uid) && $uid > 0) {
		$what .= ' U#'.$uid;
	}
	if (isset($mid) && is_numeric($mid) && $mid > 0) {
		$what .= ' M#'.$mid;
	}
	$what .= ' '.$message;
	$what .= "\n";
	fwrite($log, $what);
	return true;
}
