<?php

// find videos that only have one "version" of them
// and transcode them all with more versions!

$login_required = true;
require_once('/median-webapp/includes/login_check.php');
require_once('/median-webapp/includes/dbconn_mongo.php');
require_once('/median-webapp/includes/file_functions.php');

if (isset($_GET['n']) && is_numeric($_GET['n'])) {
	$at_a_time = (int) $_GET['n'] * 1;
} else {
	$at_a_time = 15;
}

$get_entries = $mdb->media->find( array( 'mt' => 'video', 'pa.c' => array( '$size'  => 1 ) ) )->sort( array('mid' => -1) );

echo '<p>Total count: '.$get_entries->count().'</p>';

echo '<p>Doing the latest '.$at_a_time.'...</p>';

$get_entries->limit($at_a_time);

echo '<pre>'."\n";
foreach ($get_entries as $entry) {
	echo $entry['mid']."\n";
	//print_r($entry['pa']);
	$transcode_result = reTranscodeMedia($entry['mid']);
	if ($transcode_result) {
		echo 'Retranscoding!';
	} else {
		echo 'Error! Oh noes!';
	}
	echo "\n";
}
echo '</pre>'."\n";
