<?php

// embed a live stream

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/error_functions.php');
require_once('/median-webapp/includes/permission_functions.php');

if (!isset($_GET['sid']) || !is_numeric($_GET['sid'])) {
    bailout('Sorry, but no stream ID was provided.', $current_user['userid']);
}

$stream_id = (int) $_GET['sid'] * 1;
$live_info = getLiveInfo($stream_id);

if ($live_info == false) {
    // uhh live stream does not exist!
    bailout('Sorry, but a live stream with that ID does not exist!', $current_user['userid']);
}

if ($current_user['userid'] == 1) {
    echo '<!-- '.print_r($live_info, true).' -->';
}

$can_view_result = canViewLive($current_user['userid'], $stream_id);

if ($can_view_result < 1) {
    switch ($can_view_result) {
        case -100:
        bailout('Sorry, but this entry is restricted to a higher user level.', $current_user['userid']);
        break;
        case 0:
        default:
        bailout('Sorry, but you are not allowed to view this entry for some reason.', $current_user['userid']);
    }
}

$manifest = getLiveStreamManifestURL($stream_id);
$flashvars = 'src='.$manifest;

?><!doctype html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width" />
<title>Embedded Content - Median - <?php echo $median_institution_name; ?></title>
<link rel="stylesheet" href="/css/embed.css">
</head>
<body>
<div id="the-player">
    <object width="100%" height="100%">
        <param name="movie" value="/m6_video_player.swf"></param>
        <param name="flashvars" value="<?php echo $flashvars; ?>"></param>
        <param name="allowFullScreen" value="true"></param>
        <param name="allowscriptaccess" value="always"></param>
        <param name="wmode" value="transparent"></param>
        <embed
            src="/m6_video_player.swf"
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
</body>
</html>