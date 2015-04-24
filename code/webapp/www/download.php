<?php

/*

    DOWNLOAD AN ENTRY'S SOURCE MEDIA
        cyle gage, emerson college, 2014

*/

if (!isset($_GET['mid']) || !is_numeric($_GET['mid'])) {
	die('Sorry, no media ID was given.');
} else {
	$mid = (int) $_GET['mid'] * 1;
}

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

// load the necessary functions
require_once('/median-webapp/includes/error_functions.php');
require_once('/median-webapp/includes/common_functions.php');
require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/media_functions.php');

$media_info = getMediaInfo($mid);

if ($media_info == false) {
	// uhh media does not exist!
	bailout('Sorry, but the media entry with that ID does not exist!', $current_user['userid'], $mid);
}

if ($media_info['mt'] == 'link') {
	bailout('Sorry, but you cannot download links.', $current_user['userid'], $mid);
}

if ($media_info['mt'] == 'clip') {
	bailout('Sorry, but you cannot download clips.', $current_user['userid'], $mid);
}

$can_view_result = canViewMedia($current_user['userid'], $mid);

if ($can_view_result < 1) {
	switch ($can_view_result) {
		case -100:
		bailout('Sorry, but this entry is restricted to a higher user level.', $current_user['userid'], $mid);
		break;
		case -200:
		bailout('Sorry, but this entry is class-only.', $current_user['userid'], $mid);
		break;
		case -300:
		bailout('Sorry, but this entry is restricted to a certain group.', $current_user['userid'], $mid);
		break;
		case -400:
		bailout('Sorry, but this entry has not yet been enabled.', $current_user['userid'], $mid);
		break;
		case 0:
		default:
		bailout('Sorry, but you are not allowed to download this entry for some reason.', $current_user['userid'], $mid);
	}
}

// password check!
if (isset($media_info['pwd']) && isset($_POST['p'])) {
	// check password!
	$pwd_check = checkMediaPassword($mid, trim($_POST['p']));
	if ($pwd_check != true) {
		bailout('Sorry, the password you entered is incorrect.', $current_user['userid'], $mid);
	}
} else if (isset($media_info['pwd']) && !isset($_POST['p']) && $current_user['userlevel'] > 1) {
	// show password form
	require_once('password.php');
	die();
}

$can_user_download = canDownloadMedia($current_user['userid'], $mid);

if (!$can_user_download) {
	bailout('Sorry, but you are not allowed to download this entry.', $current_user['userid'], $mid);
}

if (!isset($media_info['pa']['in']) || trim($media_info['pa']['in']) == '') {
	bailout('Sorry, but this entry does not have a downloadable file.', $current_user['userid'], $mid);
}

updateDownloadCount($mid, $current_user['userid']);

$download_source_path = $media_info['pa']['in'];

// ok they can download it...

// create a unique download ID for this transaction
$new_unique_download_id = generateUniqueId(32);

// save the new unique download ID to the database
$new_transaction = array();
$new_transaction['code'] = $new_unique_download_id;
$new_transaction['mid'] = $mid;
$new_transaction['uid'] = $current_user['userid'];
$new_transaction['file'] = $download_source_path;
$new_transaction['tsc'] = time();

$save_transaction = $mdb->download_slots->insert($new_transaction, array('w' => 1));

// then send them off to the file API for the actual download
header('Location: /files/download/'.$mid.'/'.$new_unique_download_id.'/');
