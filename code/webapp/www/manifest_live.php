<?php

// generate a JSON manifest for a LIVE stream

/*

    deal with...

        if it's an akamai entry
        permalink

        error if there's an error

*/

header('Content-type: application/json');

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

if (!isset($_GET['lid']) || !is_numeric($_GET['lid'])) {
    echo json_encode( array('error' => 'Sorry, but no live stream ID was provided.') );
    die();
}

$stream_id = (int) $_GET['lid'] * 1;

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/config_functions.php');
require_once('/median-webapp/includes/live_functions.php');
require_once('/median-webapp/includes/permission_functions.php');

$stream_info = getLiveInfo($stream_id);
if ($stream_info == false) {
    echo json_encode( array('error' => 'Sorry, but that live stream does not exist.') );
    die();
}

$can_view_result = canViewLive($current_user['userid'], $stream_id);

if ($can_view_result < 1) {
    switch ($can_view_result) {
        case -100:
        echo json_encode( array('error' => 'Sorry, but this entry is restricted to a higher user level.') );
        break;
        case 0:
        default:
        echo json_encode( array('error' => 'Sorry, but you are not allowed to view this entry for some reason.') );
    }
}

// ok this'll hold the array to JSONify later
$v = array();
$v['type'] = 'live'; // what kind of playback is expected
$v['permalink'] = $median_base_url.'live/'.$stream_id.'/'; // permalink back to the entry on median
$v['streams'] = array(); // this'll hold the actual streams

if (isset($stream_info['a']) && $stream_info['a'] == true && isset($stream_info['a_fms']) && trim($stream_info['a_fms']) != '') {
    // ok it's actually akamai!
    $get_akamai_name = preg_match('/^(rtmp.*\/live\/)(.*)$/', $stream_info['a_fms'], $url_pieces);
    $v['server'] = $url_pieces[1];
    $stream_name = $url_pieces[2];
} else {
    // what server is this on...?
    $current_streams = getLiveListFromNginx();
    foreach ($current_streams as $stream) {
        if ($stream['stream'] == 'ms'.$stream_id) {
            $v['server'] = $stream['server'];
            break;
        }
    }
    $stream_name = 'ms'.$stream_id;
}

$v['streams'][] = array('path' => $stream_name, 'bitrate' => 400); // bitrate is unknown unfortunately

echo json_encode($v); // send out the video info as JSON

// all done
