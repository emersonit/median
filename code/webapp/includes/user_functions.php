<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH USERS
		cyle gage, emerson college, 2012


	generateNewUserRecord($username, $userlevel)
	getUserInfo($uid)
	getUserName($uid)
	getUserId($ecnet)
	getUserNames($uids)
	getUserLevel($uid)
	getUserMedia($uid)
	getUserManageMedia($uid)
	getUserClasses($uid, $as_just_codes)
	getUserGroups($uid, $as_just_ids)
	getUserGroupsOwnership($uid, $as_just_ids)
	getUserEventOwnerships($uid, $as_just_ids)
	getUserCategoryOwnerships($uid, $as_just_ids)
	getUserFavs($uid)
    searchUsers($what)
    addToUserFavs($uid, $mid)
    getUserAlerts($uid, $just_count)
    alwaysUseSubtitles($uid)

*/

require_once('/median-webapp/includes/dbconn_mongo.php');

// generate a new unique ID for a user, plus a blank record
function generateNewUserRecord($username, $userlevel = 6) {

	if (!isset($username) || trim($username) == '') {
		return false;
	}

	global $mdb;

	$get_latest_uid = $mdb->increments->findOne(array('w' => 'uid'));
	$uid = $get_latest_uid['id'];
	$next_highest_uid = $uid + 1;

	$new_user = array();
	$new_user['uid'] = $next_highest_uid;
	$new_user['ecnet'] = $username;
	$new_user['ul'] = $userlevel;
	$new_user['ts'] = time();
	$new_user['la'] = time();

	// insert user record
	try {
		$new_user_result = $mdb->users->insert($new_user, array('w' => 1));
		$increase_id = $mdb->increments->update(array('w' => 'uid'), array('$inc' => array('id' => 1) ), array('w' => 1) );
	} catch(MongoCursorException $e) {
		return false;
	}

	return $next_highest_uid;

}

function getUserInfo($uid) {
	// return an array with general user info

	if (!is_numeric($uid) || $uid * 1 == 0) {
		return false;
	}

	$uid = $uid * 1;

	global $mdb;

	$user_info = $mdb->users->findOne(array('uid' => $uid));
	if ($user_info == null) {
		return false;
	} else {
		return $user_info;
	}

}

function getUserName($uid) {
	if (!is_numeric($uid)) {
		return false;
	}
	$uid = (int) $uid * 1;
	global $mdb;
	$user_name = 'Guest';
	if ($uid > 0) {
		$user_info = $mdb->users->findOne(array('uid' => $uid), array('ecnet' => true));
		if (isset($user_info)) {
			$user_name = $user_info['ecnet'];
		}
	}
	return $user_name;
}

function getUserId($username) {
	if (trim($username) == '') {
		return false;
	}
	$username = strtolower(trim($username));
	global $mdb;
	$userid = 0;
	$user_info = $mdb->users->findOne(array('ecnet' => $username), array('uid' => true));
	if (isset($user_info)) {
		$userid = $user_info['uid'];
	}
	return $userid;
}

// return an array of usernames based on input user IDs
function getUserNames($uids) {
	if (!isset($uids) || !is_array($uids) || count($uids) < 1) {
		return false;
	}
	global $mdb;
	$users = array();
	$find_stuff = $mdb->users->find(array('uid' => array('$in' => $uids)), array('uid' => true, 'ecnet' => true));
	foreach ($find_stuff as $user) {
		$users[] = array('name' => $user['ecnet'], 'id' => $user['uid']);
	}
	return $users;
}

function getUserLevel($uid) {
	if (!is_numeric($uid) || $uid * 1 == 0) {
		return 6;
	}

	$uid = (int) $uid * 1;

	global $mdb;

	$user_level = 6;

	if ($uid > 0) {
		$user_info = $mdb->users->findOne(array('uid' => $uid), array('ul' => true, 'o_ul' => true));
		if (isset($user_info)) {
			if (isset($user_info['o_ul'])) {
				$user_level = $user_info['o_ul'] * 1;
			} else if (isset($user_info['ul'])) {
				$user_level = $user_info['ul'] * 1;
			}
		}
	}

	return $user_level;

}

// get media ID list of media that the user owns
// JUST the media directly owned by the user
function getUserMedia($uid) {
	global $mdb;
	
	if (!is_numeric($uid) || $uid * 1 == 0) {
		return false;
	}

	$uid = (int) $uid * 1;

	if ($uid == 0) {
		return array();
	}

	$media_ids = array();

	$get_media_ids = $mdb->media->find(array('ow.u' => $uid), array('mid' => true));
	if ($get_media_ids->count() > 0) {
		foreach ($get_media_ids as $media_id) {
			$media_ids[] = (int) $media_id['mid'] * 1;
		}
	}

	return $media_ids;
}

// get media ID list of media that the user can edit
// media in groups owned by user, or owned directly
function getUserManageMedia($uid) {

	if (!is_numeric($uid) || $uid * 1 == 0) {
		return false;
	}

	$uid = (int) $uid * 1;

	if ($uid == 0) {
		return array();
	}

	global $mdb;

	$media_ids = array();

	// now get groups the user is an owner of
	$users_group_ownerships = getUserGroupsOwnership($uid, true);

	$media_query = array('$or' => array(array('ow.u' => $uid), array('ow.g' => array('$in' => $users_group_ownerships))));

	$get_media_ids = $mdb->media->find($media_query, array('mid' => true));
	if ($get_media_ids->count() > 0) {
		foreach ($get_media_ids as $media_id) {
			$media_ids[] = (int) $media_id['mid'] * 1;
		}
	}

	return $media_ids;
}

// get full list of classes the user is enrolled in or teaches
// check mongodb cache of ODS data
// check overrides in median mongodb
function getUserClasses($uid, $as_just_codes = false) {
	global $mdb, $m, $coursesdb;

	if (!is_numeric($uid) || $uid * 1 == 0) {
		return false;
	}

	$uid = (int) $uid * 1;

	if ($uid == 0) {
		return array();
	}

	$user_classes = array();
	$user_classes['taking'] = array();
	$user_classes['teaching'] = array();
	$user_classes_as_codes = array();

	// get user ecnet based on provided UID
	$user_info = $mdb->users->findOne(array('uid' => $uid));
	if ($user_info != null) {
		$user_ecnet = $user_info['ecnet'];
	} else {
		// no user with that UID...
		return false;
	}

	// ok first get stuff from course enrollment cache
	$get_taking = $coursesdb->taking->find(array('ec' => $user_ecnet));
	if ($get_taking->count() > 0) {
		foreach ($get_taking as $course_taking) {
			$new_taking = array();
			$new_taking['cc'] = $course_taking['cc'];
			$new_taking['sc'] = $course_taking['sc'];
			$course_info = $coursesdb->courses->findOne(array('cc' => $course_taking['cc']));
			if ($course_info == null) {
				$new_taking['name'] = 'No Name';
			} else {
				$new_taking['name'] = $course_info['ct'];
			}
			$user_classes['taking'][] = $new_taking;
			$user_classes_as_codes[] = $course_taking['cc'];
		}
	}
	$get_teaching = $coursesdb->teaching->find(array('ec' => $user_ecnet));
	if ($get_teaching->count() > 0) {
		foreach ($get_teaching as $course_teaching) {
			$new_teaching = array();
			$new_teaching['cc'] = $course_teaching['cc'];
			$new_teaching['sc'] = $course_teaching['sc'];
			$course_info = $coursesdb->courses->findOne(array('cc' => $course_teaching['cc']));
			if ($course_info == null) {
				$new_teaching['name'] = 'No Name';
			} else {
				$new_teaching['name'] = $course_info['ct'];
			}
			$user_classes['teaching'][] = $new_teaching;
			$user_classes_as_codes[] = $course_teaching['cc'];
		}
	}

	unset($course_taking, $course_teaching, $course_info);

	// then get overrides from users collection
	if (isset($user_info['o_c'])) {
		if (isset($user_info['o_c']['taking']) && count($user_info['o_c']['taking']) > 0) {
			foreach ($user_info['o_c']['taking'] as $course_taking) {
				$course_info = $coursesdb->courses->findOne(array('cc' => $course_taking['cc'], 'sc' => $course_taking['sc']));
				if ($course_info != null) {
					$new_taking = array();
					$new_taking['cc'] = $course_taking['cc'];
					$new_taking['sc'] = $course_taking['sc'];
					$new_taking['name'] = $course_info['ct'];
					$user_classes['taking'][] = $new_taking;
					$user_classes_as_codes[] = $course_taking['cc'];
				}
			}
		}
		if (isset($user_info['o_c']['teaching']) && count($user_info['o_c']['teaching']) > 0) {
			foreach ($user_info['o_c']['teaching'] as $course_teaching) {
				$course_info = $coursesdb->courses->findOne(array('cc' => $course_teaching['cc'], 'sc' => $course_teaching['sc']));
				if ($course_info != null) {
					$new_teaching = array();
					$new_teaching['cc'] = $course_teaching['cc'];
					$new_teaching['sc'] = $course_teaching['sc'];
					$new_teaching['name'] = $course_info['ct'];
					$user_classes['teaching'][] = $new_teaching;
					$user_classes_as_codes[] = $course_teaching['cc'];
				}
			}
		}
	}

	$user_classes_as_codes = array_unique($user_classes_as_codes);

	if ($as_just_codes) {
		return array_values($user_classes_as_codes); // array_values() to fix potential mongodb driver bug
	} else {
		return $user_classes;
	}
}

function getUserGroups($uid, $as_just_ids = false) {
	// get full list of groups the user is a member of

	if (!is_numeric($uid) || $uid * 1 == 0) {
		return false;
	}

	$uid = (int) $uid * 1;

	if ($uid == 0) {
		return array();
	}

	global $mdb;

	$groups = array();

	$find_groups = $mdb->groups->find(array('m' => $uid), array('gid' => true, 'n' => true, 'a' => true, 'd' => true));
	if ($find_groups->count() > 0) {
		$find_groups->sort(array('n' => 1));
		foreach ($find_groups as $group) {
			if ($as_just_ids) {
				$groups[] = $group['gid'];
			} else {
				$groups[] = $group;
			}
		}
	}

	return $groups;
}

function getUserGroupsOwnership($uid, $as_just_ids = false) {
	// get full list of groups the user is an owner of

	if (!is_numeric($uid) || $uid * 1 == 0) {
		return false;
	}

	$uid = (int) $uid * 1;

	if ($uid == 0) {
		return array();
	}

	global $mdb;

	$groups = array();

	$find_groups = $mdb->groups->find(array('o' => $uid), array('gid' => true, 'n' => true, 'a' => true, 'd' => true));
	if ($find_groups->count() > 0) {
		foreach ($find_groups as $group) {
			if ($as_just_ids) {
				$groups[] = $group['gid'];
			} else {
				$groups[] = $group;
			}
		}
	}

	return $groups;
}

function getUserEventOwnerships($uid, $as_just_ids = false) {
	// get full list of events the user owns or a group owns that the user is an owner of

	if (!is_numeric($uid) || $uid * 1 == 0) {
		return false;
	}

	$uid = (int) $uid * 1;

	if ($uid == 0) {
		return array();
	}

	global $mdb;

	$events = array();

	$users_group_ownerships = getUserGroupsOwnership($uid, true);

	$events_query = array('w' => 'event', '$or' => array(array('u_o' => $uid), array('g_o' => array('$in' => $users_group_ownerships))));

	$find_events = $mdb->meta->find($events_query, array('id' => true, 'ti' => true, 'de' => true));
	if ($find_events->count() > 0) {
		foreach ($find_events as $event) {
			if ($as_just_ids) {
				$events[] = $event['id'];
			} else {
				$events[] = $event;
			}
		}
	}

	return $events;
}

function getUserCategoryOwnerships($uid, $as_just_ids = false) {
	// get full list of categories the user owns or a group owns that the user is an owner of

	if (!is_numeric($uid) || $uid * 1 == 0) {
		return false;
	}

	$uid = (int) $uid * 1;

	if ($uid == 0) {
		return array();
	}

	global $mdb;

	$cats = array();

	$users_group_ownerships = getUserGroupsOwnership($uid, true);

	$cats_query = array('w' => 'cat', '$or' => array(array('u_o' => $uid), array('g_o' => array('$in' => $users_group_ownerships))));

	$find_cats = $mdb->meta->find($cats_query, array('id' => true, 'ti' => true, 'de' => true));
	if ($find_cats->count() > 0) {
		foreach ($find_cats as $category) {
			if ($as_just_ids) {
				$cats[] = $category['id'];
			} else {
				$cats[] = $category;
			}
		}
	}

	return $cats;
}

function getUserFavs($uid) {
	// get full list of user's favorites as media IDs

	if (!is_numeric($uid) || $uid * 1 == 0) {
		return false;
	}

	$uid = (int) $uid * 1;

	if ($uid == 0) {
		return array();
	}

	global $mdb;

	$favs = array();

	$user_info = $mdb->users->findOne(array('uid' => $uid), array('f' => true));
	if ($user_info == null) {
		return false;
	} else {
		if (isset($user_info['f']) && is_array($user_info['f'])) {
			$favs = $user_info['f'];
		} else {
			return false;
		}
	}

	$favs = array_values($favs); // to fix potential mongodb driver issue

	return $favs;
}

function searchUsers($what) {
	// search for users with provided name as a regex
	if ($what == null || trim($what) == '') {
		return false;
	}

	$users = array();

	global $mdb;

	$filter = array('uid' => true, 'ecnet' => true);

	$find_users = $mdb->users->find(array('ecnet' => array('$regex' => $what, '$options' => 'i')), $filter);
	if ($find_users->count() > 0) {
		$find_users->sort(array('ecnet' => 1));
		foreach ($find_users as $user) {
			$users[] = $user;
		}
	}

	return $users;

}

// add a median entry to a user's favorites list
function addToUserFavs($uid, $mid) {

	if (!isset($uid) || !is_numeric($uid) || $uid * 1 == 0) {
		return false;
	}

	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}

	$uid = (int) $uid * 1;
	$mid = (int) $mid * 1;

	global $mdb;

	try {
		$result = $mdb->users->update(array('uid' => $uid), array('$push' => array('f' => $mid)), array('w' => 1));
	} catch(MongoCursorException $e) {
		return false;
	}

	return true;

}

function getUserAlerts($uid, $just_count = false) {
	// get alerts for this user

	/*

	types of alerts:

		- transcode errors
		- entries class-only with no class

	*/

	if (!isset($uid) || !is_numeric($uid) || $uid * 1 == 0) {
		return false;
	}

	global $m, $mdb, $farmdb;

	$alerts = array();

	$users_mids = getUserMedia($uid);
	if (count($users_mids) > 0) {

		// get transcode errors
		$get_trans_errors = $farmdb->jobs->find(array('mid' => array('$in' => $users_mids), 's' => 3));
		if ($get_trans_errors->count() > 0) {
			foreach ($get_trans_errors as $farm_job) {
				$alerts[] = array('type' => 't-error', 'mid' => $farm_job['mid'], 'ts' => $farm_job['tsc'], 'msg' => $farm_job['m']);
			}
		}

		// get class-only with no class
		$get_classonly = $mdb->media->find( array('mid' => array('$in' => $users_mids), 'co' => true) );
		if ($get_classonly->count() > 0) {
			foreach ($get_classonly as $classonly_entry) {
				if (!isset($classonly_entry['as']['cl']) || count($classonly_entry['as']['cl']) == 0) {
					$alerts[] = array('type' => 'co-error', 'mid' => $classonly_entry['mid'], 'ts' => $classonly_entry['tsc']);
				}
			}
		}

	}

	$alerts = array_map('unserialize', array_unique(array_map('serialize', $alerts)));

	if ($just_count) {
		return count($alerts);
	} else {
		usort($alerts, 'sortByTimestamp');
		return $alerts;
	}

}

// does the given user have subtitles always turned on for their account?
function alwaysUseSubtitles($uid) {
	if (!isset($uid) || !is_numeric($uid) || $uid * 1 == 0) {
		return false;
	}

	$info = getUserInfo($uid);

	if ($info == false) {
		return false;
	} else {
		if (isset($info['st']) && $info['st'] == true) {
			return true;
		} else {
			return false;
		}
	}

}

// helper function to sort by timestamp, if it doesn't exist
if (!function_exists('sortByTimestamp')) {
	function sortByTimestamp($a, $b) {
		$key = 'ts';
		if ($a[$key] == $b[$key]) {
			return 0;
		}
		return ($a[$key] < $b[$key]) ? 1 : -1;
	}
}
