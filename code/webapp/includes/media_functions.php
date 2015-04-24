<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH MEDIA
		cyle gage, emerson college, 2012


	getMediaInfo($mid)
	getMediaTitle($mid)
	getPermalink($mid)
	getMediaPaths($mid)
	getPlayerManifest($mid)
	getThumbnails($mid)
	getMediaListing($uid, $options)
	getTimeCodeFromSeconds($secs)
	getSecondsFromTimeCode($timecode)
	groupLevelToString($grouplevel)
	licenseString($license_string)
	metaFieldDisplay($metadata_array)
	getMediaComments($mid)
	bitrateToFriendly($bitrate)
	generateNewMediaRow($uid)
	updateViewCount($mid, $uid)
	updateDownloadCount($mid, $uid)
	addMediaComment($comment, $mid, $uid, $timecode)
	makeNewClip($mid, $uid, $in, $out)
	addMediaToClass($mid, $uid, $class, $semester)
	isMediaInAkamai($mid)
	getEmbedViewCount($mid)
	getHTMLFIVElink($mid)

*/

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/dbconn_mongo.php');
require_once('/median-webapp/includes/meta_functions.php'); // includes user_functions.php
require_once('/median-webapp/includes/activity_functions.php');

function getMediaInfo($mid) {
	// get all the meta-info for a piece of media

	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}

	$mid = (int) $mid * 1;

	global $mdb;

	$media_info = array();

	$fetch_media_info = $mdb->media->findOne(array('mid' => $mid));
	if ($fetch_media_info == null) {
		return false;
	} else {
		$media_info = $fetch_media_info;
	}

	return $media_info;
}

function getMediaTitle($mid) {
	// just return the title of the entry
	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}
	$mid = (int) $mid * 1;
	global $mdb;
	$media_title = 'Untitled';
	$fetch_media_info = $mdb->media->findOne(array('mid' => $mid, 'ti' => array('$exists' => true)), array('ti' => true));
	if ($fetch_media_info == null) {
		return false;
	} else {
		$media_title = $fetch_media_info['ti'];
	}
	return $media_title;
}

function getPermalink($mid) {
	if (!is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}
	global $median_base_url;
	return $median_base_url.'media/'.$mid.'/';
}

function getMediaPaths($mid) {
	// get all the path info for a piece of media

	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}

	$mid = (int) $mid * 1;

	global $mdb;

	$paths = array();

	$fetch_media_info = $mdb->media->findOne(array('mid' => $mid), array('pa' => true));
	if ($fetch_media_info == null || !isset($fetch_media_info['pa'])) {
		return false;
	} else {
		$paths = $fetch_media_info['pa'];
	}

	return $paths;
}

function getPlayerManifest($mid) {
	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}
	global $median_base_url;
	$the_manifest = $median_base_url.'manifest/vod/'.$mid.'.json';
	return $the_manifest;
}

function getThumbnails($mid) {
	// get small and big thumbnails
	// return $thumbs array with $thumbs['big'] and $thumbs['small']

	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}

	$mid = (int) $mid * 1;

	global $mdb;

	$thumbs = array();
	$thumbs['big'] = '';
	$thumbs['small'] = '';

	$fetch_media_info = $mdb->media->findOne(array('mid' => $mid, 'th' => array('$exists' => true)), array('th' => true));
	if ($fetch_media_info == null) {
		return false;
	} else {
		if (isset($fetch_media_info['th']) && is_array($fetch_media_info['th'])) {
			if (isset($fetch_media_info['th']['b']) && trim($fetch_media_info['th']['b']) != '') {
				$thumbs['big'] = $fetch_media_info['th']['b'];
			}
			if (isset($fetch_media_info['th']['s']) && trim($fetch_media_info['th']['s']) != '') {
				$thumbs['small'] = $fetch_media_info['th']['s'];
			}
		}
	}

	if ($thumbs['big'] == '' && $thumbs['small'] == '') {
		return false;
	}

	return $thumbs;
}

function getMediaListing($uid = 0, $options = null) {
	// get listing of media; there's a lot of usage for this
	// default to a feed of the latest media based on current logged in user
	// the $options array can have howmany, page, filter array, mids list, context, etc
	// see below functionality for more info

	if (!isset($uid) || !is_numeric($uid)) {
		return false;
	}

	// acceptable values for certain things
	$acceptable_filter_types = array('video', 'audio', 'image', 'link', 'doc');

	// default options
	$context = 'nowhere';
	$howmany = 10;
	$page = 1;
	$sort = 'latest';
	$filter = array();
	$mids = null;
	$user_level_override = 0;
	$managing = false;

	if (isset($options) && is_array($options)) {
		if (isset($options['howmany']) && is_numeric($options['howmany'])) { $howmany = (int) $options['howmany'] * 1; }
		if (isset($options['page']) && is_numeric($options['page'])) { $page = (int) $options['page'] * 1; }
		if (isset($options['filter']) && is_array($options['filter'])) { $filter = $options['filter']; }
		if (isset($options['mids']) && is_array($options['mids'])) { $mids = $options['mids']; }
		if (isset($options['context']) && trim($options['context']) != '') { $context = strtolower(trim($options['context'])); }
		if (isset($options['sort']) && trim($options['sort']) != '') { $sort = strtolower(trim($options['sort'])); }
		if (isset($options['ul']) && is_numeric($options['ul'])) { $user_level_override = (int) $options['ul'] * 1; }
		if (isset($options['manage']) && is_bool($options['manage'])) { $managing = (bool) $options['manage']; }
	}

	$uid = (int) $uid * 1;

	global $mdb;

	$media_list = array();

	// return only these fields from the database
	$field_filter = array('mid' => true, 'mt' => true, 'ty' => true, 'ti' => true, 'vc' => true, 'cc' => true, 'ow' => true, 'th' => true, 'tsc' => true, 'du' => true, 'en' => true, 'me.notes' => true);
	// blank media query to start with
	$media_query = array();

	// if given a list of mids, just fucking get them
	if (isset($mids) && is_array($mids)) {
		if ($managing) {
			$media_query = array('mid' => array('$in' => $mids), 'pending' => array('$exists' => false));
		} else {
			$media_query = array('mid' => array('$in' => $mids), 'pending' => array('$exists' => false), 'en' => true);
		}
		if (isset($filter['type']) && trim($filter['type']) != '' && in_array($filter['type'], $acceptable_filter_types)) {
			$media_query['mt'] = strtolower(trim($filter['type'])); // only get entries with this metatype
		}
		$get_media = $mdb->media->find($media_query, $field_filter);
	} else {

		// get the requester's user level
		if ($user_level_override > 0) {
			$user_level = (int) $user_level_override * 1;
		} else {
			$user_level = getUserLevel($uid);
		}

		// whether or not to show things marked as "don't show" by the user
		// TRUE means DO NOT SHOW THEM, FALSE means WHATEVER MAN, SHOW IT
		$dontshow_toggle = true;

		// basic filter for all queries

		if (!$managing) {
			$media_query['en'] = true; // has to be enabled!
		}

		$media_query['pending'] = array('$exists' => false);

		if ($user_level > 1) {
			$media_query['ul'] = array('$gte' => $user_level); // has to be user level accessible
		}

		if ($user_level > 1 && !isset($filter['clid'])) {
			$media_query['co'] = false; // unless there's a class filter, has to be NOT class-only
		}

		if ($user_level > 1 && !isset($filter['gid'])) {
			$media_query['as.gr'] = array('$exists' => false); // has to be NOT group-restricted
		}

		if ($user_level != 1) {
			$media_query['ha'] = false; // do not get hidden stuff for lists
		}

		// filter and stuff and whatever
		// filter options: by meta type (audio, video, image), by meta object (category, event, group, class, etc)

		if (isset($filter['ul']) && is_numeric($filter['ul'])) {
			$new_user_level = (int) $filter['ul'] * 1;
			$media_query['ul'] = array('$gte' => $new_user_level);
		}

		if (isset($filter['cid']) && is_numeric($filter['cid'])) {
			$media_query['as.ca'] = (int) $filter['cid'] * 1; // get only entries in this category
			$dontshow_toggle = false;
		}

		if (isset($filter['eid']) && is_numeric($filter['eid'])) {
			$media_query['as.ev'] = (int) $filter['eid'] * 1; // get only entries in this event
			$dontshow_toggle = false;
		}

		if (isset($filter['plid']) && is_numeric($filter['plid'])) {
			$media_query['as.pl'] = (int) $filter['plid'] * 1; // get only entries in this event
			$dontshow_toggle = false;
		}

		if (isset($filter['gid']) && is_numeric($filter['gid'])) {
			$gid = (int) $filter['gid'] * 1;
			if ($context == 'group-page-as-member') { // only if we're on the group's page as a member of the group
				$media_query['$or'] = array('ow.g' => $gid, 'as.gr' => $gid); // get only entries in or restricted to this group
			} else {
				$media_query['ow.g'] = $gid; // only get entries owned by this group
			}
			$dontshow_toggle = false;
		}

		if (isset($filter['type']) && trim($filter['type']) != '' && in_array($filter['type'], $acceptable_filter_types)) {
			$media_query['mt'] = strtolower(trim($filter['type'])); // only get entries with this metatype
			$dontshow_toggle = false;
		}

		if (isset($filter['tag']) && trim($filter['tag']) != '') {
			$media_query['as.tg'] = strtolower($filter['tag']); // only get entries with this tag
			$dontshow_toggle = false;
		}

		if (isset($filter['uid']) && is_numeric($filter['uid'])) {
			$media_query['ow.u'] = (int) $filter['uid'] * 1; // only get entries owned by this user
			$dontshow_toggle = false;
		}

		if (isset($filter['aid']) && is_numeric($filter['aid'])) {
			$media_query['as.as'] = (int) $filter['aid'] * 1; // only get entries in this assignment
			$dontshow_toggle = false;
		}

		if (isset($filter['clid']) && trim($filter['clid']) != '') {
			//$course_code = $filter['clid']['c'];
			//$semester = $filter['clid']['s'];
			$this_semester_code = getCurrentSemesterCode();
			$media_query['as.cl'] = array('c' => $filter['clid'], 's' => $this_semester_code); // only get entries in this class and semester
			$dontshow_toggle = false;
		}

		if (isset($filter['title']) && trim($filter['title']) != '') {
			$media_query['ti'] = array('$regex' => $filter['title'], '$options' => 'i');
			$dontshow_toggle = false;
		}

		// ok, get the media using that query, oh lawds!
		$get_media = $mdb->media->find($media_query, $field_filter);

	}

	$total_entries = $get_media->count();

	// sort
	switch (strtolower($sort)) {
		case 'latest':
		case 'date_desc':
		$get_media->sort(array('tsc' => -1));
		break;
		case 'oldest':
		case 'date_asc':
		$get_media->sort(array('tsc' => 1));
		break;
		case 'alpha_asc':
		$get_media->sort(array('ti' => 1));
		break;
		case 'alpha_desc':
		$get_media->sort(array('ti' => -1));
		break;
		case 'time_asc':
		$get_media->sort(array('du' => 1));
		break;
		case 'time_desc':
		$get_media->sort(array('du' => -1));
		break;
		case 'views':
		case 'views_desc':
		$get_media->sort(array('vc' => -1));
		break;
		case 'comments':
		case 'comments_desc':
		$get_media->sort(array('cc' => -1));
		break;
		default:
		$get_media->sort(array('tsc' => -1));
	}
	// end sort

	// pagination
	$media_list['total'] = $total_entries;
	$media_list['pages'] = ceil($total_entries/$howmany);
	$media_list['perpage'] = $howmany;
	$page_actual = $page - 1;
	if ($page_actual > 0) {
		$get_media->skip($howmany * $page_actual);
	}
	$get_media->limit($howmany);
	// end pagination

	// ok now get them and put them in an array
	foreach ($get_media as $media_entry) {
		$media_list[] = $media_entry;
	}

	return $media_list;
}

// helper function to generate a timecode from seconds number
function getTimeCodeFromSeconds($secs) {
	$formattedTime = '';
	$timeSeconds = round($secs);
	$hours = floor($timeSeconds / 60 / 60);
	$minutes = floor(($timeSeconds / 60) % 60);
	$seconds = $timeSeconds % 60;
	$secondsTenths = (round($secs * 10) % 600)/10;
	if ($hours > 0) {
		$formattedTime = $hours . ":";
	}
	$formattedTime .= ( ($minutes < 10) ? "0" : "" ) . $minutes . ":" . ( ($secondsTenths < 10) ? "0" : "" ) . $secondsTenths;
	return $formattedTime;
}

// helper function get get seconds number from timecode string
function getSecondsFromTimeCode($timecode) {
	$timecode_array = explode(':', trim($timecode));
	if (count($timecode_array) == 3) {
		$timecode_seconds = ($timecode_array[0] * 60 * 60) + ($timecode_array[1] * 60) + $timecode_array[2];
	} else if (count($timecode_array) == 2) {
		$timecode_seconds = ($timecode_array[0] * 60) + $timecode_array[1];
	} else {
		$timecode_seconds = $timecode * 1;
	}
	return $timecode_seconds;
}

// helper function to turn a user level int into a friendly string
function groupLevelToString($group) {
	$group = (int) $group * 1;
	switch($group) {
		case 0:
		$string = 'Owners Only';
		break;
		case 1:
		$string = 'Admin-Only';
		break;
		case 2:
		$string = 'Request Manager';
		break;
		case 3:
		$string = 'Request Manager';
		break;
		case 4:
		$string = 'Faculty-Only';
		break;
		case 5:
		$string = 'Emerson Community';
		break;
		case 6:
		$string = 'Public';
		break;
		default:
		$string = 'Unknown';
	}
	return $string;
}

// helper function to turn a license code into a friendly string
function licenseString($type) {
	$tempname = 'Unknown';
	if (preg_match('/cc_by$/', $type) || preg_match('/cc-by$/', $type)) {
		$tempname = 'Creative Commons: Attribution';
	}
	if (preg_match('/cc_nd$/', $type) || preg_match('/cc-nd$/', $type)) {
		$tempname = 'Creative Commons: Attribution No Derivatives';
	}
	if (preg_match('/cc_sa$/', $type) || preg_match('/cc-sa$/', $type)) {
		$tempname = 'Creative Commons: Attribution Share-alike';
	}
	if (preg_match('/cc_by-sa$/', $type) || preg_match('/cc-by-sa$/', $type)) {
		$tempname = 'Creative Commons: Attribution Share-Alike';
	}
	if (preg_match('/cc_by-nc$/', $type) || preg_match('/cc-by-nc$/', $type)) {
		$tempname = 'Creative Commons: Attribution Non-Commercial';
	}
	if (preg_match('/cc_by-nd$/', $type) || preg_match('/cc-by-nd$/', $type)) {
		$tempname = 'Creative Commons: Attribution No Derivatives';
	}
	if (preg_match('/cc_by-nc-nd$/', $type) || preg_match('/cc-by-nc-nd$/', $type)) {
		$tempname = 'Creative Commons: Attribution Non-Commercial No Derivatives';
	}
	if (preg_match('/cc_by-nc-sa$/', $type) || preg_match('/cc-by-nc-sa$/', $type)) {
		$tempname = 'Creative Commons: Attribution Non-Commercial Share-Alike';
	}
	if (preg_match('/gpl/', $type)) {
		$tempname = 'GPL';
	}
	if (preg_match('/copyright$/', $type)) {
		$tempname = 'Strict Copyright';
	}
	if (preg_match('/public$/', $type)) {
		$tempname = 'Public Domain';
	}
	if (preg_match('/(und|undetermined|unknown)$/', $type)) {
		$tempname = 'Undetermined';
	}
	return $tempname;
}

// helper function for displaying metadata fields to the user
function metaFieldDisplay($keyval) {
	if (!is_array($keyval)) {
		return false;
	}

	global $mdb;

	$key = $keyval['key'];
	$val = $keyval['val'];

	// special rules for displaying certain pieces of metadata
	switch ($key) {
		case 'seasonpremiere':
		case 'episodepremiere':
		case 'seriespremiere':
		case 'airdate':
		case 'datetaken':
		case 'datemade':
		case 'date':
		case 'createdon':
		$display_val = date('n/j/Y', strtotime($val));
		break;
		default:
		$display_val = $val;
	}
	if (!isset($display_val)) {
		$display_val = $val;
	}

	$get_display_key = $mdb->metafields->findOne(array('id' => $key));
	if (isset($get_display_key) && isset($get_display_key['d'])) {
		$display_key = $get_display_key['d'];
	} else {
		$display_key = ucwords($key);
	}

	return array('key' => $display_key, 'val' => $display_val);
}

// get comments for a given media entry
function getMediaComments($mid) {

	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}

	$mid = (int) $mid * 1;

	global $mdb;

	$comments = array();

	$get_comments = $mdb->comments->find(array('mid' => $mid));

	if ($get_comments->count() > 0) {
		$get_comments->sort(array('tsc' => -1));
		foreach ($get_comments as $comment) {
			$comments[] = $comment;
		}
	}

	return $comments;

}

// helper function to turn a bitrate into a friendly string
function bitrateToFriendly($bitrate) {
	if (!is_numeric($bitrate)) {
		return false;
	}

	$bitrate = (int) $bitrate * 1;
	$friendly_string = 'Original File';

	switch ($bitrate) {
		case 364:
		$friendly_string = 'Mobile';
		break;
		case 696:
		$friendly_string = 'SD';
		break;
		case 1328:
		$friendly_string = 'HD';
		break;
		case 1828:
		$friendly_string = 'Ultra HD';
		break;
	}

	return $friendly_string;
}

// make a new unique ID number and record for a new median entry
function generateNewMediaRow($uid = 0, $title = '') {
	// ok so make a new numeric media ID with a blank mongo record

	if (!isset($uid) || !is_numeric($uid)) {
		return false;
	}

	$uid = (int) $uid * 1;

	$new_mid = 0;

	global $mdb;

	$get_latest_mid = $mdb->increments->findOne(array('w' => 'mid'));
	$mid = $get_latest_mid['id'];

	$new_mid = $mid + 1;

	$new_entry = array();
	$new_entry['mid'] = $new_mid;
	$new_entry['uid'] = $uid;
	$new_entry['en'] = false;
	$new_entry['tsc'] = time();
	$new_entry['tsu'] = time();
	if (isset($title) && trim($title) != '') {
		$new_entry['ti'] = $title;
	}

	try {
		$result = $mdb->media->insert($new_entry, array('w' => 1));
		$increase_id = $mdb->increments->update(array('w' => 'mid'), array('$inc' => array('id' => 1) ), array('w' => 1) );
	} catch(MongoCursorException $e) {
		return false;
	}

	// return MID
	return $new_mid;
}

function updateViewCount($mid = 0, $uid = 0, $embed = false, $embedParentURL = '') {
	// update the view count by one in the media row
	// add a media view record

	if (!isset($mid) || !is_numeric($mid)) {
		return false;
	}

	$mid = (int) $mid * 1;

	if (!isset($uid) || !is_numeric($uid)) {
		$uid = 0;
	} else {
		$uid = (int) $uid * 1;
	}

	global $mdb;

	// update the media row with one more view
	$update_media = $mdb->media->update(array('mid' => $mid), array('$inc' => array('vc' => 1)));

	// add a view record for metrics
	$new_view = array();
	$new_view['ts'] = time();
	$new_view['dd'] = intval(date('j')); // date day
	$new_view['dm'] = intval(date('n')); // date month
	$new_view['dy'] = intval(date('Y')); // date year
	if (isset($_SERVER['REMOTE_ADDR']) && trim($_SERVER['REMOTE_ADDR']) != '' && trim($_SERVER['REMOTE_ADDR']) != '127.0.0.1') {
		$new_view['ip'] = trim($_SERVER['REMOTE_ADDR']); // ip address of viewer
	} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && trim($_SERVER['HTTP_X_FORWARDED_FOR']) != '' && trim($_SERVER['HTTP_X_FORWARDED_FOR']) != '127.0.0.1') {
		$new_view['ip'] = trim($_SERVER['HTTP_X_FORWARDED_FOR']); // ip address of viewer
	}
	$new_view['mid'] = $mid; // media ID being viewed, of course
	if ($uid > 0) {
		$new_view['uid'] = $uid; // user viewing, if any
	}
	if (isset($embed) && $embed == true) {
		$new_view['em'] = true;
		if (isset($embedParentURL) && trim($embedParentURL) != '') {
			$new_view['p_url'] = trim($embedParentURL);
		}
	}
	$insert_view = $mdb->views->insert($new_view);

	return true;

}

function updateDownloadCount($mid = 0, $uid = 0) {
	// update the view count by one in the media row
	// add a media view record

	if (!isset($mid) || !is_numeric($mid)) {
		return false;
	}

	$mid = (int) $mid * 1;

	if (!isset($uid) || !is_numeric($uid)) {
		$uid = 0;
	} else {
		$uid = (int) $uid * 1;
	}

	global $mdb;

	// update the media row with one more download
	$update_media = $mdb->media->update(array('mid' => $mid), array('$inc' => array('dc' => 1)));

	// add a download record for metrics
	$new_download = array();
	$new_download['ts'] = time();
	$new_download['dd'] = intval(date('j')); // date day
	$new_download['dm'] = intval(date('n')); // date month
	$new_download['dy'] = intval(date('Y')); // date year
	if (isset($_SERVER['REMOTE_ADDR']) && trim($_SERVER['REMOTE_ADDR']) != '' && trim($_SERVER['REMOTE_ADDR']) != '127.0.0.1') {
		$new_download['ip'] = trim($_SERVER['REMOTE_ADDR']); // ip address of downloader
	} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && trim($_SERVER['HTTP_X_FORWARDED_FOR']) != '' && trim($_SERVER['HTTP_X_FORWARDED_FOR']) != '127.0.0.1') {
		$new_download['ip'] = trim($_SERVER['HTTP_X_FORWARDED_FOR']); // ip address of downloader
	}
	$new_download['mid'] = $mid; // media ID being downloaded, of course
	if ($uid > 0) {
		$new_download['uid'] = $uid; // user downloading, if any
	}
	$insert_download = $mdb->downloads->insert($new_download);

	return true;

}

function addMediaComment($comment = '', $mid = 0, $uid = 0, $timecode = 0) {

	if (!isset($comment) || trim($comment) == '') {
		return false;
	}

	$comment = trim($comment);

	if (!isset($mid) || !is_numeric($mid)) {
		return false;
	}

	$mid = (int) $mid * 1;

	if (!isset($uid) || !is_numeric($uid)) {
		return false;
	}

	$uid = (int) $uid * 1;

	global $mdb;

	$new_comment = array();
	$new_comment['tsc'] = time();
	$new_comment['mid'] = $mid;
	$new_comment['t'] = $comment;
	$new_comment['uid'] = $uid;
	if (isset($timecode) && $timecode * 1 > 0) {
		$new_comment['tc'] = (float) $timecode * 1;
	}

	try {
		$result = $mdb->comments->insert($new_comment, array('w' => 1));
	} catch(MongoCursorException $e) {
		return false;
	}

	// add action
	$new_action = array('uid' => $uid, 't' => 'newcomment', 'mid' => $mid);
	addNewAction($new_action);

	return true;

}

function makeNewClip($mid, $uid = 0, $in = 0, $out = 0, $title = '') {
	// make a new clip from a video entry

	if (!isset($mid) || !is_numeric($mid)) {
		return false;
	}

	if (!isset($uid) || !is_numeric($uid)) {
		return false;
	}

	if (!isset($in) || !is_numeric($in)) {
		return false;
	}

	if (!isset($out) || !is_numeric($out)) {
		return false;
	}

	global $mdb;

	$mid = (int) $mid * 1;
	$media_info = getMediaInfo($mid);

	if (!isset($title) || trim($title) == '') {
		$title = 'Clip of '.$media_info['ti'];
	}

	$uid = (int) $uid * 1;
	$in = (float) $in * 1;
	$out = (float) $out * 1;

	$new_mid = generateNewMediaRow($uid, $title);

	if ($new_mid == false) {
		return false;
	}

	$updated_mid = array();
	$updated_mid['mt'] = 'clip';
	$updated_mid['clip'] = array();
	$updated_mid['clip']['in'] = $in;
	$updated_mid['clip']['out'] = $out;
	$updated_mid['clip']['src'] = $mid;
	$updated_mid['ow']['u'] = array($uid); // just this user is an owner
	$updated_mid['en'] = true;
	$updated_mid['cc'] = 0;
	$updated_mid['vc'] = 0;
	$updated_mid['du'] = ceil($out - $in);

	// copy old entry's protection and other info
	if (isset($media_info['as'])) {
		$updated_mid['as'] = $media_info['as'];
	}
	if (isset($media_info['co'])) {
		$updated_mid['co'] = $media_info['co'];
	}
	if (isset($media_info['ha'])) {
		$updated_mid['ha'] = $media_info['ha'];
	}
	if (isset($media_info['ul'])) {
		$updated_mid['ul'] = $media_info['ul'];
	}
	if (isset($media_info['li'])) {
		$updated_mid['li'] = $media_info['li'];
	}
	if (isset($media_info['th'])) {
		$updated_mid['th'] = $media_info['th'];
	}

	try {
		$result = $mdb->media->update(array('mid' => $new_mid), array('$set' => $updated_mid), array('w' => 1));
	} catch(MongoCursorException $e) {
		return false;
	}

	// return new MID
	return $new_mid;

}

function addMediaToClass($mid, $uid = 0, $class = '', $semester = '') {

	if (!isset($mid) || !is_numeric($mid)) {
		return false;
	}

	if (!isset($uid) || !is_numeric($uid)) {
		return false;
	}

	if (!isset($class) || trim($class) == '') {
		return false;
	}

	$mid = (int) $mid * 1;
	$uid = (int) $uid * 1;

	global $mdb;
	
	if (isset($semester) && trim($semester) != '') {
		$semester_code = trim($semester);
	} else {
		$semester_code = getCurrentSemesterCode();
	}

	$new_class_entry = array('c' => strtoupper(trim($class)), 's' => $semester_code);

	try {
		$result = $mdb->media->update(array('mid' => $mid), array('$push' => array('as.cl' => $new_class_entry) ), array('w' => 1));
	} catch(MongoCursorException $e) {
		return false;
	}

	// also add an action
	$new_action = array('uid' => $uid, 't' => 'newmid', 'mid' => $mid, 'clid' => $new_class_entry);
	addNewAction($new_action);

	return true;

}

function isMediaInAkamai($mid) {
	if (!isset($mid) || !is_numeric($mid)) {
		return false;
	}

	$mid = (int) $mid * 1;

	global $mdb;

	$find_out = $mdb->akamai->findOne(array('mid' => $mid));

	if (isset($find_out)) {
		return true;
	} else {
		return false;
	}

}

function getEmbedViewCount($mid) {
	if (!isset($mid) || !is_numeric($mid)) {
		return false;
	}

	$mid = (int) $mid * 1;

	global $mdb;

	$get_views = $mdb->views->find( array('mid' => $mid, 'em' => true) )->count();

	return $get_views;
}

// get the unique secure sandbox URL for the HTML5 version of this entry
function getHTMLFIVElink($mid) {
	if (!is_numeric($mid)) {
		return false;
	}
	global $html5_file_base, $html5_url_base;
	$mid = (int) $mid * 1;
	$info = getMediaInfo($mid);
	if (isset($info['html5']) && trim($info['html5']) != '') {
		return str_replace($html5_file_base, $html5_url_base, trim($info['html5']));
	} else {
		return false;
	}
}
