<?php

// acknowledge a new live stream via info from M6LiveBroadcaster.swf
// expect to receive $_POST['access'] and $_POST['title']

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

if (!isset($current_user['loggedin']) || $current_user['loggedin'] == false) {
	die('ERROR: You are not logged in.');
}

require_once('/median-webapp/includes/live_functions.php');

if (!isset($_POST['access']) || trim($_POST['access']) == '') {
    die('ERROR: You did not set an access level.');
}

$access = (int) $_POST['access'] * 1;

if (!isset($_POST['title']) || trim($_POST['title']) == '') {
    $title = 'Untitled Live Stream';
} else {
    $title = trim($_POST['title']);
}

$new_stream = addNewLiveStream($current_user['userid'], $title, $access);

if ($new_stream === false) {
	echo 'ERROR: There was an error registering the live stream.';
} else {
	echo $new_stream;
}
