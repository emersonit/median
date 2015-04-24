<?php

/*

    bulk add entries to a class

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
    bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

// load the media functions that'll have the addMediaToClass() function
require_once('/median-webapp/includes/media_functions.php');

if (!isset($_POST['cc']) || trim($_POST['cc']) == '') {
    die('No course code was provided, please try again!');
}

if (preg_match('/^[-_A-Za-z0-9]+$/i', trim($_POST['cc'])) == false) {
    die('Invalid course code provided, please try again.');
}

if (!isset($_POST['sc']) || trim($_POST['sc']) == '') {
    die('No semester code was provided, please try again!');
}

if (preg_match('/^[-_A-Za-z0-9]+$/i', trim($_POST['sc'])) == false) {
    die('Invalid semester code provided, please try again.');
}

if (!isset($_POST['mids']) || trim($_POST['mids']) == '') {
    die('No media IDs were provided, please try again!');
}

// first, we parse the incoming string of media IDs, figure em out
$mids = array();
$mids_string = trim($_POST['mids']);

// comma-separated list, or new lines?
if (strpos($mids_string, ',') !== false) {
    // comma-separated, neat
    $mids = explode(',', $mids_string);
} else {
    // must be line by line
    $mids_string = str_replace("\r\n", "\n", $mids_string); // get rid of windows line endings
    $mids = explode("\n", $mids_string);
}

// save the final array of mids to this
$final_mids = array();

// go through each mid and inspect if it's valid
for ($i = 0; $i < count($mids); $i++) {
    if (trim($mids[$i]) == '' || !is_numeric($mids[$i]) || $mids[$i] * 1 == 0) {
        continue;
    }
    $final_mids[] = (int) $mids[$i] * 1;
}

// get rid of duplicates
$final_mids = array_unique($final_mids);

// check if there are any remaining
if (count($final_mids) == 0) {
    die('No media IDs could be parsed from your input, please try again. Remember, either a comma-separated list, or separated by new lines.');
}

// ok cool -- now use the provided course and semester codes
$course_code = strtoupper(trim($_POST['cc']));
$semester_code = trim($_POST['sc']);

// save em one by one
foreach ($final_mids as $mid) {
    $add_to_class = addMediaToClass($mid, $current_user['userid'], $course_code, $semester_code);
    if ($add_to_class) {
        echo '<p>Added '.$mid.' to '.$course_code.' for semester '.$semester_code.'</p>';
    } else {
        echo '<p><b>ERROR</b> adding '.$mid.' to '.$course_code.' for semester '.$semester_code.'</p>';
    }
}

echo '<p><b>Should be all done! <a href="/admin/">Go back to admin index.</a></b></p>';
