<?php

/*

    MEDIAN LIVE
        cyle gage, emerson college, 2014

*/

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/error_functions.php');
require_once('/median-webapp/includes/permission_functions.php');

$stream_id = 0;
$stream_arg = '';

if (isset($_GET['sid']) && is_numeric($_GET['sid'])) {
    $stream_id = (int) $_GET['sid'] * 1;
    $stream_arg = '&sid='.$stream_id;

    $live_info = getLiveInfo($stream_id);

    if ($live_info == false) {
        // uhh media does not exist!
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

}

$page_uuid = 'live-page';
$page_title = 'Median Live'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <?php
        // if there was a live stream selected, show it!
        if ($stream_id > 0) {
        ?>
        <h2>Median Live: <?php echo $live_info['ti']; ?></h2>
        <?php
        if (isset($live_info['d']) && trim($live_info['d']) != '') {
            echo '<p>'.$live_info['d'].'</p>';
        }
        $manifest = getLiveStreamManifestURL($stream_id);
        $flashvars = 'src='.$manifest;
        ?>
        <div id="the-player">
            <object width="100%" height="600">
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
                    height="600"
                    flashvars="<?php echo $flashvars; ?>">
                </embed>
            </object>
        </div>
        <?php
        // otherwise, show a list of streams and allow broadcast
        } else {
            ?>
            <h2>Median <i>Live</i></h2>
            <h3>Watch</h3>
            <?php
            $live_list = getLiveList($current_user['userid']);
            if ($live_list == false || count($live_list) == 0) {
                echo '<p>Sorry, there are no live streams to display!</p>';
            } else {
                foreach ($live_list as $live) {
                    echo '<div class="live-entry">';
                    echo '<a href="/live/'.$live['lid'].'/">'.$live['ti'].'</a> &raquo;';
                    echo '</div>'."\n";
                }
            }
            ?>
            <h3>Broadcast</h3>
            <?php
            if ($current_user['loggedin'] == false) {
            ?>
            <p>Please <a href="/login/?r=<?php echo $median_base_url; ?>live/">log in</a> if you would like to broadcast using Median Live.</p>
            <?php
            } else {
            ?>
            <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="100%" height="500" id="M4SiteBroadcaster">
            <param name="movie" value="/M6LiveBroadcaster.swf" />
            <embed src="/M6LiveBroadcaster.swf" type="application/x-shockwave-flash" width="100%" height="500"></embed>
            </object>
            <?php
            } // end login check
        } // end if stream ID check
        ?>

    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
