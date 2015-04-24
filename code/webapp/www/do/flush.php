<?php

/*

	flush content from a category, event, playlist, whatever
	can be done by owners of the meta object

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/error_functions.php');
require_once('/median-webapp/includes/meta_functions.php');

/*
	routing start
*/

if (!isset($_GET['w']) || trim($_GET['w']) == '') {
	bailout('No route provided.', $current_user['userid']);
}

$route_name = strtolower(trim($_GET['w']));

$allowed_routes = array('category', 'group', 'event', 'class', 'playlist');

if (!in_array($route_name, $allowed_routes)) {
	bailout('Not an acceptable route.', $current_user['userid'], null, $route_name);
}

$route_id = 0;

switch ($route_name) {
	case 'category':
	case 'group':
	case 'event':
	case 'playlist':
	if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { bailout('No valid route ID provided.', $current_user['userid']); }
	$route_id = (int) $_GET['id'] * 1;
	break;
	case 'class':
	$route_id = strtoupper(trim($_GET['id']));
	break;
}

if ($route_name != 'class' && $route_id == 0) {
	bailout('No valid route ID provided.', $current_user['userid']);
}

/*
	routing end
*/




/*
	permissions check start
*/

require_once('/median-webapp/includes/permission_functions.php');

$can_edit_this = false;

switch ($route_name) {
	case 'category':
	$can_edit_this = canEditCategory($current_user['userid'], $route_id);
	break;
	case 'event':
	$can_edit_this = canEditEvent($current_user['userid'], $route_id);
	break;
	case 'group':
	$can_edit_this = canEditGroup($current_user['userid'], $route_id);
	break;
	case 'class':
	$can_edit_this = isTeachingClass($current_user['userid'], $route_id);
	break;
	case 'playlist':
	$can_edit_this = canEditPlaylist($current_user['userid'], $route_id);
	break;
}

if (!$can_edit_this) {
	bailout('Sorry, you do not have permission to edit this.', $current_user['userid'], null, $route_id);
}

/*
	permissions check end
*/


if (isset($_POST['mids']) && is_array($_POST['mids'])) {
	$use_mids = true;
	$mids = array();
	foreach ($_POST['mids'] as $selected_mid) {
		if (!is_numeric($selected_mid)) {
			continue;
		}
		$mids[] = (int) $selected_mid * 1;
	}
	$mids = array_unique($mids);
	if (count($mids) == 0) {
		bailout('Sorry, you have not selected any media IDs to remove.', $current_user['userid'], null, $route_id);
	}
} else {
	$use_mids = false;
}

/*

	ok, flush!

*/

switch ($route_name) {
	case 'category':
	try {
		if ($use_mids) {
			// remove all mention of the category from media association
			$delete_from_media = $mdb->media->update( array('mid' => array('$in' => $mids) ), array('$pull' => array('as.ca' => $route_id)), array('multiple' => true, 'w' => 1));
			// if those media had no other category, add them to Uncategorized
			$update_media = $mdb->media->update( array('mid' => array('$in' => $mids), 'as.ca' => array('$size' => 0) ), array( '$push' => array( 'as.ca' => 1 ) ), array('multiple' => true, 'w' => 1) );
			// delete activities
			$delete_activity = $mdb->activity->remove(array('cid' => $route_id, 't' => 'newmid', 'mid' => array('$in' => $mids) ), array('multiple' => true));
		} else {
			// get mids using this category to clear them all
			$get_media = $mdb->media->find(array('as.ca' => $route_id), array('mid' => true) );
			$mids = array();
			foreach ($get_media as $media_entry) {
				$mids[] = $media_entry['mid'];
			}
			unset($get_media);
			// remove all mention of the category from media association
			$delete_from_media = $mdb->media->update( array(), array('$pull' => array('as.ca' => $route_id)), array('multiple' => true, 'w' => 1));
			// if those media had no other category, add them to Uncategorized
			$update_media = $mdb->media->update( array('mid' => array('$in' => $mids), 'as.ca' => array('$size' => 0) ), array( '$push' => array( 'as.ca' => 1 ) ), array('multiple' => true, 'w' => 1) );
			// delete activities
			$delete_activity = $mdb->activity->remove(array('cid' => $route_id, 't' => 'newmid'), array('multiple' => true));
		}

	} catch(MongoCursorException $e) {
		bailout('There was an error clearing the media.', $current_user['userid']);
	}
	break;
	case 'event':
	try {
		if ($use_mids) {
			// remove all mention of the event from media association
			$delete_from_media = $mdb->media->update( array('mid' => array('$in' => $mids) ), array('$pull' => array('as.ev' => $route_id)), array('multiple' => true, 'w' => 1));
			// delete activities
			$delete_activity = $mdb->activity->remove(array('eid' => $route_id, 't' => 'newmid', 'mid' => array('$in' => $mids) ), array('multiple' => true));
		} else {
			// remove all mention of the event from media association
			$delete_from_media = $mdb->media->update( array(), array('$pull' => array('as.ev' => $route_id)), array('multiple' => true, 'w' => 1));
			// delete activities
			$delete_activity = $mdb->activity->remove(array('eid' => $route_id, 't' => 'newmid'), array('multiple' => true));
		}
	} catch(MongoCursorException $e) {
		bailout('There was an error clearing the media.', $current_user['userid']);
	}
	break;
	case 'group':
	try {
		if ($use_mids) {
			// remove all mention of the group from media ownership or restriction
			$delete_from_media = $mdb->media->update( array('mid' => array('$in' => $mids) ), array('$pull' => array('ow.g' => $route_id)), array('multiple' => true, 'w' => 1));
			// delete activities
			$delete_activity = $mdb->activity->remove(array('gid' => $route_id, 't' => 'newmid', 'mid' => array('$in' => $mids)), array('multiple' => true));
		} else {
			// remove all mention of the group from media ownership or restriction
			$delete_from_media = $mdb->media->update( array(), array('$pull' => array('ow.g' => $route_id)), array('multiple' => true, 'w' => 1));
			// delete activities
			$delete_activity = $mdb->activity->remove(array('gid' => $route_id, 't' => 'newmid'), array('multiple' => true));
		}

	} catch(MongoCursorException $e) {
		bailout('There was an error clearing the media.', $current_user['userid']);
	}
	break;
	case 'class':
	$class = array('c' => $route_id, 's' => getCurrentSemesterCode());
	try {
		if ($use_mids) {
			// remove all mention of the group from media ownership or restriction
			$delete_from_media = $mdb->media->update( array('mid' => array('$in' => $mids) ), array('$pull' => array('as.cl' => $class)), array('multiple' => true, 'w' => 1));
			// delete activities
			$delete_activity = $mdb->activity->remove(array('clid' => $class, 't' => 'newmid', 'mid' => array('$in' => $mids)), array('multiple' => true));
		} else {
			// remove all mention of the group from media ownership or restriction
			$delete_from_media = $mdb->media->update( array(), array('$pull' => array('as.cl' => $class)), array('multiple' => true, 'w' => 1));
			// delete activities
			$delete_activity = $mdb->activity->remove(array('clid' => $class, 't' => 'newmid'), array('multiple' => true));
		}
	} catch(MongoCursorException $e) {
		bailout('There was an error clearing the media.', $current_user['userid']);
	}
	break;
	case 'playlist':
	try {
		if ($use_mids) {
			// remove all mention of the playlist from media association
			$delete_from_media = $mdb->media->update( array('mid' => array('$in' => $mids) ), array('$pull' => array('as.pl' => $route_id)), array('multiple' => true, 'w' => 1));
			// delete activities
			$delete_activity = $mdb->activity->remove(array('plid' => $route_id, 't' => 'newmid', 'mid' => array('$in' => $mids) ), array('multiple' => true));
		} else {
			// remove all mention of the playlist from media association
			$delete_from_media = $mdb->media->update( array(), array('$pull' => array('as.pl' => $route_id)), array('multiple' => true, 'w' => 1));
			// delete activities
			$delete_activity = $mdb->activity->remove(array('plid' => $route_id, 't' => 'newmid'), array('multiple' => true));
		}
	} catch(MongoCursorException $e) {
		bailout('There was an error clearing the media.', $current_user['userid']);
	}
	break;
}

if ($use_mids) {
	echo 'done';
} else {
	header('Location: /'.$route_name.'/'.$route_id.'/');
}
