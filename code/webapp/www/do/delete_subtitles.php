<?php

/*

    delete this entry's subtitles

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/error_functions.php');

if (!isset($_GET['mid']) || !is_numeric($_GET['mid'])) {
    bailout('No valid median ID provided.', $current_user['userid']);
}

$mid = (int) $_GET['mid'] * 1;
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

// ok now actually delete
$delete_result = delete_subtitles($mid);

// success?
if ($delete_result['ok'] == false) {
    bailout('Sorry, there was an error deleting the subtitles in the database.', $current_user['userid'], $mid);
} else {
    echo '<p>Successfully removed the existing subtitles! <a href="/media/'.$mid.'/">Go back to the Median entry.</a></p>';
}