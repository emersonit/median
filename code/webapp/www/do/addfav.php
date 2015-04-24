<?php

// add a media entry as my favorite

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

if (!isset($current_user['loggedin']) || $current_user['loggedin'] == false) {
	echo 'You need to be logged in to add a favorite.';
	die();
}

if (!isset($_POST['mid']) || !is_numeric($_POST['mid'])) {
	echo 'No Media ID provided...';
	die();
}

$mid = (int) $_POST['mid'] * 1;
$uid = $current_user['userid'];

require_once('/median-webapp/includes/user_functions.php');

$add_fav = addToUserFavs($uid, $mid);

if ($add_fav == false) {
	echo 'There was an error adding your fav, sorry. Please try again.';
} else {
	echo 'done';
}