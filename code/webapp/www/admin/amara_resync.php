<?php

/*

    resync subtitles from Amara to Median

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
    bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

if (!isset($_GET['mid']) || !is_numeric($_GET['mid'])) {
	die('ERROR: No media entry ID given.');
}

$mid = (int) $_GET['mid'] * 1;

require_once('/median-webapp/includes/subtitle_functions.php');

$resync_result = sync_captions_from_amara($mid);
if ($resync_result['ok'] == true) {
    echo '<p>Synced entry #'.$mid.' captions to the Median captions database!</p>';
} else {
    echo '<p>Error saving #'.$mid.' to database: '.$resync_result['error'].'</p>';
}

//echo '<p><a href="/admin/report_captioned.php">go back to captions report</a></p>';
