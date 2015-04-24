<?php

// restart all jobs that aren't done to status = 0

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
	bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

echo '<pre>';

require_once('/median-webapp/includes/dbconn_mongo.php');

if (isset($_GET['errord'])) {
	$query = array('s' => 3);
} else {
	$query = array('s' => 1);
}

$restart_jobs = $farmdb->jobs->update( $query, array('$set' => array('s' => 0, 'tsc' => time(), 'tsu' => time())), array('multiple' => true) );

echo 'Restarted any jobs with status = 1';

echo '</pre>';
