<?php

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
    bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

require_once('/median-webapp/includes/dbconn_mongo.php');

$page_uuid = 'admin-page';
$page_title = 'Median Cluster Info'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Median Cluster Info</h2>
        <table>
        <thead>
        <tr><th>IP</th><th>Hostname</th><th>Enabled?</th><th>Type/Function</th><th>Heartbeat (from Client)</th><th>Heartbeat (from Monitor)</th></tr>
        </thead>
        <tbody>
        <?php
        $servers = $m6db->servers->find()->sort( array('hostname' => 1) );
        foreach ($servers as $server) {
            echo '<tr>';
            echo '<td>'.$server['ip'].'</td>';
            echo '<td>'.$server['hostname'].'</td>';
            echo '<td>'.(($server['e']) ? 'yes': 'nope').'</td>';
            echo '<td>'.$server['t'].'</td>';
            echo '<td>'.date('m-d-Y h:i:s A', $server['hb_c']).'</td>';
            echo '<td>'.date('m-d-Y h:i:s A', $server['hb_m']).'</td>';
            echo '</tr>';
            echo "\n";
        }
        ?>
        </tbody>
        </table>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
