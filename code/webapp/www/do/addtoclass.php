<?php

// add a media entry to a class

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

if (!isset($current_user['loggedin']) || $current_user['loggedin'] == false) {
	echo 'You need to be logged in to add to a class.';
	die();
}

if (!isset($_POST['mid']) || !is_numeric($_POST['mid'])) {
	echo 'No Media ID provided...';
	die();
}

$mid = (int) $_POST['mid'] * 1;
$uid = $current_user['userid'];
$clid = strtoupper(trim($_POST['cl']));

require_once('/median-webapp/includes/permission_functions.php');

// check that user has class
if (!canViewClass($uid, $clid)) {
	echo 'You are not a member of that class, sorry.';
	die();
}

require_once('/median-webapp/includes/media_functions.php');

$add_to_class = addMediaToClass($mid, $uid, $clid);

if ($add_to_class == false) {
	echo 'There was an error adding this to the class, sorry. Please try again.';
} else {
	echo 'done';
}