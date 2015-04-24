<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH ERROR REPORTING
		cyle gage, emerson college, 2012


	bailout($error_message, $uid, $mid, $additional_data, $display_page)
	writeToErrorLog($error_message, $uid, $mid, $additional_data)

*/
require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/dbconn_mongo.php');

// use this to cleanly fail at a script, log the error, show a nice page
function bailout($error_message = 'There was an error of some kind, sorry!', $uid = 0, $mid = 0, $additional_data = '', $display_page = true) {
	global $mdb, $error_page_path;

	$new_error = array();
	$new_error['ts'] = time();
	$new_error['u'] = trim($_SERVER['REQUEST_URI']);
	$new_error['m'] = $error_message;
	if (isset($additional_data) && trim($additional_data) != '') {
		$new_error['a'] = $additional_data;
	}
	if (isset($uid) && $uid * 1 > 0) {
		$new_error['uid'] = (int) $uid * 1;
	}
	if (isset($mid) && $mid * 1 > 0) {
		$new_error['mid'] = (int) $mid * 1;
	}

	$save_error = $mdb->error_log->insert($new_error);

	if (isset($display_page) && $display_page == true) {
		// use the actual error page with full formatting
		require_once($error_page_path);
	} else {
		// if not, just show a very basic message page
		echo '<html><body style="border:1px solid black;border-radius:3px;padding:10px;margin:2px;font-family:monospace;font-size:14px;"><b>Median Error:</b> '.$error_message.'</body></html>';
	}
	die(); // PANIC
}

// write something to the error log for review later
function writeToErrorLog($error_message = 'there was an error of some kind', $uid = 0, $mid = 0, $additional_data = '') {
	global $mdb;

	$new_error = array();
	$new_error['ts'] = time();
	$new_error['u'] = trim($_SERVER['REQUEST_URI']);
	$new_error['m'] = $error_message;
	if (isset($additional_data) && trim($additional_data) != '') {
		$new_error['a'] = $additional_data;
	}
	if (isset($uid) && $uid * 1 > 0) {
		$new_error['uid'] = (int) $uid * 1;
	}
	if (isset($mid) && $mid * 1 > 0) {
		$new_error['mid'] = (int) $mid * 1;
	}

	$save_error = $mdb->error_log->insert($new_error);

}
