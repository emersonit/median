<?php

/*

	bulk-update media entries with updated info
        like category or group ownership or something

*/

require_once('/median-webapp/includes/error_functions.php');

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

if (!isset($_POST['mid']) || !is_array($_POST['mid'])) {
	bailout('Sorry, you have not provided any Median IDs to update.', $current_user['userid']);
}

$mids = array();
foreach ($_POST['mid'] as $mid) {
	if (!is_numeric($mid) || $mid * 1 <= 0) {
		continue;
	}
	$mids[] = (int) $mid * 1;
}

unset($mid);

if (count($mids) == 0) {
	bailout('Sorry, you have not provided any Median IDs to update.', $current_user['userid']);
}

require_once('/median-webapp/includes/meta_functions.php');

// got mids, now what to do with them!
$updated_entry = array();

// group owners
if (isset($_POST['groupowner']) && is_array($_POST['groupowner'])) {
	$group_owners = array();
	foreach ($_POST['groupowner'] as $group_owner) {
		if (!is_numeric($group_owner) || $group_owner * 1 <= 0) {
			continue;
		}
		$group_owners[] = (int) $group_owner * 1;
	}
	if (count($group_owners) > 0) {
		$group_owners = array_unique($group_owners);
		$updated_entry['ow.g'] = $group_owners;
	}
}

// categories
if (isset($_POST['cat']) && is_array($_POST['cat'])) {
	$cats = array();
	foreach ($_POST['cat'] as $cat) {
		if (!is_numeric($cat) || $cat * 1 <= 0) {
			continue;
		}
		$cats[] = (int) $cat * 1;
	}
	if (count($cats) > 0) {
		$cats = array_unique($cats);
		$updated_entry['as.ca'] = $cats;
	}
}

// events
if (isset($_POST['event']) && is_array($_POST['event'])) {
	$events = array();
	foreach ($_POST['event'] as $event) {
		if (!is_numeric($event) || $event * 1 <= 0) {
			continue;
		}
		$events[] = (int) $event * 1;
	}
	if (count($events) > 0) {
		$events = array_unique($events);
		$updated_entry['as.ev'] = $events;
	}
}

// playlists
if (isset($_POST['playlist']) && is_array($_POST['playlist'])) {
	$playlists = array();
	foreach ($_POST['playlist'] as $playlist) {
		if (!is_numeric($playlist) || $playlist * 1 <= 0) {
			continue;
		}
		$playlists[] = (int) $playlist * 1;
	}
	if (count($playlists) > 0) {
		$playlists = array_unique($playlists);
		$updated_entry['as.pl'] = $playlists;
	}
}

// classes
if (isset($_POST['class']) && is_array($_POST['class'])) {
	$current_semester_code = getCurrentSemesterCode();
	$classes = array();
	foreach ($_POST['class'] as $class) {
		if (trim($class) == '0' || trim($class) == '') {
			continue;
		}
		$classes[] = array('c' => strtoupper(trim($class)), 's' => $current_semester_code);
	}
	if (count($classes) > 0) {
		$updated_entry['as.cl'] = $classes;
	}
}

// tags
if (isset($_POST['tags']) && trim($_POST['tags']) != '') {
	$tags = array();
	$temp_tags = explode(',', $_POST['tags']);
	foreach ($temp_tags as $tag) {
		if (trim($tag) == '') {
			continue;
		}
		$tags[] = trim($tag);
	}
	if (count($tags) > 0) {
		$tags = array_unique($tags);
		$updated_entry['as.tg'] = $tags;
	}
}

//echo '<pre>';
//echo 'gonna do: '."\n";
//echo print_r($updated_entry, true);


require_once('/median-webapp/includes/dbconn_mongo.php');

// ok so now add activity records

require_once('/median-webapp/includes/activity_functions.php');

foreach ($mids as $mid) {

	//echo 'doing it to '.$mid."\n";

	try {
		$update = $mdb->media->update(array('mid' => $mid), array('$pushAll' => $updated_entry), array('w'=>1));
	} catch(MongoCursorException $e) {
		// error.....
		bailout('There was an error updating the media entry.', $current_user['userid']);
	}

	//$new_entry = $mdb->media->findOne(array('mid' => $mid));
	//print_r($new_entry);

	// the base of all actions here:
	$base_action = array('uid' => $current_user['userid'], 't' => 'newmid', 'mid' => $mid);

	// add the actions of group(s) getting a new entry assigned to them
	if (isset($group_owners)) {
		foreach ($group_owners as $new_go_action) {
			$new_action = $base_action;
			$new_action['gid'] = $new_go_action;
			addNewAction($new_action);
		}
	}

	// add the actions of categories getting a new entry assigned to them
	if (isset($cats)) {
		foreach ($cats as $new_cat_action) {
			$new_action = $base_action;
			$new_action['cid'] = $new_cat_action;
			addNewAction($new_action);
		}
	}

	// add the actions of event(s) getting a new entry assigned to them
	if (isset($events)) {
		foreach ($events as $new_event_action) {
			$new_action = $base_action;
			$new_action['eid'] = $new_event_action;
			addNewAction($new_action);
		}
	}

	// add the actions of class(es) getting a new entry assigned to them
	if (isset($classes)) {
		foreach ($classes as $new_class_action) {
			$new_action = $base_action;
			$new_action['clid'] = $new_class_action;
			addNewAction($new_action);
		}
	}

} // end add actions

//echo '</pre>';

header('Location: /manage/edit-success/');
