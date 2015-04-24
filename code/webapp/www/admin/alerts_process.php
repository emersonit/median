<?php

// process alerts

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
	bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

if (!isset($_GET['t']) || trim($_GET['t']) == '') {
	die('no action selected');
}

$action = strtolower(trim($_GET['t']));

require_once('/median-webapp/includes/dbconn_mongo.php');

if ($action == 'd') {
	// delete alert

	if (!isset($_GET['id']) || trim($_GET['id']) == '') {
		die('no ID given');
	}

	try {
		$mdb->alerts->remove( array('_id' => new MongoId(trim($_GET['id']))), array('w' => 1) );
	} catch(MongoCursorException $e) {
		die('Error adding entry to mongo...');
	}

	header('Location: alerts.php');

} else if ($action == 'a') {
	// add alert

	if (!isset($_POST['c']) || trim($_POST['c']) == '') {
		die('no content given');
	}

	if (isset($_POST['a']) && trim($_POST['a']) == 1) {
		$alertbox = true;
	} else {
		$alertbox = false;
	}

	try {
		$mdb->alerts->insert( array('c' => trim($_POST['c']), 'a' => $alertbox, 'e' => true, 'tsc' => time()), array('w' => 1) );
	} catch(MongoCursorException $e) {
		die('Error adding entry to mongo...');
	}

	header('Location: alerts.php');

} else {

	die('uhhhh');

}