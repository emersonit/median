<?php

/*

	edit playlist submit script!

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
	bailout('You do not have sufficient privileges to edit a playlist.', $current_user['userid']);
}

if (!isset($_POST['plid']) || !is_numeric($_POST['plid'])) {
	die('Sorry, no playlist ID provided to edit.');
}

$plid = (int) $_POST['plid'] * 1;

require_once('/median-webapp/includes/dbconn_mongo.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/permission_functions.php');

$playlist_info = getPlaylistInfo($plid);

if ($playlist_info == false) {
	// uhh media does not exist!
	bailout('Sorry, but a playlist with that ID does not exist!', $current_user['userid']);
}

// permission checks
$can_user_edit = canEditPlaylist($current_user['userid'], $plid);

if (!$can_user_edit) {
	bailout('Sorry, but you do not have permission to edit this playlist.', $current_user['userid']);
}

if (!isset($_POST['t']) || trim($_POST['t']) == '') {
	die('Sorry, it looks like you forgot to add a title for the playlist.');
}

$new_playlist = array();
$new_playlist['ti'] = trim($_POST['t']);
if (isset($_POST['d']) && trim($_POST['d']) != '') {
	$new_playlist['de'] = strip_tags(trim($_POST['d']));
} else {
	$new_playlist['de'] = '';
}

if (isset($_POST['sd']) && trim($_POST['sd']) != '') {
	$new_playlist['sd'] = strip_tags(trim($_POST['sd']));
} else {
	$new_playlist['sd'] = '';
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
		$thumb_out_url = '/thumbs/art/playlist_'.$plid.'_'.$unique_hash.'.jpg';
		$thumb_out_path = 'art/playlist_'.$plid.'_'.$unique_hash.'.jpg'; // the file base path will be added by a function later
		// ok send the file and the $thumb_out_path to the file API
		$send_image_result = sendImageToFileAPI($thumb_path, $thumb_out_path, 'art');
		if ($send_image_result == true) {
			$new_playlist['art_u'] = $thumb_out_url;
			$new_playlist['art_p'] = $thumb_out_path;
			// ok now add a file operation to delete the old art
			if (isset($playlist_info['art_p']) && trim($playlist_info['art_p']) != '') {
				$add_to_delete_queue = addDeleteFileToOperationsQueue($playlist_info['art_p']);
			}
		} else {
			// fail silently ... ?
			//die('there was a problem trying to save the image!');
		}
	}
}

// that's it

try {
	$result = $mdb->playlists->update(array('id' => $plid), array('$set' => $new_playlist), array('w' => 1));
} catch(MongoCursorException $e) {
	bailout('There was an error editing your playlist, sorry. Please try again.', $current_user['userid']);
}

header('Location: /playlist/'.$plid.'/');
