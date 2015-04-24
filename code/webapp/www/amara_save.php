<?php

/*

	save amara ID to media's entry

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

if (!isset($_POST['mid']) || !is_numeric($_POST['mid'])) {
	die('ERROR: No MID given, cannot save ID');
}

if (!isset($_POST['aid']) || trim($_POST['aid']) == '') {
	die('ERROR: No Amara ID given, cannot save');
}

$mid = (int) $_POST['mid'] * 1;
$amara_id = trim($_POST['aid']);

require_once('/median-webapp/includes/permission_functions.php');

if (canUseAmara($current_user['userid'])) {
	$save = $mdb->media->update( array('mid' => $mid), array('$set' => array('amara' => $amara_id) ) );
	header('Location: /media/'.$mid.'/');
} else {
	die('ERROR: You cannot use this functionality, sorry.');
}
