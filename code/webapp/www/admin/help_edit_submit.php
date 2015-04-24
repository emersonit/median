<?php

// submit help page edit
// if $_POST['id'] not set, make a brand new page

//echo '<pre>'.print_r($_POST, true).'</pre>';

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
	bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

require_once('/median-webapp/includes/dbconn_mongo.php');

if (!isset($_POST['k']) || trim($_POST['k']) == '') {
	bailout('Short name cannot be empty.', $current_user['userid']);
}

if (!isset($_POST['t']) || trim($_POST['t']) == '') {
	bailout('Title cannot be empty.', $current_user['userid']);
}

if (!isset($_POST['id']) || trim($_POST['id']) == '') {
	// inserting

	$new_entry = array();
	$new_entry['k'] = strtolower(trim($_POST['k']));
	$new_entry['ti'] = trim($_POST['t']);
	$new_entry['c'] = trim($_POST['c']);
	$new_entry['tsc'] = time();
	$new_entry['tsu'] = time();
	$new_entry['v'] = 0;

	try {
		$insert = $mdb->helpfiles->insert($new_entry, array('w'=>1));
	} catch(MongoCursorException $e) {
		bailout('Error adding entry to mongo...', $current_user['userid']);
	}

} else {
	// updating

	$page_id = new MongoId(trim($_POST['id']));

	$updated_entry = array();
	$updated_entry['k'] = strtolower(trim($_POST['k']));
	$updated_entry['ti'] = trim($_POST['t']);
	$updated_entry['c'] = trim($_POST['c']);
	$updated_entry['tsu'] = time();

	try {
		$update = $mdb->helpfiles->update(array('_id' => $page_id), array('$set' => $updated_entry), array('w'=>1));
	} catch(MongoCursorException $e) {
		bailout('Error adding entry to mongo...', $current_user['userid']);
	}

}

header('Location: /admin/help.php');
