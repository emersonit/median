<?php

// delete a version

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
    bailout('Sorry, you do not have permission to do this.', $current_user['userid']);
}

// make sure there's a MID
if (!isset($_GET['mid']) || !is_numeric($_GET['mid'])) {
    bailout('Sorry, no Media ID provided.', $current_user['userid']);
}

// make sure there's a bitrate to delete
if (!isset($_GET['b']) || !is_numeric($_GET['b'])) {
    bailout('Sorry, no bitrate to remove provided.', $current_user['userid']);
}

$mid = (int) $_GET['mid'] * 1;
$bitrate = (int) $_GET['b'] * 1;

require_once('/median-webapp/includes/file_functions.php');

$result = deleteVideoVersion($mid, $bitrate);

if ($result == true) {
    header('Location: /media/'.$mid.'/');
} else {
    die('There was an error deleting the version, please contact Cyle with the entry\'s Median ID.');
}