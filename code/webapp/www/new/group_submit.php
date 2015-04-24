<?php

/*

	new group submit script!

*/

//echo '<pre>'.print_r($_POST, true).'</pre>';

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

if (!isset($_POST['n']) || trim($_POST['n']) == '') {
	die('Sorry, it looks like you forgot to add a name for the group.');
}

if (!isset($_POST['ul']) || !is_numeric($_POST['ul'])) {
	die('Sorry, it looks like you forgot to set the user level visibility.');
}

if (!isset($_POST['m']) || !is_array($_POST['m'])) {
	die('Sorry, it looks like you forgot to add any members.');
}

if (!isset($_POST['o']) || !is_array($_POST['o'])) {
	die('Sorry, it looks like you forgot to add any owners.');
}

require_once('/median-webapp/includes/error_functions.php');
require_once('/median-webapp/includes/group_functions.php');

$new_group = array();
$new_group['n'] = trim($_POST['n']);
$new_group['v'] = (int) $_POST['ul'] * 1;
$new_group['d'] = strip_tags(trim($_POST['d']));
$new_group['sd'] = strip_tags(trim($_POST['sd']));
$members = array();
foreach ($_POST['m'] as $member_id) {
	if (is_numeric($member_id) && $member_id * 1 > 0) {
		$members[] = (int) $member_id * 1;
	}
}
$new_group['m'] = $members;
$owners = array();
foreach ($_POST['o'] as $owner_id) {
	if (is_numeric($owner_id) && $owner_id * 1 > 0) {
		$owners[] = (int) $owner_id * 1;
	}
}
$new_group['o'] = $owners;

$new_gid = generateNewGroupId();
if ($new_gid == false) {
	bailout('There was an error generating your group, sorry. Please try again.', $current_user['userid']);
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
        $thumb_out_url = '/thumbs/art/group_'.$new_gid.'_'.$unique_hash.'.jpg';
        $thumb_out_path = 'art/group_'.$new_gid.'_'.$unique_hash.'.jpg'; // the file base path will be added by a function later
        // ok send the file and the $thumb_out_path to the file API
        $send_image_result = sendImageToFileAPI($thumb_path, $thumb_out_path, 'art');
        if ($send_image_result == true) {
            $new_group['a'] = array();
            $new_group['a']['u'] = $thumb_out_url;
            $new_group['a']['p'] = $thumb_out_path;
        } else {
            // fail silently ... ?
            //die('there was a problem trying to save the image!');
        }
    }
}

// that's it
//echo '<pre>'.print_r($new_group, true).'</pre>';

try {
	$result = $mdb->groups->update(array('gid' => $new_gid), array('$set' => $new_group), array('w' => 1));
} catch(MongoCursorException $e) {
	bailout('There was an error adding your group, sorry. Please try again.', $current_user['userid']);
}

require_once('/median-webapp/includes/activity_functions.php');

$new_action = array('uid' => $current_user['userid'], 't' => 'newgroup', 'gid' => $new_gid);
addNewAction($new_action);

header('Location: /group/'.$new_gid.'/');
