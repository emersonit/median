<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH PERMISSIONS
		cyle gage, emerson college, 2012

	
	doesMediaRequireLogin($mid)
	canViewMedia($uid, $mid, $ul_override)
	canEditMedia($uid, $mid)
	canDownloadMedia($uid, $mid)
	canViewLive($uid, $lid)
	canViewClass($uid, $clid)
	isTeachingClass($uid, $clid)
	canViewCategory($uid, $cid, $ul_override)
	canEditCategory($uid, $cid)
	canSubmitToCategory($uid, $cid)
	canViewEvent($uid, $eid, $ul_override)
	canEditEvent($uid, $eid)
	canSubmitToEvent($uid, $eid)
	canViewGroup($uid, $gid, $ul_override)
	canEditGroup($uid, $gid)
	canViewPlaylist($uid, $plid, $ul_override)
	canEditPlaylist($uid, $plid)
	doesMediaHavePassword($mid)
	checkMediaPassword($mid, $password)
    canUseAkamai($uid)
    canUseAmara($uid)
    canBeHTML5($mid)

*/

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/dbconn_mongo.php');
require_once('/median-webapp/includes/media_functions.php'); // includes user_functions.php and meta_functions.php
require_once('/median-webapp/includes/group_functions.php');
require_once('/median-webapp/includes/live_functions.php');

function doesMediaRequireLogin($mid) {
	// does the media entry require a login to view it?
	
	if (!is_numeric($mid) || $mid * 1 == 0) {
		return false; // let them proceed, bigger problems
	}
	
	// get the metadata for the entry itself
	$media_info = getMediaInfo($mid);
	
	// check for no media info first
	if ($media_info == false) {
		return false; // let them proceed, bigger problems
	}
	
	// is the media not public?
	if ($media_info['ul'] < 6) {
		return true;
	}
	
	// is the media enabled at all?
	if (!isset($media_info['en']) || $media_info['en'] == false) {
		return true;
	}
	
	// check if the entry is class-only
	if ($media_info['co'] == true) {
		return true;
	}

	// check if the entry is restricted to a certain group
	if (isset($media_info['as']['gr'])) {
		return true;
	}
	
	return false;
	
}

function canViewMedia($uid = 0, $mid = 0, $ul_override = 0) {
	// can the user view a certain media entry
	// if $uid is 0, assume guest
	// check licensing info, ownership, privacy, etc

	/*
	permission return error codes:
		-100 = user level not allowed
		-200 = class-only and you are not in the class
		-300 = restricted to a group you're not in
	*/

	if (!is_numeric($mid) || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;
	$mid = (int) $mid * 1;

	// get the metadata for the entry itself
	$media_info = getMediaInfo($mid);

	if ($media_info == false) {
		return false;
	}

	// get user level
	if (isset($ul_override) && $ul_override > 0) {
		$user_level = $ul_override;
	} else {
		$user_level = getUserLevel($uid);
	}

	// check if the user is an admin
	if ($user_level == 1) {
		return true; // they can immediately see it, no worries
	}

	if (!isset($media_info['en']) || $media_info['en'] == false) {
		return -400;
	}

	// check if the user is an owner of the entry
	if (isset($media_info['ow']['u']) && in_array($uid, $media_info['ow']['u'])) {
		return true; // they can immediately see it, no worries
	}

	// check if the user is an owner of a group which owns the entry
	if (isset($media_info['ow']['g'])) {
		$user_groups = getUserGroupsOwnership($uid, true);
		if ($user_groups != false && is_array($user_groups)) {
			if (count($user_groups) > 0) {
				foreach ($media_info['ow']['g'] as $group_owner) {
					if (in_array($group_owner, $user_groups)) {
						return true; // they can immediately see it, no worries
					}
				}
				unset($user_groups);
			}
		}
	}

	// check if the user is the right user level
	if ($user_level > $media_info['ul']) {
		return -100;
	}

	// check if the entry is class-only
	if ($media_info['co'] == true) {
		$user_classes = getUserClasses($uid, true);
		if ($user_classes != false && is_array($user_classes) && isset($media_info['as']['cl'])) {
			$in_class = false;
			foreach($media_info['as']['cl'] as $class) {
				if (in_array($class['c'], $user_classes)) {
					$in_class = true;
				}
			}
			if (!$in_class) {
				return -200; // entry is class-only but not assigned to any classes. welp...
			}
		} else {
			return -200; // entry is class-only but not assigned to any classes. welp...
		}
	}

	// check if the entry is restricted to a certain group
	if (isset($media_info['as']['gr'])) {
		$in_group_restrict = false;
		$user_groups = getUserGroups($uid, true);
		if ($user_groups != false && is_array($user_groups)) {
			foreach ($media_info['as']['gr'] as $group_restrict) {
				if (in_array($group_restrict, $user_groups)) {
					$in_group_restrict = true;
				}
			}
			unset($user_groups);
		}
		if (!$in_group_restrict) {
			return -300;
		}
	}

	// if all else fails, they can probably see it
	return true;
}

function canEditMedia($uid = 0, $mid = 0) {
	// can the user edit a certain media entry
	// if $uid is 0, of course not
	// check to see if they are an owner of the file one way or another

	if (!is_numeric($mid) || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;
	$mid = (int) $mid * 1;

	$can_edit = false;

	// get the metadata for the entry itself
	$media_info = getMediaInfo($mid);

	// get user level
	$user_level = getUserLevel($uid);

	// check if the user is an admin
	if ($user_level == 1) {
		$can_edit = true;
	}

	// check if the user is an owner of the entry
	if (isset($media_info['ow']['u']) && in_array($uid, $media_info['ow']['u'])) {
		$can_edit = true;
	}

	// check if the user is an owner of a group which owns the entry
	if (isset($media_info['ow']['g'])) {
		$user_groups = getUserGroupsOwnership($uid, true);
		if ($user_groups != false && is_array($user_groups)) {
			foreach ($media_info['ow']['g'] as $group_owner) {
				if (in_array($group_owner, $user_groups)) {
					$can_edit = true;
				}
			}
			unset($user_groups);
		}
	}

	return $can_edit;
}

function canDownloadMedia($uid = 0, $mid = 0) {
	// can the user download the media entry?
	// check licensing info, ownership, privacy, etc
	
	global $acceptable_html5_copyright_bypasses;
	
	if (!is_numeric($mid) || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;

	// get user level
	$user_level = getUserLevel($uid);

	// check if the user is an admin
	if ($user_level == 1) {
		return true; // they can immediately see it, no worries
	}

	$mid = (int) $mid * 1;

	$can_download = false;

	// if they are in the special downloaders group, then yeah
	$user_groups = getUserGroups($uid, true);
	if ($user_groups != false && is_array($user_groups) && in_array(14, $user_groups)) {
		return true;
	}

	if (canEditMedia($uid, $mid)) {
		// if they can edit it, they can definitely download it
		$can_download = true;
	} else if (canViewMedia($uid, $mid)) {
		// if they can view it, they might be able to download it, check licensing
		$media_info = getMediaInfo($mid);
		if (preg_match('/(copyright|und|undetermined|unknown)/', $media_info['li']) == 0) {
			$can_download = true;
		} else {
			// the only time someone can download a copyrighted work and NOT be its owner is if it's certain copyright holders' material
			if (getUserLevel($uid) == 3 && isset($media_info['cp']) && isset($media_info['cp']['h'])) {
				if (in_array(strtotlower($media_info['cp']['h']), $acceptable_html5_copyright_bypasses)) {
					$can_download = true;
				}
			}
			if ($media_info['ul'] == 4 && $user_level == 4) {
				$can_download = true;
			}
			if ($media_info['mt'] == 'doc') {
				$can_download = true;
			}
		}
	}

	return $can_download;
}

// can the given user view a live stream?
function canViewLive($uid = 0, $lid = 0) {

	if (!is_numeric($lid) || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;

	// get user level
	$user_level = getUserLevel($uid);

	// check if the user is an admin
	if ($user_level == 1) {
		return true; // they can immediately see it, no worries
	}

	$lid = (int) $lid * 1;

	$can_watch = true;

	$live_info = getLiveInfo($lid);

	// check if the user is an owner of the entry
	if (isset($live_info['ow']['u']) && in_array($uid, $live_info['ow']['u'])) {
		return true; // they can immediately see it, no worries
	}

	// check if the user is an owner of a group which owns the entry
	if (isset($live_info['ow']['g'])) {
		$user_groups = getUserGroupsOwnership($uid, true);
		if (count($user_groups) > 0) {
			foreach ($live_info['ow']['g'] as $group_owner) {
				if (in_array($group_owner, $user_groups)) {
					return true; // they can immediately see it, no worries
				}
			}
			unset($user_groups);
		}
	}

	// check if the user is the right user level
	if ($user_level > $live_info['ul']) {
		return -100;
	}

	return $can_watch;

}

function canViewClass($uid = 0, $clid = '') {
	// is the user in the class?
	// that's the only way you can see a class

	if (trim($clid) == '' || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;

	// get user level
	$user_level = getUserLevel($uid);

	// check if the user is an admin
	if ($user_level == 1) {
		return true; // they can immediately see it, no worries
	}

	$clid = strtoupper(trim($clid));

	$can_view = false;

	global $mdb;

	$user_classes = getUserClasses($uid);
	if (count($user_classes) > 0) {
		$all_classes = array_merge($user_classes['taking'], $user_classes['teaching']);
		$all_class_codes = array();
		foreach ($all_classes as $class) {
			$all_class_codes[] = $class['cc'];
		}
		if (in_array($clid, $all_class_codes)) {
			$can_view = true;
		}
	}

	return $can_view;

}

// is the user teaching a given class?
function isTeachingClass($uid = 0, $clid = '') {

	if (trim($clid) == '' || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;
	$clid = strtoupper(trim($clid));

	$is_teaching = false;

	global $mdb;

	$user_classes = getUserClasses($uid);
	if (count($user_classes) > 0) {
		$class_codes = array();
		foreach ($user_classes['teaching'] as $class) {
			$class_codes[] = $class['cc'];
		}
		if (in_array($clid, $class_codes)) {
			$is_teaching = true;
		}
	}

	return $is_teaching;

}

function canViewCategory($uid = 0, $cid = 0, $ul_override = 0) {
	// can user view this category?

	if (!is_numeric($cid) || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;

	// get user level
	if (isset($ul_override) && $ul_override * 1 > 0) {
		$user_level = $ul_override;
	} else {
		$user_level = getUserLevel($uid);
	}

	// check if the user is an admin
	if ($user_level == 1) {
		return true; // they can immediately see it, no worries
	}

	$cid = (int) $cid * 1;

	if ($cid == 0) {
		return false;
	}

	$can_view = false;

	global $mdb;

	$get_stuff = $mdb->meta->findOne(array('w' => 'cat', 'id' => $cid), array('ul_v' => true));
	if ($get_stuff == null) {
		return false;
	} else {
		if ($get_stuff['ul_v'] >= getUserLevel($uid)) {
			$can_view = true;
		}
	}

	return $can_view;
}

function canEditCategory($uid = 0, $cid = 0) {

	if (!is_numeric($cid) || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;

	// get user level
	$user_level = getUserLevel($uid);

	// check if the user is an admin
	if ($user_level == 1) {
		return true; // they can immediately see it, no worries
	}

	$cid = (int) $cid * 1;

	if ($cid == 0) {
		return false;
	}

	$can_edit = false;

	global $mdb;

	$cat_info = getCategoryInfo($cid);

	// get user level
	$user_level = getUserLevel($uid);

	// check if the user is an admin
	if ($user_level == 1) {
		$can_edit = true;
	}

	// check if the user is an owner of the category
	if (isset($cat_info['u_o']) && in_array($uid, $cat_info['u_o'])) {
		$can_edit = true;
	}

	// check if the user is an owner of a group which owns the category
	if (isset($cat_info['g_o']) && count($cat_info['g_o']) > 0) {
		$user_groups = getUserGroupsOwnership($uid, true);
		foreach ($cat_info['g_o'] as $group_owner) {
			if (in_array($group_owner, $user_groups)) {
				$can_edit = true;
			}
		}
		unset($user_groups);
	}

	// check parent category
	if (isset($cat_info['pid']) && $can_edit == false) {
		$parent_cat_info = getCategoryInfo($cat_info['pid']);
		// check if the user is an owner of the category
		if (isset($parent_cat_info['u_o']) && in_array($uid, $parent_cat_info['u_o'])) {
			$can_edit = true;
		}

		// check if the user is an owner of a group which owns the category
		if (isset($parent_cat_info['g_o']) && count($parent_cat_info['g_o']) > 0) {
			$user_groups = getUserGroupsOwnership($uid, true);
			foreach ($parent_cat_info['g_o'] as $group_owner) {
				if (in_array($group_owner, $user_groups)) {
					$can_edit = true;
				}
			}
			unset($user_groups);
		}

		// ok... check parent of parent
		if (isset($parent_cat_info['pid']) && $can_edit == false) {
			$parent2_cat_info = getCategoryInfo($parent_cat_info['pid']);
			// check if the user is an owner of the category
			if (isset($parent2_cat_info['u_o']) && in_array($uid, $parent2_cat_info['u_o'])) {
				$can_edit = true;
			}

			// check if the user is an owner of a group which owns the category
			if (isset($parent2_cat_info['g_o']) && count($parent2_cat_info['g_o']) > 0) {
				$user_groups = getUserGroupsOwnership($uid, true);
				foreach ($parent2_cat_info['g_o'] as $group_owner) {
					if (in_array($group_owner, $user_groups)) {
						$can_edit = true;
					}
				}
				unset($user_groups);
			}
		}
	}

	return $can_edit;
}

function canSubmitToCategory($uid = 0, $cid = 0) {
	// can user submit media to this category?

	if (!is_numeric($cid) || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;

	// get user level
	$user_level = getUserLevel($uid);

	// check if the user is an admin
	if ($user_level == 1) {
		return true; // they can immediately see it, no worries
	}

	$cid = (int) $cid * 1;

	if ($cid == 0) {
		return false;
	}

	$can_submit = false;

	global $mdb;

	$get_stuff = $mdb->meta->find(array('w' => 'cat', 'id' => $cid), array('ul_s' => true));
	if ($get_stuff == null) {
		return false;
	} else {
		if ($get_stuff['ul_s'] >= getUserLevel($uid)) {
			$can_submit = true;
		}
	}

	return $can_submit;
}

function canViewEvent($uid = 0, $eid = 0) {
	// can user view this event?

	if (!is_numeric($eid) || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;

	// get user level
	if (isset($ul_override) && $ul_override * 1 > 0) {
		$user_level = $ul_override;
	} else {
		$user_level = getUserLevel($uid);
	}

	// check if the user is an admin
	if ($user_level == 1) {
		return true; // they can immediately see it, no worries
	}

	$eid = (int) $eid * 1;

	if ($eid == 0) {
		return false;
	}

	$can_view = false;

	global $mdb;

	$get_stuff = $mdb->meta->findOne(array('w' => 'event', 'id' => $eid), array('ul_v' => true));
	if ($get_stuff == null) {
		return false;
	} else {
		if ($get_stuff['ul_v'] >= getUserLevel($uid)) {
			$can_view = true;
		}
	}

	return $can_view;
}

function canEditEvent($uid = 0, $eid = 0) {
	if (!is_numeric($eid) || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;
	$eid = (int) $eid * 1;

	if ($eid == 0) {
		return false;
	}

	$can_edit = false;

	global $mdb;

	$event_info = getEventInfo($eid);

	// get user level
	$user_level = getUserLevel($uid);

	// check if the user is an admin
	if ($user_level == 1) {
		$can_edit = true;
	}

	// check if the user is an owner of the event
	if (isset($event_info['u_o']) && in_array($uid, $event_info['u_o'])) {
		$can_edit = true;
	}

	// check if the user is an owner of a group which owns the event
	if (isset($event_info['g_o']) && count($event_info['g_o']) > 0) {
		$user_groups = getUserGroupsOwnership($uid, true);
		foreach ($event_info['g_o'] as $group_owner) {
			if (in_array($group_owner, $user_groups)) {
				$can_edit = true;
			}
		}
		unset($user_groups);
	}

	return $can_edit;
}

function canSubmitToEvent($uid = 0, $eid = 0) {
	// can user submit media to this event?

	if (!is_numeric($eid) || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;

	// get user level
	$user_level = getUserLevel($uid);

	// check if the user is an admin
	if ($user_level == 1) {
		return true; // they can immediately see it, no worries
	}

	$eid = (int) $eid * 1;

	if ($eid == 0) {
		return false;
	}

	$can_submit = false;

	global $mdb;

	$get_stuff = $mdb->meta->findOne(array('w' => 'event', 'id' => $eid), array('ul_s' => true));
	if ($get_stuff == null) {
		return false;
	} else {
		if ($get_stuff['ul_s'] >= getUserLevel($uid)) {
			$can_submit = true;
		}
	}

	return $can_submit;
}

function canViewGroup($uid = 0, $gid = 0, $ul_override = 0) {
	// can user view this group?

	if (!is_numeric($gid) || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;

	// get user level
	if (isset($ul_override) && $ul_override > 0) {
		$user_level = $ul_override;
	} else {
		$user_level = getUserLevel($uid);
	}

	// check if the user is an admin
	if ($user_level == 1) {
		return true; // they can immediately see it, no worries
	}

	$gid = (int) $gid * 1;

	if ($gid == 0) {
		return false;
	}

	$can_view = false;

	$group_members = getGroupMembers($gid, true);

	if (in_array($uid, $group_members)) {
		return true;
	}

	global $mdb;

	$get_stuff = $mdb->groups->findOne(array('gid' => $gid), array('v' => true));
	if ($get_stuff == null) {
		return false;
	} else {
		if ($get_stuff['v'] >= getUserLevel($uid)) {
			$can_view = true;
		}
	}

	return $can_view;
}

function canEditGroup($uid = 0, $gid = 0) {
	if (!is_numeric($gid) || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;

	// get user level
	$user_level = getUserLevel($uid);

	// check if the user is an admin
	if ($user_level == 1) {
		return true; // they can immediately see it, no worries
	}

	$gid = (int) $gid * 1;

	if ($gid == 0) {
		return false;
	}

	$can_edit = false;

	global $mdb;

	$group_owners = getGroupOwners($gid, true);

	// get user level
	$user_level = getUserLevel($uid);

	// check if the user is an admin
	if ($user_level == 1) {
		$can_edit = true;
	}

	// check if the user is an owner of the group
	if (count($group_owners) > 0 && in_array($uid, $group_owners)) {
		$can_edit = true;
	}

	return $can_edit;
}

function canViewPlaylist($uid = 0, $plid = 0, $ul_override = 0) {

	if (!is_numeric($plid) || !is_numeric($uid)) {
	    return false;
	}

	if (isset($ul_override) && $ul_override > 0) {
		$ul_override = (int) $ul_override * 1;
	} else {
		$ul_override = 0;
	}

	$uid = (int) $uid * 1;
	$plid = (int) $plid * 1;

	// playlist permissions depend on what they are attributed to
	// currently either UID, GID, or CLID... so if they can view those, they can view the playlist

	$can_view = false;

	$playlist_info = getPlaylistInfo($plid);
	if ($playlist_info == false) {

		return false;

	} else {

		if (isset($playlist_info['uid']) && $playlist_info['uid'] * 1 > 0) {

			$can_view = true; // it's a user's playlist, those are all visible... for now?

		} else if (isset($playlist_info['gid']) && $playlist_info['gid'] * 1 > 0) {

			$gid = (int) $playlist_info['gid'] * 1;
			$can_view = canViewGroup($uid, $gid, $ul_override);

		} else if (isset($playlist_info['clid']) && is_array($playlist_info['clid'])) {

			$class = $playlist_info['clid']['c'];
			$semester = $playlist_info['clid']['s'];
			$this_semester = getCurrentSemesterCode();
			if ($semester * 1 == $this_semester * 1) {
				$can_view = canViewClass($uid, $class);
			}

		}

	}

	return $can_view;

}

function canEditPlaylist($uid = 0, $plid = 0) {

	if (!is_numeric($plid) || !is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;
	$plid = (int) $plid * 1;

	// playlist permissions depend on what they are attributed to
	// currently either UID, GID, or CLID... so if they can edit those, they can edit the playlist

	$can_edit = false;

	// get user level
	$user_level = getUserLevel($uid);

	// check if the user is an admin
	if ($user_level == 1) {
		$can_edit = true;
	}

	$playlist_info = getPlaylistInfo($plid);
	if ($playlist_info == false) {

		return false;

	} else {

		if (isset($playlist_info['uid']) && $playlist_info['uid'] > 0) {

			if ($uid == $playlist_info['uid']) {
				$can_edit = true;
			}

		} else if (isset($playlist_info['gid']) && $playlist_info['gid'] > 0) {

			$gid = (int) $playlist_info['gid'] * 1;
			$can_edit = canEditGroup($uid, $gid);

		} else if (isset($playlist_info['clid']) && is_array($playlist_info['clid'])) {

			$class = $playlist_info['clid']['c'];
			$semester = $playlist_info['clid']['s'];
			$this_semester = getCurrentSemesterCode();
			if ($semester * 1 == $this_semester * 1) {
				$can_edit = isTeachingClass($uid, $class);
			}

		}

	}

	return $can_edit;

}

function doesMediaHavePassword($mid = 0) {
	// does the media entry have a password !?

	if (!is_numeric($mid) || $mid * 1 == 0) {
	    return false;
	}

	$mid = (int) $mid * 1;

	global $mdb;

	// get the metadata for the entry itself
	$media_info = getMediaInfo($mid);

	if (isset($media_info['pwd'])) {
		return true;
	} else {
		return false;
	}
}

function checkMediaPassword($mid = 0, $password = '') {
	// take a plain text password as input and check it against the media entry's password

	if (!is_numeric($mid)) {
	    return false;
	}

	if (!isset($password) || trim($password) == '') {
		return false;
	}

	$mid = (int) $mid * 1;
	$password = trim($password);

	global $mdb;

	// get the metadata for the entry itself
	$media_info = getMediaInfo($mid);

	if (isset($media_info['pwd'])) {
		$salty = $media_info['pwd']['s'];
		$attempt = hash('sha256', $salty.$password);
		if ($attempt == $media_info['pwd']['h']) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}

}

function canUseAkamai($uid = 0) {
	// can the user use the akamai functionality in median?

	if (!is_numeric($uid)) {
	    return false;
	}

	global $akamai_group_id;

	$uid = (int) $uid * 1;

	// get user level
	$user_level = getUserLevel($uid);

	// check if the user is an admin
	if ($user_level == 1) {
		return true; // they can immediately see it, no worries
	}

	global $mdb;

	$akamai_group_check = $mdb->groups->findOne(array('gid' => $akamai_group_id, 'm' => $uid));

	if (!isset($akamai_group_check)) {
		return false;
	} else {
		return true;
	}
}

function canUseAmara($uid = 0) {
	// can the user use the amara subtitling functionality in median?

	if (!is_numeric($uid)) {
	    return false;
	}
	
	global $amara_group_id;

	$uid = (int) $uid * 1;

	// get user level
	$user_level = getUserLevel($uid);

	// check if the user is an admin
	if ($user_level == 1) {
		return true; // they can immediately see it, no worries
	}

	global $mdb;

	$akamai_group_check = $mdb->groups->findOne(array('gid' => $amara_group_id, 'm' => $uid));

	if (!isset($akamai_group_check)) {
		return false;
	} else {
		return true;
	}
}

// can the median entry be viewed over HTML5 means?
// this is a security concern for any entry that's copyrighted
function canBeHTML5($mid) {
	if (!is_numeric($mid) || $mid * 1 == 0) {
	    return false;
	}

	global $acceptable_html5_copyright_bypasses;
	
	$mid = (int) $mid * 1;

	// get the metadata for the entry itself
	$media_info = getMediaInfo($mid);

	if ($media_info == false) {
		return false;
	}

	// if it's not copyrighted or unknown, then it's probably safe!
	if (preg_match('/(copyright|und|unknown)/i', $media_info['li']) == 0) {
		return true;
	} else {
		// if the entry is copyrighted to an acceptable holder, it can be viewed via HTML5 -- this is an intentional security hole
		// see the config.php file to customize this
		if (isset($media_info['me']) && isset($media_info['me']['copyright_holder']) && in_array(strtolower(trim($media_info['me']['copyright_holder'])), $acceptable_html5_copyright_bypasses)) {
			return true;
		} else {
			return false;
		}
	}

}
