<?php

// user name autocomplete result

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past, BACK TO THE FUTURE

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

if (isset($_GET['term']) && trim($_GET['term']) != '' && preg_match('/^[-_A-Z0-9]+$/i', trim($_GET['term']))) {

	$search_term = strtolower(trim($_GET['term']));

	require_once('/median-webapp/includes/dbconn_mongo.php');

	$possibles = array();

	$find_users = $mdb->users->find(array('ecnet' => array('$regex' => '^'.$search_term.'.*$', '$options' => 'i')), array('uid' => true, 'ecnet' => true));

	if ($find_users->count() > 0) {
		foreach ($find_users as $user) {
			$possibles[] = array('label' => $user['ecnet'], 'value' => $user['ecnet'], 'id' => $user['uid']);
		}
	}

	echo json_encode($possibles);

}
