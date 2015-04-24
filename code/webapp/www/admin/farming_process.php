<?php

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
	bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

if (!isset($_GET['t']) || trim($_GET['t']) == '') {
	bailout('No action set...');
}

require_once('/median-webapp/includes/dbconn_mongo.php');
$farmdb = $m->farm;

//echo '<pre>'.print_r($_GET, true).'</pre>';
//echo '<pre>'.print_r($_POST, true).'</pre>';

$action = strtolower(trim($_GET['t']));

if ($action == 'u') {

	// update farmers...

	if (!isset($_POST['fid']) || !is_array($_POST['fid'])) {
		die('ugh no FIDs');
	}

	if (!isset($_POST['n']) || !is_array($_POST['n'])) {
		die('ugh no names');
	}

	if (!isset($_POST['hn']) || !is_array($_POST['hn'])) {
		die('ugh no hostnames');
	}

	if (!isset($_POST['e']) || !is_array($_POST['e'])) {
		die('ugh no enableds');
	}

	for ($i = 0; $i < count($_POST['fid']); $i++) {

		$farmer_id = new MongoId(trim($_POST['fid'][$i]));

		$updated_farmer = array();
		$updated_farmer['n'] = trim($_POST['n'][$i]);
		$updated_farmer['hn'] = trim($_POST['hn'][$i]);
		if (trim($_POST['e'][$i]) == '1') {
			$updated_farmer['e'] = true;
		} else {
			$updated_farmer['e'] = false;
		}

		try {
			$farmdb->farmers->update( array('_id' => $farmer_id), array('$set' => $updated_farmer), array('w' => 1) );
		} catch(MongoCursorException $e) {
			die('Error adding entry to mongo...');
		}

	}

	header('Location: farming.php');

}
