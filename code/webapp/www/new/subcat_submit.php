<?php

/*

	new subcategory submit script!

*/

//echo '<pre>'.print_r($_POST, true).'</pre>';

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

if (!isset($_POST['pid']) || !is_numeric($_POST['pid'])) {
	bailout('No valid parent ID provided.', $current_user['userid']);
}

$pid = (int) $_POST['pid'] * 1;

require_once('/median-webapp/includes/error_functions.php');
require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/group_functions.php');

$cat_info = getCategoryInfo($pid);

if ($cat_info == false) {
	bailout('Sorry, but a parent category with that ID does not exist!', $current_user['userid']);
}

// permission checks
$can_user_edit = canEditCategory($current_user['userid'], $pid);

if (!$can_user_edit) {
	bailout('Sorry, but you do not have permission to edit the parent category.', $current_user['userid']);
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

$has_owners = false;

if (isset($_POST['userowner']) && is_array($_POST['userowner'])) {
	$has_owners = true;
}

if (isset($_POST['groupowner']) && is_array($_POST['groupowner'])) {
	$has_owners = true;
}

if (!$has_owners) {
	die('Sorry, it looks like you forgot to set any owners.');
}

$new_cid = generateNewMetaId('cat');
if ($new_cid == false) {
	bailout('There was an error adding your category, sorry. Please try again.', $current_user['userid']);
}

$new_cat = array();
$new_cat['pid'] = $pid;
$new_cat['ti'] = trim($_POST['n']);
$new_cat['ul_v'] = (int) $_POST['ul_v'] * 1;
$new_cat['ul_s'] = (int) $_POST['ul_s'] * 1;

$user_owners = array();
foreach ($_POST['userowner'] as $user_owner) {
	$user_id = getUserId(strtolower(trim($user_owner)));
	if ($user_id > 0) {
		$user_owners[] = $user_id;
	}
}
$new_cat['u_o'] = $user_owners;

$group_owners = array();
foreach ($_POST['groupowner'] as $group_owner) {
	if ($group_owner * 1 > 0) {
		$group_owners[] = (int) $group_owner * 1;
	}
}
$new_cat['g_o'] = $group_owners;

if (isset($_POST['d']) && trim($_POST['d']) != '') {
	$new_cat['de'] = strip_tags(trim($_POST['d']));
}

if (isset($_POST['sd']) && trim($_POST['sd']) != '') {
	$new_cat['sd'] = strip_tags(trim($_POST['sd']));
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
        $thumb_out_url = '/thumbs/art/cat_'.$new_cid.'_'.$unique_hash.'.jpg';
        $thumb_out_path = 'art/cat_'.$new_cid.'_'.$unique_hash.'.jpg'; // the file base path will be added by a function later
        // ok send the file and the $thumb_out_path to the file API
        $send_image_result = sendImageToFileAPI($thumb_path, $thumb_out_path, 'art');
        if ($send_image_result == true) {
            $new_cat['art_u'] = $thumb_out_url;
            $new_cat['art_p'] = $thumb_out_path;
        } else {
            // fail silently ... ?
            //die('there was a problem trying to save the image!');
        }
    }
}

//echo '<pre>'.print_r($edit_cat, true).'</pre>';

try {
	$result = $mdb->meta->update(array('w' => 'cat', 'id' => $new_cid), array('$set' => $new_cat), array('w' => 1));
} catch(MongoCursorException $e) {
	bailout('There was an error adding your category, sorry. Please try again.', $current_user['userid']);
}

header('Location: /category/'.$new_cid.'/');
