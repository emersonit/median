<?php

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

/*

	update user's account options...

		- subtitles always on? $_POST['st']

*/

require_once('/median-webapp/includes/dbconn_mongo.php');

if (isset($_POST['st']) && trim($_POST['st']) == '1') {
	// add user row option
	try {
		$mdb->users->update( array('uid' => $current_user['userid']), array('$set' => array('st' => true)), array('w' => 1));
	} catch(MongoCursorException $e) {
		die(print_r($e, true));
	}
} else {
	// unset user row option
	try {
		$mdb->users->update( array('uid' => $current_user['userid']), array('$unset' => array('st' => '')), array('w' => 1));
	} catch(MongoCursorException $e) {
		die(print_r($e, true));
	}
}

header('Location: /manage/account/success/');
