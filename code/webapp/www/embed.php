<?php

/*

    MEDIAN EMBEDDED PLAYER PAGE
        cyle gage, emerson college, 2014

*/

// if ?ap is set, enable autoplay
if (isset($_GET['ap']) && trim($_GET['ap']) != 'false') {
	$autoplay = true;
} else {
	$autoplay = false;
}

// if ?cc is set, enable captions
if (isset($_GET['cc']) && trim($_GET['cc']) != 'false') {
	$force_subtitles = true;
} else {
	$force_subtitles = false;
}

require_once('/median-webapp/includes/error_functions.php');

if (!isset($_GET['mid']) || trim($_GET['mid']) == '' || !is_numeric($_GET['mid'])) {
	bailout('No Median Entry ID specified.', $current_user['userid'], null, null, false);
}

$mid = (int) $_GET['mid'] * 1;

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/media_functions.php');

$login_required = doesMediaRequireLogin($mid);

require_once('/median-webapp/includes/login_check.php');

$media_info = getMediaInfo($mid);

if ($media_info == false) {
	// uhh media does not exist!
	bailout('Sorry, but the media entry with that ID does not exist!', $current_user['userid'], $mid, null, false);
}

// can the current user view this entry?
$can_view_result = canViewMedia($current_user['userid'], $mid);

if ($can_view_result < 1) {
    switch ($can_view_result) {
        case -100:
        if ($current_user['loggedin']) {
            bailout('Sorry, but this entry is restricted to a higher user level.', $current_user['userid'], $mid, null, false);
        } else {
            bailout('Sorry, but this entry is restricted to a higher user level. Try <a href="/login.php?r='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'">logging in</a> to see this entry, you\'ll be brought back to this page after logging in.', 0, $mid, null, false);
        }
        break;
        case -200:
        if ($current_user['loggedin']) {
            bailout('Sorry, but this entry is class-only for a class you are not in.', $current_user['userid'], $mid, null, false);
        } else {
            bailout('Sorry, but this entry is class-only. Try <a href="/login.php?r='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'">logging in</a> to see this entry, you\'ll be brought back to this page after logging in.', 0, $mid, null, false);
        }
        break;
        case -300:
        if ($current_user['loggedin']) {
            bailout('Sorry, but this entry is restricted to a certain group you are not in.', $current_user['userid'], $mid, null, false);
        } else {
            bailout('Sorry, but this entry is restricted to a certain group. Try <a href="/login.php?r='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'">logging in</a> to see this entry, you\'ll be brought back to this page after logging in.', 0, $mid, null, false);
        }
        break;
        case -400:
        bailout('Sorry, but this entry has not yet been enabled. Please be patient, Median is working as fast as possible to get this entry ready!', $current_user['userid'], $mid, null, false);
        break;
        case 0:
        default:
        bailout('Sorry, but you are not allowed to view this entry for some reason.', $current_user['userid'], $mid, null, false);
    }
}

if (isset($media_info['pending']) && $media_info['pending'] == true) {
    bailout('Sorry, but this entry is pending, meaning the uploader has not actually submtited the wizard.', $current_user['userid'], $mid, null, false);
}

// password check!
if (isset($media_info['pwd']) && isset($_POST['p'])) {
    // check password!
    $pwd_check = checkMediaPassword($mid, trim($_POST['p']));
    if ($pwd_check != true) {
        bailout('Sorry, but the password you entered is incorrect.', $current_user['userid'], $mid, null, false);
    }
} else if (isset($media_info['pwd']) && !isset($_POST['p']) && $current_user['userlevel'] > 1) {
    // show password form
    require_once('/median-webapp/www/password.php');
    die();
}

// if this is on the Emerson website, Jason will send along a parentURL query string. capture it.
$embed_parent_frame_url = '';
if (isset($_GET['parentURL']) && trim($_GET['parentURL']) != '') {
	$embed_parent_frame_url = trim($_GET['parentURL']);
}

// update view count
updateViewCount($mid, $current_user['userid'], true, $embed_parent_frame_url); // third param is true for tracking embeds vs median-based viewing

// check if an iOS device is viewing this...
$is_ios = false;
if (stripos($_SERVER['HTTP_USER_AGENT'], 'ipad')) {
	$is_ios = true;
	$is_ipad = true;
	$is_iphone = false;
} else if (stripos($_SERVER['HTTP_USER_AGENT'], 'iphone')) {
	$is_ios = true;
	$is_iphone = true;
	$is_ipad = false;
}

$can_be_html5 = canBeHTML5($mid);
if ($media_info['mt'] == 'clip') {
	$can_be_html5 = false;
}
?><!doctype html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width" />
<title>Embedded Content - Median - <?php echo $median_institution_name; ?></title>
<link rel="stylesheet" href="/css/embed.css">
</head>
<body>
<?php

/*

    start player selection and display based on media type and client capability

*/

if ($media_info['mt'] == 'video' || $media_info['mt'] == 'clip') { // videos

    if ($is_ios) {
        if (!$can_be_html5) {
            // sorry, no HTML5
            ?>
            <div class="alert-box alert">Sorry, this entry cannot be played on iOS devices for security reasons. <a href="/help/medianmobile/">Read this help doc to find out why.</a></div>
            <?php
        } else {
            // ok, load up an HTML5 version of the video
			$file_location = getHTMLFIVElink($media_info['mid']);
            if ($file_location != false) {
            ?>
            <div id="the-player">
            <video width="100%" height="100%" controls="controls" preload="metadata" <?php if ($autoplay) { echo 'autoplay="autoplay"'; } ?>>
                <source src="<?php echo $file_location; ?>"  type='video/mp4; codecs="avc1.42E01E, mp4a.40.2"\' />
                <div class="alert-box alert">Your browser or device does not support HTML5 H264 video, sorry.</div>
            </video>
            </div>
            <?php
            } else {
                ?>
                <div class="alert-box alert">Sorry, I could not find a version of the video for your platform.</div>
                <?php
            }
        } // end HTML5 check
    } else {
        $manifest = getPlayerManifest($media_info['mid']);
        $flashvars = 'src='.$manifest;
        if (alwaysUseSubtitles($current_user['userid']) || $force_subtitles) {
            $flashvars .= '&cc=1';
        }
        if ($autoplay) {
            $flashvars .= '&ap=1';
        }
        ?>
        <div id="the-player">
            <object width="100%" height="100%">
                <param name="movie" value="<?php echo $median_base_url; ?>m6_video_player.swf"></param>
                <param name="flashvars" value="<?php echo $flashvars; ?>"></param>
                <param name="allowFullScreen" value="true"></param>
                <param name="allowscriptaccess" value="always"></param>
                <param name="wmode" value="transparent"></param>
                <embed
                    src="<?php echo $median_base_url; ?>m6_video_player.swf"
                    type="application/x-shockwave-flash"
                    allowscriptaccess="always"
                    allowfullscreen="true"
                    wmode="transparent"
                    width="100%"
                    height="100%"
                    flashvars="<?php echo $flashvars; ?>">
                </embed>
            </object>
        </div>
        <?php
    } // end if iOS check

} else if ($media_info['mt'] == 'audio') { // audio

    // just display an <audio> tag for ALL devices
	$file_location = getHTMLFIVElink($media_info['mid']);
    if ($file_location != false) {
		?>
        <div id="the-player">
            <audio height="30" width="100%" src="<?php echo $file_location; ?>" controls="controls" preload="metadata" <?php if ($autoplay) { echo 'autoplay="autoplay"'; } ?>><div class="alert-box alert">It seems your browser or device does not support audio, sorry.</div></audio>
        </div>
        <?php
    } else {
        ?>
        <div class="alert-box alert">Sorry, I could not find a version of the audio for your platform.</div>
        <?php
    }

} else if ($media_info['mt'] == 'image') { // image

	$file_location = getHTMLFIVElink($media_info['mid']);
	
    // oh god. for now just show the damn image. i hate flash.
    ?>
    <div id="the-player"><a href="<?php echo $file_location; ?>"><img src="<?php echo $file_location; ?>" /></a></div>
    <?php

} else if ($media_info['mt'] == 'doc') { // document
    ?>
    <div id="the-player" class="doc">
        <div class="alert-box alert">This is a link to a document. Median does not screen or filter these documents, so please be careful.</div>
        <div><a class="button success large" target="_blank" href="<?php echo $median_base_url; ?>download/<?php echo $media_info['mid']; ?>">Download Document &raquo;</a></div>
    </div>
    <?php
} else if ($media_info['mt'] == 'link') { // link
    ?>
    <div id="the-player" class="link">
        <div class="alert-box alert">This is a link to an external site. Median does not screen or filter these links, so please be careful.</div>
        <div class="alert-box secondary">Site URL: <code><?php echo $media_info['url']; ?></code></div>
        <div><a class="button success large" href="<?php echo $media_info['url']; ?>" target="_blank">Go to site &raquo;</a></div>
    </div>
    <?php

} else { // uhhhh unknown

    ?>
    <div id="the-player">Uhhhhhh...</div>
    <?php

} // end player selection and display

?>
</body>
</html>
