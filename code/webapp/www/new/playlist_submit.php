<?php

/*

	new playlist submit script!

*/

//echo '<pre>'.print_r($_POST, true).'</pre>';

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

if (!isset($_POST['t']) || trim($_POST['t']) == '') {
	die('Sorry, it looks like you forgot to add a title for the playlist.');
}

$new_playlist = array();
$new_playlist['ti'] = trim($_POST['t']);
if (isset($_POST['d']) && trim($_POST['d']) != '') {
	$new_playlist['de'] = strip_tags(trim($_POST['d']));
}

require_once('/median-webapp/includes/error_functions.php');
require_once('/median-webapp/includes/dbconn_mongo.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/permission_functions.php');

// do permission checks for these...?
if (isset($_POST['uid']) && is_numeric($_POST['uid'])) {
	$new_playlist['uid'] = (int) $_POST['uid'] * 1;
} else if (isset($_POST['gid']) && is_numeric($_POST['gid'])) {
	$new_playlist['gid'] = (int) $_POST['gid'] * 1;
} else if (isset($_POST['clid']) && trim($_POST['clid']) != '') {
	$new_playlist['clid'] = array( 'c' => strtoupper(trim($_POST['clid'])), 's' => getCurrentSemesterCode() );
} else {
	die('Sorry, you have not indicated what to attach this playlist to.');
}

$new_plid = generateNewPlaylistId();
if ($new_plid == false) {
	bailout('There was an error generating your playlist, sorry. Please try again.', $current_user['userid']);
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
        $thumb_out_url = '/thumbs/art/playlist_'.$new_plid.'_'.$unique_hash.'.jpg';
        $thumb_out_path = 'art/playlist_'.$new_plid.'_'.$unique_hash.'.jpg'; // the file base path will be added by a function later
        // ok send the file and the $thumb_out_path to the file API
        $send_image_result = sendImageToFileAPI($thumb_path, $thumb_out_path, 'art');
        if ($send_image_result == true) {
            $new_playlist['art_u'] = $thumb_out_url;
            $new_playlist['art_p'] = $thumb_out_path;
        } else {
            // fail silently ... ?
            //die('there was a problem trying to save the image!');
        }
    }
}

// that's it

try {
	$result = $mdb->playlists->update(array('id' => $new_plid), array('$set' => $new_playlist), array('w' => 1));
} catch(MongoCursorException $e) {
	bailout('There was an error adding your playlist, sorry. Please try again.', $current_user['userid']);
}

require_once('/median-webapp/includes/activity_functions.php');

$new_action = array('uid' => $current_user['userid'], 't' => 'newplaylist', 'plid' => $new_plid);
addNewAction($new_action);

header('Location: /playlist/'.$new_plid.'/');
