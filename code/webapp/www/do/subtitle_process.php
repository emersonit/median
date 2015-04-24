<?php

/*

    accept incoming SRT or VTT file, check it, and save it to the captions collection

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/error_functions.php');

if (!isset($_POST['mid']) || !is_numeric($_POST['mid'])) {
    bailout('No valid median ID provided.', $current_user['userid']);
}

$mid = (int) $_POST['mid'] * 1;
$uid = $current_user['userid'];

require_once('/median-webapp/includes/media_functions.php');
require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/subtitle_functions.php');

// does the current user have permission to edit the entry?
$media_info = getMediaInfo($mid);

if ($media_info == false) {
	// uhh media does not exist!
	bailout('Sorry, but the media entry with that ID does not exist!', $current_user['userid'], $mid);
}

// permission checks
$can_user_edit = canEditMedia($current_user['userid'], $mid);

if (!$can_user_edit) {
	bailout('Sorry, but you do not have permission to edit this media entry, so you cannot add subtitles.', $current_user['userid'], $mid);
}

// ok now move on to the file

if (!isset($_FILES['subtitle-file'])) {
    die('No subtitle file was provided, please try again.');
}

if ($_FILES['subtitle-file']['error'] == 4) {
    die('No subtitle file was provided, please try again.');
}

if ($_FILES['subtitle-file']['error'] == 1 || $_FILES['subtitle-file']['error'] == 2 || $_FILES['subtitle-file']['error'] == 3) {
    die('The file you uploaded was either too large or did not finish uploading, please try again.');
}

if ($_FILES['subtitle-file']['error'] == 6 || $_FILES['subtitle-file']['error'] == 7 || $_FILES['subtitle-file']['error'] == 8) {
    die('There was an error uploading your file, please try again. If this problem persists, please fill out a support request.');
}

// handle incoming php file
/*
	[name] => celery.gif
    [type] => image/gif
    [tmp_name] => /tmp/phptwtsMN
    [error] => 0
    [size] => 543317
*/

$accepted_extensions = array('.vtt', '.srt');
$file_extension = strtolower(strrchr($_FILES['subtitle-file']['name'], "."));

if (!in_array($file_extension, $accepted_extensions)) {
    $delete_file = unlink($_FILES['subtitle-file']['tmp_name']);
    bailout('Sorry, the subtitle file you upload must end with .srt or .vtt.', $current_user['userid'], $mid);
}

// read incoming file
$subtitles_string = file_get_contents($_FILES['subtitle-file']['tmp_name']);

// check it
$new_subtitles_result = convert_subtitles_to_json($subtitles_string);

if ($new_subtitles_result['ok'] == false) {
    $delete_file = unlink($_FILES['subtitle-file']['tmp_name']);
    bailout('Sorry, the subtitle file you uploaded appears to be invalid. It does not parse as either SRT or WebVTT.', $current_user['userid'], $mid);
}

// save the captions
$save_subtitles_result = save_new_subtitles($mid, $new_subtitles_result['data']);

if ($save_subtitles_result['ok'] == false) {
    $delete_file = unlink($_FILES['subtitle-file']['tmp_name']);
    bailout('Sorry, there was an error saving your subtitles file to the database.', $current_user['userid'], $mid);
}

// delete the file
$delete_file = unlink($_FILES['subtitle-file']['tmp_name']);

// ok all done
echo '<p>Successfully uploaded the subtitles! <a href="/media/'.$mid.'/">Go back to the Median entry.</a></p>';
