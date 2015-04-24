<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH ACTIVITY LOGGING
		cyle gage, emerson college, 2012


	addNewAction($what)
	getActivityForUser($uid)
	sortByTimestamp($a, $b)

	activity tracking types:
		user
		group
		event
		cat
		newgroups
		newevents

	activity types:
		newmid -- new media IDs matching criteria like group, class, category
		newgroup -- new groups as they are created
		newevent -- new events as they are created
		newpost -- new forum posts matching criteria like group, class, category
		newcomment -- new commnets on owned mids
		user -- user's new media entries
		group -- group's new media entries


	action records:

	Array(
		'ts' => 1344610418, // timestamp!
		'uid' => 1, // who! user ID
		't' => '', // type of action...
		... // more info contextual to the action type
		// stuff like cid, eid, gid, mid, etc
	)


*/

require_once('/median-webapp/includes/dbconn_mongo.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/permission_functions.php');

function addNewAction($what = array()) {
	// add a new action record

	if (!isset($what) || !is_array($what) || count($what) == 0) {
		return false;
	}

	if (!isset($what['uid']) || !isset($what['t'])) {
		return false;
	}

	global $mdb;
	$activity_collection = $mdb->activity;

	$new_action = array();

	$new_action['ts'] = time();

	$new_action = array_merge($new_action, $what);

	// not safe, just do it, whatever
	$insert_action = $activity_collection->insert($new_action);

	return true;

}

function getActivityForUser($uid = 0, $howlong = 4, $limit = '') {
	// get recent activity for a user's "my stuff" feed
	// howlong option determines just how recent
	// limit option determines if user wants to see all activity or just one type

	if (!isset($uid) || $uid * 1 == 0) {
		return false;
	}

	$uid = (int) $uid * 1;

	$user_info = getUserInfo($uid);
	if ($user_info == false) {
		return false;
	}

	$user_subs = array();
	if (isset($user_info['s']) && is_array($user_info['s'])) {
		$user_subs = $user_info['s'];
	}

	$sincewhen = strtotime('-'.$howlong.' days');

	// everyone is subscribed to:
	//    new comments on their media entries -- newcomment
	//    new media in their classes -- newmid with class filter
	//    new forum posts -- newpost with ownership filter
	//    new entries in their groups -- newmid with group filter
	//    new entries in their categories -- newmid with category filter
	//    new entries in their events -- newmid with event filter

	global $mdb;
	$activity_collection = $mdb->activity;

	$activity = array();

	// first let's get those default things everyone is subscribed to

	$user_mids = getUserManageMedia($uid);
	$user_classes = getUserClasses($uid, true);
	$user_groups = getUserGroups($uid, true);
	$user_events = getUserEventOwnerships($uid, true);
	$user_cats = getUserCategoryOwnerships($uid, true);

	for ($i = 0; $i < count($user_cats); $i++) {
		if ($user_cats[$i] == 1) {
			unset($user_cats[$i]);
		}
	}

	if (count($user_mids) > 0) {
		$get_mid_updates = $activity_collection->find(array('ts' => array('$gte' => $sincewhen), '$or' => array(array('t' => 'newcomment'), array('t' => 'encoded')), 'mid' => array('$in' => $user_mids)));
		foreach ($get_mid_updates as $mid_update) {
			$mid_update['why'] = 'yourmedia';
			$activity[] = $mid_update;
		}
	}

	if (count($user_classes) > 0) {
		$current_semester = getCurrentSemesterCode();
		foreach ($user_classes as $class) {
			$class_query = array('c' => $class, 's' => $current_semester);
			$get_new_class_media = $activity_collection->find(array('ts' => array('$gte' => $sincewhen), '$or' => array(array('t' => 'newmid'), array('t' => 'newpost')), 'clid' => $class_query));
			foreach ($get_new_class_media as $new_class_media) {
				if (isset($new_class_media['mid']) && canViewMedia($uid, $new_class_media['mid']) != true) {
					continue;
				}
				$new_class_media['why'] = 'yourclass';
				$activity[] = $new_class_media;
			}
		}
	}

	if (count($user_groups) > 0) {
		$get_new_group_media = $activity_collection->find(array('ts' => array('$gte' => $sincewhen), '$or' => array(array('t' => 'newmid'), array('t' => 'newpost')), 'gid' => array('$in' => $user_groups)));
		foreach ($get_new_group_media as $new_group_media) {
			if (isset($new_group_media['mid']) && canViewMedia($uid, $new_group_media['mid']) != true) {
				continue;
			}
			$new_group_media['why'] = 'yourgroup';
			$activity[] = $new_group_media;
		}
		unset($get_new_group_media, $new_group_media);
	}

	if (count($user_events) > 0) {
		$get_new_event_media = $activity_collection->find(array('ts' => array('$gte' => $sincewhen), '$or' => array(array('t' => 'newmid'), array('t' => 'newpost')), 'eid' => array('$in' => $user_events)));
		foreach ($get_new_event_media as $new_event_media) {
			if (isset($new_event_media['mid']) && canViewMedia($uid, $new_event_media['mid']) != true) {
				continue;
			}
			$new_event_media['why'] = 'yourevent';
			$activity[] = $new_event_media;
		}
		unset($get_new_event_media, $new_event_media);
	}

	if (count($user_cats) > 0) {
		$get_new_cat_media = $activity_collection->find(array('ts' => array('$gte' => $sincewhen), '$or' => array(array('t' => 'newmid'), array('t' => 'newpost')), 'cid' => array('$in' => $user_cats)));
		foreach ($get_new_cat_media as $new_cat_media) {
			if (isset($new_cat_media['mid']) && canViewMedia($uid, $new_cat_media['mid']) != true) {
				continue;
			}
			$new_cat_media['why'] = 'yourcat';
			$activity[] = $new_cat_media;
		}
		unset($get_new_cat_media, $new_cat_media);
	}

	// ok now do their custom ones
	foreach ($user_subs as $subscription) {
		if (isset($subscription['t'])) {
			$sub_type = $subscription['t'];
		} else if (isset($subscription['w'])) {
			$sub_type = $subscription['w'];
		} else {
			continue;
		}
		if (isset($subscription['id'])) {
			$sub_id = $subscription['id'];
		} else if (isset($subscription['exid'])) {
			$sub_id = $subscription['exid'];
		}
		switch ($sub_type) {
			case 'user':
			// get new media entries owned by user ID provided
			$get_new_user_media = $activity_collection->find(array( 'ts' => array('$gte' => $sincewhen), 't' => 'newmid', 'uid_ow' => intval($sub_id) ));
			foreach ($get_new_user_media as $new_user_media) {
				if (canViewMedia($uid, $new_user_media['mid']) != true) {
					continue;
				}
				$new_user_media['watchfor'] = $sub_id;
				$new_user_media['why'] = 'watchuser';
				$activity[] = $new_user_media;
			}
			break;
			case 'group':
			// get new media entries owned by group ID provided
			$get_new_group_media = $activity_collection->find(array( 'ts' => array('$gte' => $sincewhen), 't' => 'newmid', 'gid' => intval($sub_id) ));
			foreach ($get_new_group_media as $new_group_media) {
				if (canViewMedia($uid, $new_group_media['mid']) != true) {
					continue;
				}
				$new_group_media['watchfor'] = $sub_id;
				$new_group_media['why'] = 'watchgroup';
				$activity[] = $new_group_media;
			}
			break;
			case 'event':
			// get new media entries owned by event ID provided
			$get_new_event_media = $activity_collection->find(array( 'ts' => array('$gte' => $sincewhen), 't' => 'newmid', 'eid' => intval($sub_id) ));
			foreach ($get_new_event_media as $new_event_media) {
				if (canViewMedia($uid, $new_event_media['mid']) != true) {
					continue;
				}
				$$new_event_media['watchfor'] = $sub_id;
				$new_event_media['why'] = 'watchevent';
				$activity[] = $new_event_media;
			}
			break;
			case 'cat':
			// get new media entries owned by cat ID provided
			$get_new_cat_media = $activity_collection->find(array( 'ts' => array('$gte' => $sincewhen), 't' => 'newmid', 'cid' => intval($sub_id) ));
			foreach ($get_new_cat_media as $new_cat_media) {
				if (canViewMedia($uid, $new_cat_media['mid']) != true) {
					continue;
				}
				$new_cat_media['watchfor'] = $sub_id;
				$new_cat_media['why'] = 'watchcat';
				$activity[] = $new_cat_media;
			}
			break;
			case 'newgroups':
			// get any new groups that have been created
			$get_new_groups = $activity_collection->find(array( 'ts' => array('$gte' => $sincewhen), 't' => 'newgroup' ));
			foreach ($get_new_groups as $new_group) {
				if (canViewGroup($uid, $new_group['gid']) != true) {
					continue;
				}
				$new_group['why'] = 'watchgroups';
				$activity[] = $new_group;
			}
			break;
			case 'newevents':
			// get any new events that have been created
			$get_new_events = $activity_collection->find(array( 'ts' => array('$gte' => $sincewhen), 't' => 'newevent' ));
			foreach ($get_new_events as $new_event) {
				if (canViewEvent($uid, $new_event['eid']) != true) {
					continue;
				}
				$new_event['why'] = 'watchevents';
				$activity[] = $new_event;
			}
			break;
		}
	}

	//print_r($activity);

	// if none, well then...
	if (count($activity) == 0) {
		return false;
	}

	// sort activity
	usort($activity, 'sortByTimestamp');

	if (count($activity) > 20) {
		$activity = array_slice($activity, 0, 20);
	}

	return $activity;

}

// helper function to sort array by 'ts' unix timestamp key
if (!function_exists('sortByTimestamp')) {
	function sortByTimestamp($a, $b) {
		$key = 'ts';
		if ($a[$key] == $b[$key]) {
			return 0;
		}
		return ($a[$key] < $b[$key]) ? 1 : -1;
	}
}
