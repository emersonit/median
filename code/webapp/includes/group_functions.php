<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH GROUPS
		cyle gage, emerson college, 2012


	getGroupInfo($gid)
	getGroupMembers($gid, $as_just_ids)
	getGroupOwners($gid, $as_just_ids)
	isUserGroupMember($uid, $gid)
	isUserGroupOwner($uid, $gid)
	generateNewGroupId()

*/

require_once('/median-webapp/includes/dbconn_mongo.php');

function getGroupInfo($gid = 0) {

	if (!is_numeric($gid)) {
		return false;
	}

	$gid = (int) $gid * 1;

	global $mdb;

	$group = array();

	$find_group = $mdb->groups->findOne(array('gid' => $gid), array('o' => false, 'm' => false));
	if ($find_group == null) {
		return false;
	} else {
		$group = $find_group;
	}

	return $group;

}

function getGroupMembers($gid = 0, $as_just_ids = false) {
	// get group's members, with ecnet names + uids, or just UIDs

	if (!is_numeric($gid)) {
		return false;
	}

	$gid = (int) $gid * 1;

	global $mdb;

	$members = array();

	$find_members = $mdb->groups->findOne(array('gid' => $gid), array('m' => true));
	if ($find_members == null) {
		return false;
	} else {
		$members = $find_members['m'];
	}

	if ($as_just_ids) {
		return $members;
	} else {
		$members_full = array();
		$get_usernames = $mdb->users->find(array('uid' => array('$in' => $members)), array('uid' => true, 'ecnet' => true));
		foreach ($get_usernames as $user) {
			$members_full[] = array('uid' => $user['uid'], 'ecnet' => $user['ecnet']);
		}
		return $members_full;
	}

}

function getGroupOwners($gid = 0, $as_just_ids = false) {
	// get group's owners, with ecnet names + uids, or just UIDs

	if (!is_numeric($gid)) {
		return false;
	}

	$gid = (int) $gid * 1;

	global $mdb;

	$owners = array();

	$find_owners = $mdb->groups->findOne(array('gid' => $gid), array('o' => true));
	if ($find_owners == null) {
		return false;
	} else {
		$owners = $find_owners['o'];
	}

	if ($as_just_ids) {
		return $owners;
	} else {
		$owners_full = array();
		$get_usernames = $mdb->users->find(array('uid' => array('$in' => $owners)), array('uid' => true, 'ecnet' => true));
		foreach ($get_usernames as $user) {
			$owners_full[] = array('uid' => $user['uid'], 'ecnet' => $user['ecnet']);
		}
		return $owners_full;
	}

}

function isUserGroupMember($uid = 0, $gid = 0) {
	// is the user a member of the group?

	if (!is_numeric($gid) || !is_numeric($uid)) {
		return false;
	}

	$gid = (int) $gid * 1;
	$uid = (int) $uid * 1;

	global $mdb;

	$is_member = false;

	$find_membership = $mdb->groups->findOne(array('m' => $uid, 'gid' => $gid));
	if ($find_membership != null) {
		$is_member = true;
	}

	return $is_member;

}

function isUserGroupOwner($uid = 0, $gid = 0) {
	// the user an owner of the group?

	if (!is_numeric($gid) || !is_numeric($uid)) {
		return false;
	}

	$gid = (int) $gid * 1;
	$uid = (int) $uid * 1;

	global $mdb;

	$is_owner = false;

	$find_ownership = $mdb->groups->findOne(array('o' => $uid, 'gid' => $gid));
	if ($find_ownership != null) {
		$is_owner = true;
	}

	return $is_owner;

}

// generate a new unique group ID number
function generateNewGroupId() {

	global $mdb;

	/*
	$find_latest = $mdb->groups->find(array('gid' => array('$exists' => true)), array('gid' => true));
	$find_latest->sort(array('gid' => -1));
	$find_latest->limit(1);
	$latest_gid = $find_latest->getNext();
	$gid = (int) $latest_gid['gid'];
	*/

	$get_latest_gid = $mdb->increments->findOne(array('w' => 'gid'));
	$gid = $get_latest_gid['id'];

	$new_gid = $gid + 1;

	$new_entry = array();
	$new_entry['gid'] = $new_gid;
	$new_entry['ts'] = time();

	try {
		$result = $mdb->groups->insert($new_entry, array('w' => 1));
		$increase_id = $mdb->increments->update(array('w' => 'gid'), array('$inc' => array('id' => 1) ), array('w' => 1) );
	} catch(MongoCursorException $e) {
		return false;
	}

	return $new_gid;
}
