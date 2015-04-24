<?php

/*

    MEDIAN BAILOUT LOG
        cyle gage, emerson college, 2014

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
$page_title = 'Bailout Log'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Bailout Log - Latest 50</h2>
		<table style="width:100%;">
		<thead>
			<tr><th>Timestamp</th><th>User</th><th>MID</th><th>Error</th><th>Page</th></tr>
		</thead>
		<tbody>
		<?php
		$log = $mdb->error_log->find()->sort(array('ts' => -1))->limit(50);
		foreach ($log as $log_entry) {
			echo '<tr>';
			echo '<td>'.date('m-d-Y h:iA', $log_entry['ts']).'</td>';
			echo '<td>'.((isset($log_entry['uid']) && $log_entry['uid'] * 1 > 0) ? getUserName($log_entry['uid']) : '0').'</td>';
			echo '<td>'.((isset($log_entry['mid']) && $log_entry['mid'] * 1 > 0) ? $log_entry['mid'] : '0').'</td>';
			echo '<td>'.$log_entry['m'].((isset($log_entry['a'])) ? ' ('.$log_entry['a'].')' : '').'</td>';
			echo '<td>'.$log_entry['u'].'</td>';
			echo '</tr>'."\n";
		}
		?>
		</tbody>
		</table>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
