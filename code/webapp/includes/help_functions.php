<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH THE HELP SYSTEM
		cyle gage, emerson college, 2012


	getHelpPages()
    getHelpPage($what)
    updateHelpViewCount($what)

*/

require_once('/median-webapp/includes/dbconn_mongo.php');

function getHelpPages() {

	global $mdb;

	$pages = array();

	$find_pages = $mdb->helpfiles->find(array(), array('ti' => true, 'k' => true, 'v' => true));
	if ($find_pages->count() > 0) {
		$find_pages->sort(array('v' => -1, 'ti' => 1));
		foreach ($find_pages as $page) {
			$pages[] = $page;
		}
	}

	return $pages;

}

function getHelpPage($what = '') {

	if (trim($what) == '') {
		return false;
	}

	$what = strtolower(trim($what));

	if (preg_match('/^[-_A-Z0-9]+$/i', $what) != true) {
		return false;
	}

	global $mdb;

	$what = strtolower(trim($what));

	$page = $mdb->helpfiles->findOne(array('k' => $what));

	if (!isset($page)) {
		return false;
	} else {
		return $page;
	}

}

function updateHelpViewCount($what = '') {

	if (trim($what) == '') {
		return false;
	}

	$what = strtolower(trim($what));

	if (preg_match('/^[-_A-Z0-9]+$/i', $what) != true) {
		return false;
	}

	global $mdb;

	$what = strtolower(trim($what));

	$update = $mdb->helpfiles->update(array('k' => $what), array('$inc' => array('v' => 1)));

	return true;

}
