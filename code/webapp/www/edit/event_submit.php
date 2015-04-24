<?php

/*

	edit event submit script!

*/

/*


        !!! how do i handle art uploads!?


*/

//echo '<pre>'.print_r($_POST, true).'</pre>';

require_once('/median-webapp/includes/error_functions.php');

require_once('/median-webapp/includes/login_check.php');
if ($current_user['loggedin'] == false) {
	bailout('You are not logged in.', $current_user['userid']);
}
if ($current_user['userlevel'] > 5) {
	bailout('You do not have sufficient privileges to edit events.', $current_user['userid']);
}

if (!isset($_POST['eid']) || !is_numeric($_POST['eid'])) {
	bailout('No valid ID provided.', $current_user['userid']);
}

$eid = (int) $_POST['eid'] * 1;

require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/group_functions.php');

$event_info = getEventInfo($eid);

if ($event_info == false) {
	// uhh media does not exist!
	bailout('Sorry, but an event with that ID does not exist!', $current_user['userid']);
}

// permission checks
$can_user_edit = canEditMedia($current_user['userid'], $eid);

if (!$can_user_edit) {
	bailout('Sorry, but you do not have permission to edit this event.', $current_user['userid']);
}

if (!isset($_POST['n']) || trim($_POST['n']) == '') {
	die('Sorry, it looks like you forgot to add a name for the event.');
}

if (!isset($_POST['ul_v']) || !is_numeric($_POST['ul_v'])) {
	die('Sorry, it looks like you forgot to set the user level visibility.');
}

if (!isset($_POST['ul_s']) || !is_numeric($_POST['ul_s'])) {
	die('Sorry, it looks like you forgot to set the user level of who can submit to this event.');
}

if (!isset($_POST['userowner']) || !is_array($_POST['userowner'])) {
	die('Sorry, it looks like you forgot to set any user owners.');
}

if (!isset($_POST['groupowner']) || !is_array($_POST['groupowner'])) {
	die('Sorry, it looks like you forgot to set any group owners.');
}

//$unset_stuff = array();

$new_event = array();
$new_event['ti'] = trim($_POST['n']);
$new_event['ul_v'] = (int) $_POST['ul_v'] * 1;
$new_event['ul_s'] = (int) $_POST['ul_s'] * 1;
if (isset($_POST['d']) && trim($_POST['d']) != '') {
	$new_event['de'] = trim($_POST['d']);
} else {
	$new_event['de'] = '';
	//$unset_stuff['de'] = 1;
}
if (isset($_POST['sde']) && trim($_POST['sde']) != '') {
	$new_event['sde'] = trim($_POST['sde']);
} else {
	$new_event['sde'] = '';
	//$unset_stuff['sde'] = 1;
}

if (isset($_POST['url']) && trim($_POST['url']) != '') {
	$new_event['url'] = trim($_POST['url']);
} else {
	//$unset_stuff['url'] = 1;
	$new_event['url'] = '';
}
if (isset($_POST['st']) && trim($_POST['st']) != '') {
	$start_time = strtotime(trim($_POST['st']));
	if ($start_time == false) {
		die('Sorry, it looks like you put in an invalid start time.');
	} else {
		$new_event['sd'] = $start_time;
	}
} else {
	$new_event['sd'] = time();
}
if (isset($_POST['de']) && trim($_POST['de']) != '') {
	$end_time = strtotime(trim($_POST['de']));
	if ($end_time == false) {
		die('Sorry, it looks like you put in an invalid end time.');
	} else {
		$new_event['dl'] = $end_time;
	}
} else {
	$new_event['dl'] = 0;
}

$user_owners = array();
foreach ($_POST['userowner'] as $user_owner) {
	$user_id = getUserId(strtolower(trim($user_owner)));
	if ($user_id > 0) {
		$user_owners[] = $user_id;
	}
}
$new_event['u_o'] = $user_owners;

$group_owners = array();
foreach ($_POST['groupowner'] as $group_owner) {
	if ($group_owner * 1 > 0) {
		$group_owners[] = (int) $group_owner * 1;
	}
}
$new_event['g_o'] = $group_owners;

// deal with art if it's set...
if (isset($_FILES['a'])) {
	if ($_FILES['a']['error'] != 4) {
		switch ($_FILES['a']['error']) {
			case 1:
			case 2:
			die('The selected file is too big.');
			break;
			case 3:
			case 6:
			case 7:
			die('The selected file is too big.');
			break;
		}
		if ($_FILES['a']['size'] > 204800) {
			die('The selected file is too big. It must be less than 200kb.');
		}
		$original_filename = $_FILES['a']['name'];
		$uploaded_extension = strtolower(strrchr($original_filename, '.'));
		if ($uploaded_extension != '.jpg' && $uploaded_extension != '.jpeg') {
			die('Sorry, but this currently only supports JPG image uploads.');
		}
		$thumb_path = $_FILES['a']['tmp_name'];
		$unique_hash = uniqid(); // so that browsers will know it's new
		$thumb_out_url = '/thumbs/art/event_'.$eid.'_'.$unique_hash.'.jpg';
		$thumb_out_path = 'art/event_'.$eid.'_'.$unique_hash.'.jpg'; // the file base path will be added by a function later
		// ok send the file and the $thumb_out_path to the file API
		$send_image_result = sendImageToFileAPI($thumb_path, $thumb_out_path, 'art');
		if ($send_image_result == true) {
			$new_event['art_u'] = $thumb_out_url;
			$new_event['art_p'] = $thumb_out_path;
			// ok now add a file operation to delete the old art
			if (isset($event_info['art_p']) && trim($event_info['art_p']) != '') {
				$add_to_delete_queue = addDeleteFileToOperationsQueue($event_info['art_p']);
			}
		} else {
			// fail silently ... ?
			//die('there was a problem trying to save the image!');
		}
	}
}

//echo '<pre>'.print_r($new_event, true).'</pre>';

try {
	$result = $mdb->meta->update(array('w' => 'event', 'id' => $eid), array('$set' => $new_event), array('w' => 1));
} catch(MongoCursorException $e) {
	bailout('There was an error editing your event, sorry. Please try again.', $current_user['userid']);
}

/*
try {
	$result = $mdb->meta->update(array('w' => 'event', 'id' => $eid), array('$unset' => $unset_stuff), array('w' => 1));
} catch(MongoCursorException $e) {
	bailout('There was an error editing your event, sorry. Please try again.', $current_user['userid']);
}
*/

header('Location: /event/'.$eid.'/');
