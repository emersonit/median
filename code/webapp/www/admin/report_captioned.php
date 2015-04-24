<?php

/*

    get every median entry that has captions from Amara, show a list

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
    bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

require_once('/median-webapp/includes/dbconn_mongo.php');

$page_uuid = 'admin-page';
$page_title = 'Entries With Amara Captions'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Entries With Amara Captions</h2>
        <div>
            <table>
            <tr><th>Median ID</th><th>Title (click to view)</th><th>Amara ID</th><th></th></tr>
            <?php
            $captioned_media = $mdb->media->find( array( 'amara' => array('$exists' => true) ) )->sort( array('ti' => 1) );
            foreach ($captioned_media as $media) {
                echo '<tr>';
                echo '<td>'.$media['mid'].'</td>';
                echo '<td><a href="/media/'.$media['mid'].'/">'.$media['ti'].'</a></td>';
                echo '<td>'.$media['amara'].'</td>';
                echo '<td><a href="amara_resync.php?mid='.$media['mid'].'">resync with amara</a></td>';
                echo '</tr>'."\n";
            }
            ?>
        </div>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
