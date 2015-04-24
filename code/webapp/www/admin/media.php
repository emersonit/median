<?php

/*

    LATEST MEDIA LIST
        cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
    bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

// get 50 newest entries...
require_once('/median-webapp/includes/media_functions.php');

$page_uuid = 'admin-page';
$page_title = 'Latest Media List'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Media - Raw List, 50 Latest</h2>
        <table>
        <?php
        $get_media = $mdb->media->find()->sort(array('mid' => -1))->limit(50);
        foreach ($get_media as $entry) {
            //echo '<pre>';
            //print_r($entry);
            //echo '</pre>';
            echo '<tr>';
            echo '<td><a href="/media/'.$entry['mid'].'/" target="_blank">'.$entry['ti'].'</a></td>';
            echo '<td>'.$entry['mt'].'</td>';
            echo '<td>'.groupLevelToString($entry['ul']).'</td>';
            echo '<td>'.getUserName($entry['uid']).'</td>';
            echo '<td>';
            if (isset($entry['pending']) && $entry['pending'] == true) {
                echo 'Pending';
            } else {
                echo ($entry['en']) ? 'Enabled' : 'Not yet enabled';
            }
            echo '</td>';
            echo '<td>'.date('m-d-Y h:i A', $entry['tsc']).'</td>';
            echo '<td>'.date('m-d-Y h:i A', $entry['tsu']).'</td>';
            echo '</tr>'."\n";
        }
        ?>
        </table>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
