<?php

// add a comment to a media entry

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

if (!isset($current_user['loggedin']) || $current_user['loggedin'] == false) {
	echo '<div class="alert-box alert">You need to be logged in to comment.</div>';
	die();
}

if (!isset($_POST['mid']) || !is_numeric($_POST['mid'])) {
	echo '<div class="alert-box alert">No Media ID provided...</div>';
	die();
}

if (!isset($_POST['c']) || trim($_POST['c']) == '') {
	echo '<div class="alert-box alert">You need to provide a comment.</div>';
	die();
}

require_once('/median-webapp/includes/permission_functions.php');

if (!canViewMedia($current_user['userid'], $_POST['mid'])) {
	echo '<div class="alert-box alert">Sorry, you cannot add a comment to an entry you cannot view.</div>';
	die();
}

require_once('/median-webapp/includes/media_functions.php');

$timecode = null;

if (isset($_POST['t'])) {
	if (is_numeric($_POST['t']) && $_POST['t'] * 1 > 0) {
		$timecode = (float) $_POST['t'] * 1;
	} else if (trim($_POST['t']) != '') {
		$timecode = getSecondsFromTimeCode(trim($_POST['t']));
	}
}

$add_comment = addMediaComment(trim($_POST['c']), $_POST['mid'], $current_user['userid'], $timecode);

if ($add_comment == false) {
	echo '<div class="alert-box alert">There was an error adding your comment, sorry. Please try again.</div>';
	die();
}

echo '<div class="comment">';
echo '<p>';
echo '<span class="person radius label clickable">'.getUserName($current_user['userid']).'</span> ';
echo '<span class="date radius label">'.date('n/j/Y').'</span> ';
if (isset($timecode) && $timecode > 0) {
	echo '<span class="radius label">'.getTimeCodeFromSeconds($timecode).'</span> ';
}
echo trim($_POST['c']);
echo '</p>';
echo '</div>';