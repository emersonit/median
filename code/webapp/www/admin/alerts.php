<?php

/*

    EDIT ALERTS
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
$page_title = 'Edit Alerts'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Edit Global Alerts</h2>
		<div id="alerts">
		<?php
		$alerts = $mdb->alerts->find()->sort( array('tsc' => -1) );
		foreach ($alerts as $alert) {
			echo '<div class="alert-box '.((isset($alert['a']) && $alert['a'] == true) ? 'alert': 'secondary').'">';
			echo '<span class="label secondary">'.date('m-d-Y h:i A', $alert['tsc']).'</span> '.$alert['c'].' <a href="alerts_process.php?t=d&id='.$alert['_id'].'" class="button small alert">delete</a>';
			echo '</div>'."\n";
		}
		?>
		</div>
		<form action="alerts_process.php?t=a" method="post">
		<fieldset>
		<legend>Add Alert</legend>
		<input required="required" type="text" name="c" placeholder="alert blurb" />
		<p>Red box? <input type="checkbox" value="1" name="a" /></p>
		<input class="button medium" type="submit" value="add!" />
		</fieldset>
		</form>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
