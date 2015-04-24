<?php

/*

	remove amara ID from media's entry

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

if (!isset($_POST['mid']) || !is_numeric($_POST['mid'])) {
	die('ERROR: No MID given, cannot save ID');
}

$mid = (int) $_POST['mid'] * 1;

require_once('/median-webapp/includes/permission_functions.php');

if (canUseAmara($current_user['userid'])) {
	$remove = $mdb->media->update( array('mid' => $mid), array('$unset' => array('amara' => '') ) );
	header('Location: /media/'.$mid.'/');
} else {
	die('ERROR: You cannot use this functionality, sorry.');
}
