<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH META-ORGANIZATION
		cyle gage, emerson college, 2012


	getCurrentSemester()
	convertSemesterDescToCode($what)
	getPlaylistInfo($plid)
	getPlaylists($source)
	getAllPlaylists($uid)
	getClassInfo($clid)
	getCategories($uid, $submit)
	getSubcategories($cid)
	getCategoryInfo($cid)
	getCategoryNames($cids)
	canHaveSubcats($cid)
	getEvents($uid, $submit)
	getEventInfo($eid)
	getGroups($uid)
	getGroupNames($gids)
	getMetaDataList()
	getNews($limit)
	getGlobalAlerts()
	generateNewMetaId($type)
	generateNewPlaylistId()

*/

require_once('/median-webapp/includes/dbconn_mongo.php');
require_once('/median-webapp/includes/user_functions.php');

// return the current course semester name
function getCurrentSemester() {
	global $m, $coursesdb;
	$semesters = $coursesdb->semesters;

	$current_semester = '';

	$result = $semesters->find(array('current' => true));
	if ($result->count() == 1) {
		// ok good
		$current_semester = $result->getNext();
	} else {
		$current_semester = false;
	}

	return $current_semester;
}

// return the current course semester unique code
function getCurrentSemesterCode() {
	global $m, $coursesdb;
	$semesters = $coursesdb->semesters;

	$current_semester_code = '';

	$result = $semesters->find(array('current' => true), array('academic_period' => true));
	if ($result->count() == 1) {
		// ok good
		$current_semester = $result->getNext();
		$current_semester_code = $current_semester['academic_period'];
	} else {
		$current_semester_code = false;
	}

	return $current_semester_code;
}

// convert a friendly semester string to its unique code
function convertSemesterDescToCode($what) {

	if (trim($what) == '') {
		return false;
	}

	$what = trim($what);

	global $m, $coursesdb;
	$semesters = $coursesdb->semesters;

	$semester_code = '';

	$semester = $semesters->findOne(array('academic_period_desc' => $what));
	if (!isset($semester)) {
		return false;
	} else {
		$semester_code = $semester['academic_period'];
	}

	return $semester_code;

}

function getPlaylistInfo($plid) {

	if (!is_numeric($plid)) {
		return false;
	}

	$plid = (int) $plid * 1;

	global $mdb;

	$playlist = $mdb->playlists->findOne( array('id' => $plid), array('mids' => false) );
	if (!isset($playlist)) {
		return false;
	} else {
		return $playlist;
	}

}

// return a playlist for a given source
// playlists are always tied to some kind of source, like a user or group
function getPlaylists($source = array()) {

	if (!isset($source) || !is_array($source)) {
		return false;
	}

	// ok so can only get playlists for UID, GID, or CLID

	if (isset($source['uid']) && is_numeric($source['uid']) && $source['uid'] * 1 > 0) {
		$uid = (int) $source['uid'] * 1;
		$query = array('uid' => $uid);
	} else if (isset($source['gid']) && is_numeric($source['gid']) && $source['gid'] * 1 > 0) {
		$gid = (int) $source['gid'] * 1;
		$query = array('gid' => $gid);
	} else if (isset($source['clid']) && trim($source['clid'] != '')) {
		$query = array('clid' => array( 'c' => strtoupper(trim($source['clid'])), 's' => getCurrentSemesterCode() ));
	} else {
		return false;
	}

	global $mdb;

	$playlists = array();

	$get_playlists = $mdb->playlists->find($query)->sort(array('ti' => 1));

	if ($get_playlists->count() > 0) {
		foreach ($get_playlists as $playlist) {
			$playlists[] = $playlist;
		}
	}

	return $playlists;

}

// return all playlists for a given user
function getAllPlaylists($uid) {

	// get all playlists that this user could add things to...

	if (!is_numeric($uid) || $uid * 1 == 0) {
		return false;
	}

	$uid = (int) $uid * 1;

	global $mdb;

	$playlists = array();

	$user_groups = getUserGroupsOwnership($uid, true);
	$user_classes = getUserClasses($uid, true);
	$classes = array();
	foreach ($user_classes as $class) {
		$classes[] = array('c' => $class, 's' => getCurrentSemesterCode());
	}

	$get_playlists = $mdb->playlists->find( array( '$or' => array( array('uid' => $uid), array('gid' => array('$in' => $user_groups) ), array('clid' => array('$in' => $classes) ) ) ) );
	if ($get_playlists->count() > 0) {
		$get_playlists->sort(array('ti' => 1));
		foreach ($get_playlists as $playlist) {
			$playlists[] = $playlist;
		}
	}

	return $playlists;

}

function getClassInfo($clid) {
	// get class name, who's teaching, who's in it
	// all based on current semester

	if (!isset($clid) || trim($clid) == '') {
		return false;
	}

	$clid = strtoupper(trim($clid));

	global $m, $coursesdb;
	$courses = $coursesdb->courses;

	$this_semester_code = getCurrentSemesterCode();

	$find_course = $courses->findOne(array('sc' => $this_semester_code, 'cc' => $clid));
	if (isset($find_course)) {
		$class_info = $find_course;
	} else {
		return false;
	}

	return $class_info;
}

function getCategories($uid, $submit = false) {
	// get categories the user can see, either because it's visible or they're an owner or in a group that owns it
	// if $submit, show categories the user can submit to, either because it's submitable or they're an owner or in a group that owns it

	if (!is_numeric($uid)) {
		return false;
	}

	$uid = (int) $uid * 1;

	global $mdb;

	$categories = array();

	$show_only = array('id' => true, 'ti' => true, 'de' => true, 'pid' => true);

	if ($submit) {
		$find_stuff_query = array('w' => 'cat', '$or' => array(array('ul_s' => array('$gte' => getUserLevel($uid))), array('u_o' => $uid), array('g_o' => array('$in' => getUserGroups($uid, true)))));
	} else {
		$find_stuff_query = array('w' => 'cat', '$or' => array(array('ul_v' => array('$gte' => getUserLevel($uid))), array('u_o' => $uid), array('g_o' => array('$in' => getUserGroups($uid, true)))));
	}

	//print_r($find_stuff_query);

	$get_stuff = $mdb->meta->find($find_stuff_query, $show_only);

	$get_stuff->sort(array('pid' => 1, 'ti' => 1));

	foreach ($get_stuff as $stuff) {
		$categories[] = $stuff;
	}

	return $categories;

}

// return a category's subcategories
function getSubcategories($cid) {
	if (!is_numeric($cid)) {
		return false;
	}

	$cid = (int) $cid * 1;

	global $mdb;

	$categories = array();

	$show_only = array('id' => true, 'ti' => true);
	$get_stuff = $mdb->meta->find(array('w' => 'cat', 'pid' => $cid), $show_only);
	$get_stuff->sort(array('ti' => 1));

	foreach ($get_stuff as $stuff) {
		$categories[] = $stuff;
	}

	return $categories;
}

function getCategoryInfo($cid) {

	if (!is_numeric($cid)) {
		return false;
	}

	$cid = (int) $cid * 1;

	global $mdb;

	$cat_info = array();

	$get_info = $mdb->meta->findOne(array('w' => 'cat', 'id' => $cid), array('mids' => false));
	if (!isset($get_info)) {
		return false;
	} else {
		$cat_info = $get_info;
	}

	return $cat_info;
}

// return category names from an array of category IDs
function getCategoryNames($cids) {
	if (!isset($cids) || !is_array($cids) || count($cids) < 1) {
		return false;
	}
	global $mdb;

	$categories = array();

	$find_cats = $mdb->meta->find(array('w' => 'cat', 'id' => array('$in' => $cids)), array('ti' => true, 'id' => true));

	foreach ($find_cats as $cat) {
		if (!isset($cat['ti'])) {
			continue; // skip anything without a title...
		}
		$categories[] = array('name' => $cat['ti'], 'id' => $cat['id']);
	}

	return $categories;
}

function canHaveSubcats($cid) {
	// determine if the current category can have subcategories
	// do not allow subcats below three levels

	if (!is_numeric($cid)) {
		return false;
	}

	$cid = (int) $cid * 1;

	global $mdb;

	$this_cat = getCategoryInfo($cid);
	if (isset($this_cat) && isset($this_cat['pid'])) {
		$parent_cat = getCategoryInfo($this_cat['pid']);
		if (isset($parent_cat) && isset($parent_cat['pid'])) {
			return false;
		} else {
			return true;
		}
	} else {
		return true;
	}

}

function getEvents($uid, $submit = false) {
	// get events the user can see, either because it's visible or they're an owner or in a group that owns it
	// if $submit, show events the user can submit to, either because it's submitable or they're an owner or in a group that owns it

	if (!isset($uid) || !is_numeric($uid)) {
		return false;
	}

	$uid = (int) $uid * 1;

	global $mdb;

	$events = array();

	$show_only = array('id' => true, 'ti' => true, 'de' => true, 'dl' => true, 'sd' => true, 'url' => true);

	if ($submit) {
		$find_stuff_query = array('w' => 'event', 'sd' => array('$lt' => time()), 'dl' => array('$gt' => time()), '$or' => array(array('ul_s' => array('$gte' => getUserLevel($uid))), array('u_o' => $uid), array('g_o' => array('$in' => getUserGroups($uid, true)))));
	} else {
		$find_stuff_query = array('w' => 'event', '$or' => array(array('ul_v' => array('$gte' => getUserLevel($uid))), array('u_o' => $uid), array('g_o' => array('$in' => getUserGroups($uid, true)))));
	}

	//print_r($find_stuff_query);

	$get_stuff = $mdb->meta->find($find_stuff_query, $show_only);

	$get_stuff->sort(array('ti' => 1));

	foreach ($get_stuff as $stuff) {
		$events[] = $stuff;
	}

	return $events;

}

function getEventInfo($eid) {

	if (!is_numeric($eid)) {
		return false;
	}

	$eid = (int) $eid * 1;

	global $mdb;

	$event_info = array();

	$get_info = $mdb->meta->findOne(array('w' => 'event', 'id' => $eid), array('mids' => false));
	if (!isset($get_info)) {
		return false;
	} else {
		$event_info = $get_info;
	}

	return $event_info;
}

// get groups the user can see, either because it's visible or they're a member
function getGroups($uid) {

	if (!isset($uid) || !is_numeric($uid)) {
		return false;
	}

	$uid = (int) $uid * 1;

	global $mdb;

	$groups = array();

	$show_only = array('gid' => true, 'n' => true, 'd' => true);

	$user_level = getUserLevel($uid);

	$get_stuff_query = array();

	if ($user_level != 1) {
		$get_stuff_query = array('$or' =>
			array(
				array('v' => array('$gte' => $user_level)),
				array('m' => $uid)
			)
		);
	}

	$get_stuff = $mdb->groups->find($get_stuff_query, $show_only);

	$get_stuff->sort(array('n' => 1));

	foreach ($get_stuff as $stuff) {
		$groups[] = $stuff;
	}

	return $groups;

}

// return group names from an array of group IDs
function getGroupNames($gids) {
	if (!isset($gids) || !is_array($gids) || count($gids) < 1) {
		return false;
	}
	global $mdb;

	$groups = array();

	$find_stuff = $mdb->groups->find(array('gid' => array('$in' => $gids)), array('gid' => true, 'n' => true));

	foreach ($find_stuff as $group) {
		$groups[] = array('name' => $group['n'], 'id' => $group['gid']);
	}

	return $groups;
}

// get list of possible metadata fields for a median entry
function getMetaDataList() {
	global $mdb;

	$fields = array();

	$find_stuff = $mdb->metafields->find(array('u' => array('$exists' => false)))->sort(array('d' => 1));

	foreach ($find_stuff as $field) {
		$fields[] = $field;
	}

	return $fields;
}

// get the current median news
function getNews($limit = 0) {

	if (is_numeric($limit) && $limit * 1 > 0) {
		$limit = (int) $limit * 1;
	} else {
		$limit = 0;
	}

	global $mdb;

	$news = array();

	$get_news = $mdb->news->find();
	if ($get_news->count() > 0) {
		$get_news->sort(array('tsc' => -1));
		if ($limit > 0) {
			$get_news->limit($limit);
		}
		foreach ($get_news as $post) {
			$news[] = $post;
		}
	}

	return $news;

}

function getGlobalAlerts() {

	global $mdb;

	$alerts = array();

	$get_alerts = $mdb->alerts->find(array('e' => true));
	if ($get_alerts->count() > 0) {
		$get_alerts->sort(array('tsc' => -1));
		foreach ($get_alerts as $alert) {
			$alerts[] = $alert;
		}
	}

	return $alerts;

}

// generate a new unique numeric ID for a given "meta type"
// such as group, category, event, etc
function generateNewMetaId($type) {

	if (!isset($type) || trim($type) == '') {
		return false;
	}

	$type = strtolower(trim($type));
	$what = '';

	global $mdb;

	switch ($type) {
		case 'cat':
		case 'category':
		$what = 'cid';
		break;
		case 'event':
		$what = 'eid';
		break;
	}

	if ($what == '') {
		return false;
	}

	$get_latest_id = $mdb->increments->findOne(array('w' => $what));
	$id = $get_latest_id['id'];
	$new_id = $id + 1;

	$new_entry = array();
	$new_entry['w'] = $type;
	$new_entry['id'] = (int) $new_id;
	$new_entry['ts'] = time();

	try {
		$result = $mdb->meta->insert($new_entry, array('w' => 1));
		$increase_id = $mdb->increments->update(array('w' => $what), array('$inc' => array('id' => 1) ), array('w' => 1) );
	} catch(MongoCursorException $e) {
		return false;
	}

	return $new_id;
}

// generate a new unique playlist numeric ID
function generateNewPlaylistId() {

	global $mdb;

	$get_latest_id = $mdb->increments->findOne(array('w' => 'plid'));
	$id = $get_latest_id['id'];
	$new_id = $id + 1;

	$new_entry = array();
	$new_entry['id'] = (int) $new_id;
	$new_entry['tsc'] = time();

	try {
		$result = $mdb->playlists->insert($new_entry, array('w' => 1));
		$increase_id = $mdb->increments->update(array('w' => 'plid'), array('$inc' => array('id' => 1) ), array('w' => 1) );
	} catch(MongoCursorException $e) {
		return false;
	}

	return $new_id;
}
