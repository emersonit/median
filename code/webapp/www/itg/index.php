<?php

/*

	INSTRUCTIONAL TECHNOLOGY TOOLS
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

$page_uuid = 'admin-page';
$page_title = 'Median ITG Tools'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
	<div class="column full">
		<h2>Median ITG Admin</h2>
		<ul>
		<li><a href="add_to_class.php">Add User to Class</a></li>
		</ul>
	</div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
