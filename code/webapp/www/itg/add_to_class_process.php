<?php

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/group_functions.php');
require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if ($current_user['userlevel'] != 1 && isUserGroupOwner($current_user['userid'], $itg_group_id) == false) {
	bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

if (!isset($_POST['username']) && trim($_POST['username']) == '' && !isset($_POST['uid']) && !is_numeric($_POST['uid'])) {
	bailout('Neither a username or user ID was provided.', $current_user['userid']);
}

if (!isset($_POST['uid']) || !is_numeric($_POST['uid'])) {
	// try to figure out UID from given username
	$uid = getUserId(strtolower(trim($_POST['username'])));
	if ($uid == false) {
		bailout('Could not find a user with that username, sorry!', $current_user['userid']);
	}
} else {
	$uid = (int) $_POST['uid'] * 1;
	if ($uid <= 0) {
		bailout('Invalid user ID, sorry!', $current_user['userid']);
	}
}

if (!isset($_POST['cc']) || trim($_POST['cc']) == '') {
	bailout('No class code provided!', $current_user['userid']);
}

if (!isset($_POST['sc']) || trim($_POST['sc']) == '' || !is_numeric($_POST['sc'])) {
	bailout('No semester code provided!', $current_user['userid']);
}

$course_code = strtoupper(trim($_POST['cc']));
$semester_code = (int) $_POST['sc'] * 1;
$class_array = array( 'cc' => $course_code, 'sc' => $semester_code );

$is_teaching = false;
if (strtolower(trim($_POST['ty'])) == 't') {
	$is_teaching = true;
}

if ($is_teaching) {
	$update = array('$push' => array('o_c.teaching' => $class_array));
} else {
	$update = array('$push' => array('o_c.taking' => $class_array));
}

// ok, update user record

try {
	$do_it = $mdb->users->update( array('uid' => $uid), $update, array('w' => 1) );
} catch(MongoCursorException $e) {
	die('Error with MongoDB: '.$e."\n");
}

header('Location: add_to_class.php?added=yup');
