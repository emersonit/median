<?php

// make a new clip from a video entry

require_once('/median-webapp/includes/login_check.php');

if (!isset($current_user['loggedin']) || $current_user['loggedin'] == false) {
	echo '<div class="alert-box alert">You need to be logged in to comment.</div>';
	die();
}

if (!isset($_POST['mid']) || !is_numeric($_POST['mid'])) {
	echo '<div class="alert-box alert">No Media ID provided...</div>';
	die();
}

if (!isset($_POST['intime']) || !is_numeric($_POST['intime'])) {
	echo '<div class="alert-box alert">No clip start time provided...</div>';
	die();
}

if (!isset($_POST['outtime']) || !is_numeric($_POST['outtime'])) {
	echo '<div class="alert-box alert">No clip end time provided...</div>';
	die();
}

require_once('/median-webapp/includes/permission_functions.php');

if (!canViewMedia($_POST['mid'], $current_user['userid'])) {
	echo '<div class="alert-box alert">Sorry, you cannot create a clip of an entry you cannot view.</div>';
	die();
}

require_once('/median-webapp/includes/media_functions.php');

$media_info = getMediaInfo($_POST['mid']);

// make new clip

// need mid, in time, out time, and title
$intime = (float) $_POST['intime'] * 1;
$outtime = (float) $_POST['outtime'] * 1;

if ($intime < 0) {
	echo '<div class="alert-box alert">Sorry, you cannot create a clip with a negative start time.</div>';
	die();
}

if ($outtime <= 0) {
	echo '<div class="alert-box alert">Sorry, you cannot create a clip with an end time at zero seconds.</div>';
	die();
}

if ($intime > $outtime) {
	echo '<div class="alert-box alert">Sorry, you cannot create a clip with a start time beyond the end time.</div>';
	die();
}

if ($intime > $media_info['du'] || $outtime > $media_info['du']) {
	echo '<div class="alert-box alert">Sorry, your clip is beyond the time limits of the media entry.</div>';
	die();
}

$new_clip = makeNewClip($_POST['mid'], $current_user['userid'], $intime, $outtime, $_POST['title']);

if ($new_clip == false) {
	echo '<div class="alert-box alert">There was an error making your clip, sorry. Please try again.</div>';
} else {
	echo '<div class="alert-box success">Clip created! <a href="/media/'.$new_clip.'/">Click here to view.</a></div>';
}
