<?php

// fix thumbnail or make a new random thumbnail!
// make sure user has permission to edit this media entry

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

if (!isset($_POST['t']) || trim($_POST['t']) == '') {
	echo '<div class="alert-box alert">Sorry, no action was provided.</div>';
	die();
}

if (!isset($_POST['mid']) || !is_numeric($_POST['mid'])) {
	echo '<div class="alert-box alert">Sorry, no media ID was provided.</div>';
	die();
}

$action = strtolower(trim($_POST['t']));
$mid = (int) $_POST['mid'] * 1;

require_once('/median-webapp/includes/media_functions.php');
require_once('/median-webapp/includes/permission_functions.php');

$can_user_edit = canEditMedia($current_user['userid'], $mid);

if ($can_user_edit != true) {
	echo '<div class="alert-box alert">Sorry, you cannot edit this media entry.</div>';
	die();
}

$media_info = getMediaInfo($mid);

if ($media_info == false) {
	echo '<div class="alert-box alert">Could not find that Media ID.</div>';
	die();
}

if (($action == 'f' || $action == 'r') && !in_array($media_info['mt'], array('video', 'image'))) {
	echo '<div class="alert-box alert">Sorry, I cannot make a new thumbnail for anything except videos or images.</div>';
	die();
}

require_once('/median-webapp/includes/file_functions.php');

if ($action == 'f') {
	// fix thumb
	$done = makeNewThumbnail($mid);
	if ($done) {
		echo '<div class="alert-box success">Done! Fixed thumbnail.</div>';
	} else {
		echo '<div class="alert-box alert">Sorry, there was an error of some kind.</div>';
	}
} else if ($action == 'r') {
	// random thumb
	$done = makeNewThumbnail($mid, true);
	if ($done) {
		echo '<div class="alert-box success">Done! New random thumbnail created.</div>';
	} else {
		echo '<div class="alert-box alert">Sorry, there was an error of some kind.</div>';
	}
} else if ($action == 'u') {
	// upload thumb
	// $_FILES['thumb-file']; (['name'], ['tmp_name'], ['size'])

	if (!isset($_FILES['thumb-file'])) {
		echo json_encode(array('result' => 'There was an error uploading the thumbnail. Please try again.'));
		die();
	}

	switch ($_FILES['thumb-file']['error']) {
		case 1:
		case 2:
		die(json_encode(array('result' => 'The selected file is too big.')));
		break;
		case 3:
		case 4:
		case 6:
		case 7:
		die(json_encode(array('result' => 'The selected file is too big.')));
		break;
	}

	if ($_FILES['thumb-file']['size'] > 204800) {
		echo json_encode(array('result' => 'The selected file is too big. It must be less than 200kb.'));
		die();
	}

	$original_filename = $_FILES['thumb-file']['name'];

	$uploaded_extension = strtolower(strrchr($original_filename, '.'));

	if ($uploaded_extension != '.jpg' && $uploaded_extension != '.jpeg') {
		echo json_encode(array('result' => 'Sorry, but this currently only supports JPG image uploads.'));
		die();
	}

	$thumb_path = $_FILES['thumb-file']['tmp_name'];

    // ok send this to the file API
    // this function will also delete the local copy so we don't have to worry about it
    $thumb_result = sendImageToFileAPI($thumb_path, $mid.'_custom_thumb.jpg', 'thumb', $mid);

	if ($thumb_result) {
		echo json_encode(array('result' => 'done'));
	} else {
		echo json_encode(array('result' => 'Sorry, but there was an error creating the thumbnails. Please try again.'));
	}

}
