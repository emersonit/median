<?php

$group_id = 62;

require_once(__DIR__.'/../includes/dbconn_mongo.php');

$mids = array();
$mids_file = file_get_contents('mids.txt');
$mids_file = str_replace("\r\n", "\n", $mids_file);
$mids_lines = explode("\n", $mids_file);
foreach ($mids_lines as $line) {
	if (trim($line) == '' || !is_numeric($line)) {
		continue;
	}
	$mids[] = (int) $line;
}

//print_r($mids);

foreach ($mids as $mid) {
	//echo 'checking '.$mid."\n";
	// check to see if they already have the group as an owner
	$entry = $mdb->media->findOne( array('mid' => $mid) );
	// if not, add it
	if (isset($entry['ow']['g']) && in_array($group_id, $entry['ow']['g'])) {
		continue; // all set
	} else {
		$doc_updates = array( '$push' => array( 'ow.g' => $group_id ) );
		$update = $mdb->media->update( array('mid' => $mid), $doc_updates );
		echo 'added group '.$group_id.' to entry '.$mid."\n";
	}
}
