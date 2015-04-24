<?php

/*

	get subtitles for given Median ID

	formerly used Amara, but now checks with Median first

*/

// allow anyone to load this resource:
header('Access-Control-Allow-Origin: *');

// don't even check login
//$login_required = false;
//require_once('includes/login_check.php');

// make sure there's a media ID to check
if (!isset($_GET['mid']) || !is_numeric($_GET['mid'])) {
	die('ERROR: No MID given, cannot look up subtitles.');
}

$mid = (int) $_GET['mid'] * 1;

require_once('/median-webapp/includes/media_functions.php');
require_once('/median-webapp/includes/subtitle_functions.php');

/*

    first check to see if captions are available in the $m6db->captions collection
    then go for amara if available

*/

$median_subtitles = getMediaSubtitles($mid);
if (count($median_subtitles) > 0) {
    echo json_encode( array('captions' => $median_subtitles) );
    die(); // quit here, we're good
}

// ok if we're still going, it means median didn't have any, so we'll check with amara

// get the amara resource ID
$amara_id = getAmaraSubtitlesID($mid);

if ($amara_id == false) {
	die('there seem to be no subtitles for that Median entry.');
}

// ok fetch the subtitles from amara and process them accordingly
$get_subtitles_result = get_subtitles_from_amara($amara_id);
if ($get_subtitles_result['ok'] == true) {
	// ok now convert to JSON
	$subtitle_convert_result = convert_subtitles_to_json($get_subtitles_result['data']);
	if ($subtitle_convert_result['ok'] == true) {
		echo json_encode( array('captions' => $subtitle_convert_result['data']) );
	} else {
		echo 'error converting subtitles: '.$subtitle_convert_result['error']."\n";
	}
} else {
	echo 'error getting subtitles: '.$get_subtitles_result['error']."\n";
}
