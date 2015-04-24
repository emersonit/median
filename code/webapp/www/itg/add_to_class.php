<?php

/*

	ADD USER TO CLASS
		cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/group_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if ($current_user['userlevel'] != 1 && isUserGroupOwner($current_user['userid'], $itg_group_id) == false) {
	bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

require_once('/median-webapp/includes/dbconn_mongo.php');

$page_uuid = 'itg-admin-page';
$page_title = 'Add User to Class'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
	<div class="column full">
		<h2>Add User to Class</h2>
		<?php
		if (isset($_GET['added']) && trim($_GET['added']) == 'yup') {
		?>
		<div class="alert-box success">User added to class successfully! They should see this change immediately.</div>
		<?php
		}
		?>
		<form action="add_to_class_process.php" id="add-user-to-class-form" method="post">
		<div><label>Username (use the autofill to make sure the user exists in Median)</label></div>
		<div><input required="required" type="text" id="username-field" name="username" /> <input type="hidden" name="uid" value="" id="userid-field" /></div>
		<div><label>Class (with this notation: VM100-01 or CC292-0)</label></div>
		<div><input type="text" name="cc" required="required" /></div>
		<div><label>Semester</label></div>
		<div style="margin-bottom:10px;"><select name="sc">
		<?php
		$get_semesters = $m->ods_cache->semesters->find( array( 'start_date' => array( '$gt' => strtotime('-1 year') ), 'start_date' => array( '$lt' => strtotime('+2 years') ) ) );
		foreach ($get_semesters as $semester) {
			echo '<option value="'.$semester['academic_period'].'" '.(($semester['current']) ? 'selected="selected"' : '').'>'.$semester['academic_period_desc'].'</option>'."\n";
		}
		?>
		</select></div>
		<div><label>As teacher or student?</label></div>
		<div><input required="required" type="radio" name="ty" value="t" /> TEACHER</div>
		<div style="margin-bottom:10px;"><input required="required" type="radio" name="ty" value="s" /> STUDENT</div>
		<div><input type="submit" class="button medium success" value="add!" /></div>
		</form>
	</div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
