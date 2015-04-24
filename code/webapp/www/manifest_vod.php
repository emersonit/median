<?php

// generate a JSON manifest for a VOD stream

/*

    deal with...

        poster URL
        whether it's a clip or not (clip_start and clip_end if so)
        captions
        permalink

        error if there's an error

*/

header('Content-type: application/json');

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

if (!isset($_GET['mid']) || !is_numeric($_GET['mid'])) {
	echo json_encode( array('error' => 'Sorry, but no media ID was provided.') );
    die();
}

$mid = (int) $_GET['mid'] * 1;

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/media_functions.php');
require_once('/median-webapp/includes/subtitle_functions.php');

// get media info for this entry
$media_info = getMediaInfo($mid);
if ($media_info == false) {
    echo json_encode( array('error' => 'Sorry, but that media ID does not exist.') );
    die();
}

// can the current user view this entry?
$can_view_result = canViewMedia($current_user['userid'], $mid);

if ($can_view_result < 1) {
    switch ($can_view_result) {
        case -100:
        if ($current_user['loggedin']) {
            echo json_encode( array('error' => 'Sorry, but this entry is restricted to a higher user level.') );
        } else {
            echo json_encode( array('error' => 'Sorry, but this entry is restricted to a higher user level. Try <a href="/login.php?r='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'">logging in</a> to see this entry, you\'ll be brought back to this page after logging in.') );
        }
        break;
        case -200:
        if ($user_cookie['loggedin']) {
            echo json_encode( array('error' => 'Sorry, but this entry is class-only for a class you are not in.') );
        } else {
            echo json_encode( array('error' => 'Sorry, but this entry is class-only. Try <a href="/login.php?r='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'">logging in</a> to see this entry, you\'ll be brought back to this page after logging in.') );
        }
        break;
        case -300:
        if ($user_cookie['loggedin']) {
            echo json_encode( array('error' => 'Sorry, but this entry is restricted to a certain group you are not in.') );
        } else {
            echo json_encode( array('error' => 'Sorry, but this entry is restricted to a certain group. Try <a href="/login.php?r='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'">logging in</a> to see this entry, you\'ll be brought back to this page after logging in.') );
        }
        break;
        case -400:
        echo json_encode( array('error' => 'Sorry, but this entry has not yet been enabled. Please be patient, Median is working as fast as possible to get this entry ready!') );
        break;
        case 0:
        default:
        echo json_encode( array('error' => 'Sorry, but you are not allowed to view this entry for some reason.') );
    }
    die();
}

// check if entry is pending
if (isset($media_info['pending']) && $media_info['pending'] == true) {
    echo json_encode( array('error' => 'Sorry, but this entry is pending, meaning the uploader has not actually submtited the wizard.') );
    die();
}

// ok this'll hold the array to JSONify later
$v = array();
$v['server'] = $median_rtmp_base; // the server base for streaming
$v['type'] = 'vod'; // what kind of playback is expected
$v['permalink'] = $median_base_url.'media/'.$mid.'/'; // permalink back to the entry on median
$v['streams'] = array(); // this'll hold the actual streams

// if clip, get media paths another way
if ($media_info['mt'] == 'clip') {
	// get media paths for this entry
	$get_paths = getMediaPaths($media_info['clip']['src']);
	if ($get_paths == false || $get_paths['c'] == null || count($get_paths['c']) == 0) {
		echo json_encode( array('error' => 'Sorry, for some reason there is no media content available.') );
	    die();
	}
	$v['clip_start'] = (int) $media_info['clip']['in'];
	$v['clip_end'] = (int) $media_info['clip']['out'];
} else {
	// get media paths for this entry
	$get_paths = getMediaPaths($mid);
	if ($get_paths == false || $get_paths['c'] == null || count($get_paths['c']) == 0) {
		echo json_encode( array('error' => 'Sorry, for some reason there is no media content available.') );
	    die();
	}
}

// check to see if there's an akamai stream...
$akamai = $mdb->akamai->findOne(array('mid' => $mid));
if (isset($akamai)) {
	$v['server'] = $akamai_server_url;
    $v['streams'][] = array('path' => $akamai['fms_path'], 'bitrate' => $akamai['b']);
} else {
    // go through the fetched paths
    if ($media_info['mt'] == 'video' || $media_info['mt'] == 'clip') {
        for ($i = 0; $i < count($get_paths['c']); $i++) {
            //if ($path['bitrate'] < 400) {
            //	continue;
            //}
            if (!isset($get_paths['c'][$i]['e']) || $get_paths['c'][$i]['e'] == false) {
                continue;
            }
			// the mp4:welp conversion is for old legacy videos
            $v['streams'][] = array('path' => str_replace('mp4:welp', 'files/out', $get_paths['c'][$i]['f']), 'bitrate' => $get_paths['c'][$i]['b'], 'width' => $get_paths['c'][$i]['w'], 'height' => $get_paths['c'][$i]['h']);
        }
    } else if ($media_info['mt'] == 'audio') {
        if ($get_paths['c']['e'] == false) {
            echo json_encode( array('error' => 'Sorry, for some reason there is no media content available.') );
            die();
        }
		// the mp3:welp conversion is for old legacy audio files
        $v['streams'][] = array('path' => str_replace('mp3:welp', 'mp3:files/out', $get_paths['c']['f']), 'bitrate' => $get_paths['c']['b']);
    } else {
        echo json_encode( array('error' => 'Sorry, this player only supports playing back audio or video.') );
        die();
    }

}

// tack on subtitles info, if there are any
$median_subtitles = getMediaSubtitles($mid);
if (count($median_subtitles) > 0) {
	// yup, has subtitles!
	$v['captions'] = $median_base_url.'subtitles/'.$mid.'/';
}

// tack on poster info, if there is any
if (isset($media_info['th']) && isset($media_info['th']['b']) && trim($media_info['th']['b']) != '') {
	$v['poster'] = substr($median_base_url, 0, -1).str_replace('/thumbs/', '/files/thumb/', $media_info['th']['b']);
}

echo json_encode($v); // send out the video info as JSON

// all done