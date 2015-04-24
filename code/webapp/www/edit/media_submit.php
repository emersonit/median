<?php

/*

	SUBMIT THE MEDIA EDIT FORM
		cyle gage, emerson college, 2014

*/

//echo '<pre>'.print_r($_POST, true).'</pre>';

require_once('/median-webapp/includes/error_functions.php');

require_once('/median-webapp/includes/login_check.php');
if ($current_user['loggedin'] == false) {
	bailout('You are not logged in.', $current_user['userid']);
}
if ($current_user['userlevel'] > 5) {
	bailout('You do not have sufficient privileges to edit things.', $current_user['userid']);
}

$uid = $current_user['userid'];

if (!isset($_POST['mid']) || !is_numeric($_POST['mid'])) {
	bailout('You have not provided a media ID to edit.', $current_user['userid']);
}

$mid = (int) $_POST['mid'] * 1;

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/media_functions.php'); // includes mongo connection and meta functions
require_once('/median-webapp/includes/user_functions.php');

$media_info = getMediaInfo($mid);

if ($media_info == false) {
	// uhh media does not exist!
	bailout('Sorry, but the media entry with that ID does not exist!', $current_user['userid'], $mid);
}

require_once('/median-webapp/includes/log_functions.php');
$edit_log = openLogFile('edit.log');

writeToLog('New edit submit!', $edit_log, $uid, $mid);

// permission checks
$can_user_edit = canEditMedia($current_user['userid'], $mid);

if (!$can_user_edit) {
	writeToLog('ERROR: User does not have permission to edit this entry.', $edit_log, $uid, $mid);
	bailout('Sorry, but you do not have permission to edit this media entry.', $current_user['userid'], $mid);
}

require_once('/median-webapp/includes/activity_functions.php');
$media_collection = $mdb->media;

// this array is so that people who are copyrighting to certain holders and distributing publicly can still do so
$acceptable_copyrightviolators = $acceptable_html5_copyright_bypasses;

// update the entry with this array eventually
$updated_entry = array();

$is_link = false;
if ($media_info['mt'] == 'link') {
	$is_link = true;
	if (!isset($_POST['url']) || trim($_POST['url']) == '') {
		bailout('Sorry, but it appears you did not submit a link.', $current_user['userid'], $mid);
	}
	$updated_entry['url'] = trim($_POST['url']);
	writeToLog('It is a link to: '.$updated_entry['url'], $edit_log, $uid, $mid);
}



// ok now run through all the form stuff and add it to the media entry record

$updated_entry['ti'] = trim($_POST['title']);
writeToLog('Its title: '.$updated_entry['ti'], $edit_log, $uid, $mid);

/*

		run through organizational associations

*/

$updated_entry['as'] = array();

// there will always be at least one category
$updated_entry['as']['ca'] = array();
if (isset($_POST['cat']) && is_array($_POST['cat'])) {
	foreach ($_POST['cat'] as $cat) {
		if (!is_numeric($cat) || $cat * 1 == 0) { continue; }
		$updated_entry['as']['ca'][] = (int) $cat * 1;
	}
	if (count($updated_entry['as']['ca']) == 0) {
		$updated_entry['as']['ca'][] = 1;
	}
} else {
	$updated_entry['as']['ca'][] = 1;
}

$updated_entry['as']['ca'] = array_unique($updated_entry['as']['ca']);

// classes
if (isset($_POST['class']) && is_array($_POST['class'])) {
	$current_semester_code = getCurrentSemesterCode();
	$updated_entry['as']['cl'] = array();
	foreach ($_POST['class'] as $class) {
		if (trim($class) == '' || trim($class) == '0') {
			continue;
		}
		$updated_entry['as']['cl'][] = array('c' => strtoupper(trim($class)), 's' => $current_semester_code);
	}
	if (count($updated_entry['as']['cl']) == 0) {
		unset($updated_entry['as']['cl']);
	}
}

// tags
if (isset($_POST['tags']) && trim($_POST['tags']) != '') {
	$updated_entry['as']['tg'] = array();
	$tags = explode(',', $_POST['tags']);
	foreach ($tags as $tag) {
		if (trim($tag) == '') {
			continue;
		}
		$updated_entry['as']['tg'][] = trim($tag);
	}
	$updated_entry['as']['tg'] = array_unique($updated_entry['as']['tg']);
	if (count($updated_entry['as']['tg']) == 0) {
		unset($updated_entry['as']['tg']);
	}
}

// events
if (isset($_POST['event']) && is_array($_POST['event'])) {
	$updated_entry['as']['ev'] = array();
	foreach ($_POST['event'] as $event) {
		if (!is_numeric($event) || $event * 1 == 0) { continue; }
		$updated_entry['as']['ev'][] = (int) $event * 1;
	}
	$updated_entry['as']['ev'] = array_unique($updated_entry['as']['ev']);
	if (count($updated_entry['as']['ev']) == 0) {
		unset($updated_entry['as']['ev']);
	}
}

// playlists
if (isset($_POST['playlist']) && is_array($_POST['playlist'])) {
	$updated_entry['as']['pl'] = array();
	foreach ($_POST['playlist'] as $playlist) {
		if (!is_numeric($playlist) || $playlist * 1 <= 0) { continue; }
		$updated_entry['as']['pl'][] = (int) $playlist * 1;
	}
	$updated_entry['as']['pl'] = array_unique($updated_entry['as']['pl']);
	if (count($updated_entry['as']['pl']) == 0) {
		unset($updated_entry['as']['pl']);
	}
}

// group restrictions
if (isset($_POST['grouprestrict']) && is_array($_POST['grouprestrict'])) {
	$updated_entry['as']['gr'] = array();
	foreach ($_POST['grouprestrict'] as $group_restrict) {
		if (!is_numeric($group_restrict) || $group_restrict * 1 == 0) { continue; }
		$updated_entry['as']['gr'][] = (int) $group_restrict * 1;
	}
	$updated_entry['as']['gr'] = array_unique($updated_entry['as']['gr']);
	if (count($updated_entry['as']['gr']) == 0) {
		unset($updated_entry['as']['gr']);
	}
}



/*

		run through metadata

*/

$updated_entry['me'] = array();

// first, regular field names and vals
if (isset($_POST['fieldname']) && is_array($_POST['fieldname']) && isset($_POST['fieldval']) && is_array($_POST['fieldval']) && count($_POST['fieldname']) == count($_POST['fieldval'])) {
	$meta_fields = $_POST['fieldname'];
	$meta_vals = $_POST['fieldval'];
	for ($i = 0; $i < count($meta_fields); $i++) {
		if (trim($meta_fields[$i]) == '' || trim($meta_vals[$i]) == '') { // if their is no value, just go on, ignore it
			continue;
		}
		$updated_entry['me'][$meta_fields[$i]] = $meta_vals[$i];
	}
}


// second, custom field names and vals
if (isset($_POST['custom_fieldname']) && is_array($_POST['custom_fieldname']) && isset($_POST['custom_fieldval']) && is_array($_POST['custom_fieldval']) && count($_POST['custom_fieldname']) == count($_POST['custom_fieldval'])) {
	$custom_meta_fields = $_POST['custom_fieldname'];
	$custom_meta_vals = $_POST['custom_fieldval'];
	for ($i = 0; $i < count($custom_meta_fields); $i++) {
		if (trim($custom_meta_fields[$i]) == '' || trim($custom_meta_vals[$i]) == '') { // if their is no value, just go on, ignore it
			continue;
		}
		$updated_entry['me'][$custom_meta_fields[$i]] = $custom_meta_vals[$i];
	}
}

/*

		run through owners

*/


$updated_entry['ow'] = array();

// run through user owners
$updated_entry['ow']['u'] = array();
if (isset($_POST['userowner']) && is_array($_POST['userowner'])) {
	foreach ($_POST['userowner'] as $user_owner) {
		if (is_numeric($user_owner)) {
			$updated_entry['ow']['u'][] = (int) $user_owner * 1;
		} else if (trim($user_owner) != '') {
			// get UID from ecnet name
			$user_id = getUserId($user_owner);
			if ($user_id > 0) {
				$updated_entry['ow']['u'][] = (int) $user_id * 1;
			} else {
				continue; // could not find user with that ID, sooooo.......
			}
		} else {
			continue;
		}
	}
	if (count($updated_entry['ow']['u']) > 0) {
		$updated_entry['ow']['u'] = array_unique($updated_entry['ow']['u']);
	}
}

// run through group owners
$updated_entry['ow']['g'] = array();
if (isset($_POST['groupowner']) && is_array($_POST['groupowner'])) {
	foreach ($_POST['groupowner'] as $group_owner) {
		if (is_numeric($group_owner) && $group_owner * 1 > 0) {
			$updated_entry['ow']['g'][] = (int) $group_owner * 1;
		} else {
			continue;
		}
	}
	if (count($updated_entry['ow']['g']) > 0) {
		$updated_entry['ow']['g'] = array_unique($updated_entry['ow']['g']);
	}
}

// run through who should be "shown" as the owner
if (isset($_POST['show-owner']) && trim($_POST['show-owner']) != '' && trim($_POST['show-owner']) != '0') {
	if (is_numeric($_POST['show-owner']) && $_POST['show-owner'] * 1 > 0) {
		// ok so it's a group ID
		$updated_entry['ow']['s'] = array('t' => 'g', 'id' => intval($_POST['show-owner']));
	} else if (is_string($_POST['show-owner'])) {
		// ok so it's a username
		$show_owner_uid = getUserId(trim($_POST['show-owner']));
		if ($show_owner_uid > 0) {
			$updated_entry['ow']['s'] = array('t' => 'u', 'id' => $show_owner_uid);
		}
	}
}

/*

		run through license garbage

*/

if (!isset($_POST['media-owner']) || trim($_POST['media-owner']) == '') {
	writeToLog('ERROR: Somehow they did not select a media owner...', $edit_log, $uid, $mid);
	bailout('Somehow you did not select a media owner.', $current_user['userid']);
}

if (!isset($_POST['license-type']) || trim($_POST['license-type']) == '') {
	writeToLog('ERROR: Somehow they did not select a license...', $edit_log, $uid, $mid);
	bailout('ERROR: Somehow you did not select a license.', $current_user['userid']);
}

// this if / else block is to make sure old "my" license strings get parsed correctly
if (strtolower(trim($_POST['media-owner'])) == 'my' || strtolower(trim($_POST['media-owner'])) != 'me') {
	$media_original_owner = 'me';
} else {
	$media_original_owner = strtolower(trim($_POST['media-owner']));
}

$do_not_allow_public = false;
if (strtolower(trim($_POST['license-type'])) == 'unknown') {
	$do_not_allow_public = true;
} else if (strtolower(trim($_POST['license-type'])) == 'copyright' && $media_original_owner != 'me') {
	if (!in_array($media_original_owner, $acceptable_copyrightviolators)) {
		$do_not_allow_public = true;
	}
}

if (strtolower(trim($_POST['license-type'])) == 'cc') {
	$updated_entry['li'] = $media_original_owner . '_' . strtolower(trim($_POST['license-type-cc']));
} else {
	$updated_entry['li'] = $media_original_owner . '_' . strtolower(trim($_POST['license-type']));
}

// add in the license holder
if (isset($_POST['license-holder']) && trim($_POST['license-holder']) != '') {
	$updated_entry['me']['copyright_holder'] = trim($_POST['license-holder']);
}

// add in the license year
if (isset($_POST['license-year']) && trim($_POST['license-year']) != '') {
	$updated_entry['me']['copyright_yr'] = trim($_POST['license-year']);
}



/*

		run through privacy and access options

*/


// check access level -- check $do_not_allow_public
if (!isset($_POST['access-level']) || !is_numeric($_POST['access-level'])) {
	writeToLog('ERROR: Somehow they managed to not have an access level set...', $edit_log, $uid, $mid);
	bailout('Somehow you managed to not set an access level.', $current_user['userid']);
}
$user_access_level = (int) $_POST['access-level'] * 1;
if ($user_access_level == 6 && $do_not_allow_public) {
	$user_access_level = 5; // be silent about it...?
}

$updated_entry['ul'] = $user_access_level;

// check if class only
if (isset($_POST['class-only']) && $_POST['class-only'] * 1 == 1) {
	$updated_entry['co'] = true;
} else {
	$updated_entry['co'] = false;
}

// check if hidden
if (isset($_POST['hide-entry']) && $_POST['hide-entry'] * 1 == 1) {
	$updated_entry['ha'] = true;
} else {
	$updated_entry['ha'] = false;
}

// check for password
if (isset($_POST['pword']) && trim($_POST['pword']) != '') {
	$salty = hash('sha256', uniqid(rand(), true));
	$password_hash = hash('sha256', $salty.trim($_POST['pword']));
	$updated_entry['pwd'] = array('h' => $password_hash, 's' => $salty);
}

$remove_password = false;
if (isset($media_info['pwd']) && (!isset($_POST['pword']) || trim($_POST['pword']) == '')) {
	// remove password...
	$remove_password = true;
}

//echo '<h1>resulting media record update:</h1>';
//echo '<pre>';

// update the time!
$updated_entry['tsu'] = time();

writeToLog('updated entry: '.oneLinePrintArray($updated_entry), $edit_log, $uid, $mid);

// do the update!
//print_r($updated_entry);

try {
	$update = $media_collection->update(array('mid' => $mid), array('$set' => $updated_entry), array('w'=>1));
} catch(MongoCursorException $e) {
	// error.....
	writeToLog('ERROR: could not update mongo record! '.print_r($e, true), $edit_log, $uid, $mid);
	bailout('There was an error updating the media entry.', $current_user['userid'], $mid, print_r($e, true));
}

if ($remove_password) {
	try {
		$update = $media_collection->update(array('mid' => $mid), array('$unset' => array('pwd' => 1)), array('w'=>1));
	} catch(MongoCursorException $e) {
		// error.....
		writeToLog('ERROR: could not update mongo record! '.print_r($e, true), $edit_log, $uid, $mid);
		bailout('There was an error updating the media entry.', $current_user['userid'], $mid, print_r($e, true));
	}
}

//echo '</pre>';

writeToLog('Media record updated!', $edit_log, $uid, $mid);


// ok so now add activity records

// the base of all actions here:
$base_action = array('uid' => $uid, 't' => 'newmid', 'mid' => $mid);

// add the actions of user(s) getting a new entry assigned to them
if (count($updated_entry['ow']['u']) > 0) {
	foreach ($updated_entry['ow']['u'] as $new_uo_action) {
		$new_action = $base_action;
		$new_action['uid_ow'] = $new_uo_action;
		addNewAction($new_action);
	}
}

// add the actions of group(s) getting a new entry assigned to them
if (count($updated_entry['ow']['g']) > 0) {
	foreach ($updated_entry['ow']['g'] as $new_go_action) {
		$new_action = $base_action;
		$new_action['gid'] = $new_go_action;
		addNewAction($new_action);
	}
}

// add the actions of categories getting a new entry assigned to them
if (isset($updated_entry['as']['ca'])) {
	foreach ($updated_entry['as']['ca'] as $new_cat_action) {
		$new_action = $base_action;
		$new_action['cid'] = $new_cat_action;
		addNewAction($new_action);
	}
}

// add the actions of event(s) getting a new entry assigned to them
if (isset($updated_entry['as']['ev'])) {
	foreach ($updated_entry['as']['ev'] as $new_event_action) {
		$new_action = $base_action;
		$new_action['eid'] = $new_event_action;
		addNewAction($new_action);
	}
}

// add the actions of playlist(s) getting a new entry assigned to them
if (isset($updated_entry['as']['pl'])) {
	foreach ($updated_entry['as']['pl'] as $new_playlist_action) {
		$new_action = $base_action;
		$new_action['plid'] = $new_playlist_action;
		addNewAction($new_action);
	}
}

// add the actions of class(es) getting a new entry assigned to them
if (isset($updated_entry['as']['cl'])) {
	foreach ($updated_entry['as']['cl'] as $new_class_action) {
		$new_action = $base_action;
		$new_action['clid'] = $new_class_action;
		addNewAction($new_action);
	}
}

writeToLog('Added actions!', $edit_log, $uid, $mid);

header('Location: /manage/edit-success/');
