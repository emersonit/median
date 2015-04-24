<?php

/*

    MEDIAN ADMIN INDEX
        cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
    bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

$page_uuid = 'admin-page';
$page_title = 'Median Admin'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Median Admin</h2>
        <ul>
        <li><a href="bulk.php">Bulk Add Entries to Class</a></li>
        <li><a href="help.php">Edit Help Pages</a></li>
        <li><a href="alerts.php">Edit Global Alerts</a></li>
        <li><a href="news.php">Edit News</a></li>
        <li><a href="farming.php">Farming Admin</a></li>
        <li><a href="/itg/">ITG Admin Tools</a></li>
        <li>Logs</li>
        <ul>
        <li><a href="cluster_info.php">Median Cluster Info</a></li>
        <li><a href="media.php">Raw New Media Entry List</a></li>
        <li><a href="bailout.php">Bailout Log</a></li>
        </ul>
        <li>Reports</li>
        <ul>
        <li><a href="report_captioned.php">Entries with Captions</a></li>
        <li><a href="report_perhour.php">Entries/Filesize Over Time</a></li>
        <li><a href="report_needs_versions.php">Retranscode old entries that don't have different versions</a></li>
        </ul>
        </ul>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
