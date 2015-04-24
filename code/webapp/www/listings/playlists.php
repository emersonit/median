<?php

// get playlists

$login_required = false;
require_once('/median-webapp/includes/login_check.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/permission_functions.php');

$manage = false;
$can_add_playlist = false;

if (isset($_GET['manage']) && trim($_GET['manage']) == 'yes') {
	$manage = true;
}

if ($manage && $current_user['loggedin']) {
	$playlists = getPlaylists(array('uid' => $current_user['userid']));
	$can_add_playlist = true;
} else {
	if (isset($_GET['w']) && trim($_GET['w']) != '') {

		if (!isset($_GET['id']) || trim($_GET['id']) == '') {
			die('No ID given.');
		}

		$where = array();

		switch (strtolower(trim($_GET['w']))) {
			case 'group':
			$route_name = 'gid';
			$route_id = (int) $_GET['id'] * 1;
			if (canViewGroup($current_user['userid'], $route_id)) {
				if (canEditGroup($current_user['userid'], $route_id)) {
					$can_add_playlist = true;
				}
			} else {
				die('Sorry, you cannot view this group\'s playlists.');
			}
			$where['gid'] = (int) $_GET['id'] * 1;
			break;
			case 'user':
			$where['uid'] = (int) $_GET['id'] * 1;
			if ($where['uid'] == $current_user['userid']) {
				$route_name = 'uid';
				$route_id = (int) $_GET['id'] * 1;
				$can_add_playlist = true;
			}
			break;
			case 'class':
			$route_name = 'class';
			$route_id = strtoupper(trim($_GET['clid']));
			if (canViewClass($current_user['userid'], $route_id)) {
				if (isTeachingClass($current_user['userid'], $route_id)) {
					$can_add_playlist = true;
				}
			} else {
				die('Sorry, you cannot view this class\'s playlists.');
			}
			$where['clid'] = array( 'c' => strtoupper(trim($_GET['clid'])), 's' => getCurrentSemesterCode() );
			break;
			default:
			die('No valid route given.');
		}

		$playlists = getPlaylists($where);
	} else {
		die('No route given.');
	}
}

if ($can_add_playlist) {
	echo '<div class="panel">';
	echo '<p>Start a new playlist/channel/folder:</p>';
	echo '<form action="/submit/new/playlist/" method="post">';
	if ($manage) {
		echo '<input type="hidden" name="uid" value="'.$current_user['userid'].'" />';
	} else {
		echo '<input type="hidden" name="'.$route_name.'" value="'.$route_id.'" />';
	}
	echo '<input type="text" name="t" placeholder="Playlist/channel/folder name..." /> ';
	echo '<input type="submit" class="button small success" value="add!" />';
	echo '</form>';
	echo '</div>';
	echo '<h3>Your Playlists:</h3>'; // only show this if we have the panel above
}


if ($playlists == false || count($playlists) == 0) {
	echo '<div class="alert-box alert">Sorry, there are no playlists to display.</div>';
} else {

	foreach ($playlists as $playlist) {
		echo '<div class="playlist row entry clickable" data-type="playlist" data-id="'.$playlist['id'].'">';
		echo '<p class="playlist-name">'.$playlist['ti'].'</p>';
		if (isset($playlist['de'])) {
			echo '<p class="playlist-description">'.$playlist['de'].'</p>';
		}
		echo '</div>'."\n";

	}

}
