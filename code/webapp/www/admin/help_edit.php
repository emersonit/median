<?php

/*

    EDIT HELP PAGE
        cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
    bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

require_once('/median-webapp/includes/help_functions.php');

if (isset($_GET['id']) && trim($_GET['id']) != '') {
	$page_id = strtolower(trim($_GET['id']));
	$page = getHelpPage($page_id);
	$page_id = $page['_id'];
	if ($page == false) {
		bailout('Sorry, no page exists with that ID.', $current_user['userid']);
	}
} else {
	if (isset($_POST['k']) && trim($_POST['k']) != '') {
		$page = array();
		$page_id = '';
		$page['c'] = '';
		$page['k'] = strtolower(trim($_POST['k']));
		$page['ti'] = trim($_POST['t']);
	} else {
		bailout('Sorry, no ID provided.', $current_user['userid']);
	}
}

$page_uuid = 'admin-page';
$page_title = 'Edit Help Page'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
		<h2>Edit Help Page</h2>
		<form action="help_edit_submit.php" method="post">
		<input type="hidden" name="id" value="<?php echo $page_id; ?>" />
		<p><input required="required" class="four" type="text" name="k" placeholder="short name" value="<?php echo $page['k']; ?>" /></p>
		<p><input required="required" class="four" type="text" name="t" placeholder="page title" value="<?php echo $page['ti']; ?>" /></p>
		<p><textarea style="height:500px;width:100%;" required="required" name="c"><?php echo $page['c']; ?></textarea></p>
		<p><input class="button medium radius" type="submit" value="save!" /></p>
		</form>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
