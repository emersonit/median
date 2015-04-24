<?php

/*

	edit category submit script!

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
	bailout('You do not have sufficient privileges to edit categories.', $current_user['userid']);
}

if (!isset($_POST['cid']) || !is_numeric($_POST['cid'])) {
	bailout('No valid ID provided.', $current_user['userid']);
}

$cid = (int) $_POST['cid'] * 1;

require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/group_functions.php');
require_once('/median-webapp/includes/file_functions.php');

$cat_info = getCategoryInfo($cid);

if ($cat_info == false) {
	bailout('Sorry, but a category with that ID does not exist!', $current_user['userid']);
}

// permission checks
$can_user_edit = canEditCategory($current_user['userid'], $cid);

if (!$can_user_edit) {
	bailout('Sorry, but you do not have permission to edit this category.', $current_user['userid']);
}

if (!isset($_POST['n']) || trim($_POST['n']) == '') {
	die('Sorry, it looks like you forgot to add a name for the category.');
}

if (!isset($_POST['ul_v']) || !is_numeric($_POST['ul_v'])) {
	die('Sorry, it looks like you forgot to set the user level visibility.');
}

if (!isset($_POST['ul_s']) || !is_numeric($_POST['ul_s'])) {
	die('Sorry, it looks like you forgot to set the user level of who can submit to this category.');
}

if (!isset($_POST['userowner']) || !is_array($_POST['userowner'])) {
	die('Sorry, it looks like you forgot to set any user owners.');
}

if (!isset($_POST['groupowner']) || !is_array($_POST['groupowner'])) {
	die('Sorry, it looks like you forgot to set any group owners.');
}


$edit_cat = array();
$edit_cat['ti'] = trim($_POST['n']); // category name
$edit_cat['ul_v'] = (int) $_POST['ul_v'] * 1; // visibility
$edit_cat['ul_s'] = (int) $_POST['ul_s'] * 1; // who can submit

// user owners
$user_owners = array();
foreach ($_POST['userowner'] as $user_owner) {
	$user_id = getUserId(strtolower(trim($user_owner)));
	if ($user_id > 0) {
		$user_owners[] = $user_id;
	}
}
$edit_cat['u_o'] = $user_owners;

// group owners
$group_owners = array();
foreach ($_POST['groupowner'] as $group_owner) {
	if ($group_owner * 1 > 0) {
		$group_owners[] = (int) $group_owner * 1;
	}
}
$edit_cat['g_o'] = $group_owners;

// description
if (isset($_POST['d']) && trim($_POST['d']) != '') {
	$edit_cat['de'] = strip_tags(trim($_POST['d']));
} else {
	$edit_cat['de'] = '';
}

// short description
if (isset($_POST['sd']) && trim($_POST['sd']) != '') {
	$edit_cat['sd'] = strip_tags(trim($_POST['sd']));
} else {
	$edit_cat['sd'] = '';
}

// author
if (isset($_POST['at']) && trim($_POST['at']) != '') {
	$edit_cat['at'] = strip_tags(trim($_POST['at']));
} else {
	$edit_cat['at'] = '';
}

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
		$thumb_out_url = '/thumbs/art/cat_'.$cid.'_'.$unique_hash.'.jpg';
		$thumb_out_path = 'art/cat_'.$cid.'_'.$unique_hash.'.jpg'; // the file base path will be added by a function later
		// ok send the file and the $thumb_out_path to the file API
		$send_image_result = sendImageToFileAPI($thumb_path, $thumb_out_path, 'art');
		if ($send_image_result == true) {
			$edit_cat['art_u'] = $thumb_out_url;
			$edit_cat['art_p'] = $thumb_out_path;
			// ok now add a file operation to delete the old art
			if (isset($cat_info['art_p']) && trim($cat_info['art_p']) != '') {
				$add_to_delete_queue = addDeleteFileToOperationsQueue($cat_info['art_p']);
			}
		} else {
			// fail silently ... ?
			//die('there was a problem trying to save the image!');
		}
	}
}

//echo '<pre>'.print_r($edit_cat, true).'</pre>';

try {
	$result = $mdb->meta->update(array('w' => 'cat', 'id' => $cid), array('$set' => $edit_cat), array('w' => 1));
} catch(MongoCursorException $e) {
	bailout('There was an error editing your category, sorry. Please try again.', $current_user['userid']);
}

header('Location: /category/'.$cid.'/');
