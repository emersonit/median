<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH ERROR REPORTING
		(specifically tailored for the file API)
		
		introduced for median 5
		continued use in median 6
		cyle gage, emerson college, 2012-2014

		
		bailout($error_message, $uid, $mid, $additional_data) -- done
		
*/

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/log_functions.php');

// write an error message in the log, JSON-ify it for the user-end
function bailout($error_message = 'There was an error of some kind, sorry!', $uid = 0, $mid = 0, $additional_data = '') {
	// write it to the log, then send it as output to the user, then exit with code 1 (error)
	writeToLog($error_message, true, $uid, $mid, $additional_data);
	echo json_encode(array('error' => $error_message));
	exit(1);
}
