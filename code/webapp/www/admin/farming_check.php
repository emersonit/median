<?php

// take jobs that are error'd out and check if they should still actually exist...

die('needs to be reworked for median 6');

/*


    this will need to be reworked
    with the new file API


*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
	bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

echo '<pre>';

require_once('/median-webapp/includes/media_functions.php');
require_once('/median-webapp/includes/dbconn_mongo.php');

$get_errord = $farmdb->jobs->find( array('s' => 3, 'o' => 1) )->sort( array('tsc' => -1) );

if ($get_errord->count() > 0) {

	foreach ($get_errord as $job) {
		//print_r($job);
		echo 'checking mid #'.$job['mid']."\n";
		$media_info = getMediaInfo($job['mid']);
		if ($media_info == false) {
			// media ID doesn't even exist anymore, delete all this
			echo 'media entry does not even exist!'."\n";
			if (isset($job['in']) && file_exists($job['in'])) {
				unlink($job['in']);
			}
			$remove_job = $farmdb->jobs->remove( array('_id' => $job['_id']), array('w' => 1) );
		} else {

			// check if in file exists
			if (file_exists($job['in'])) {
				// file does exist... set this back to 0 and update tsu
				echo 'welp... try again...?'."\n";
				$update_job = $farmdb->jobs->update( array('_id' => $job['_id']), array('$set' => array('s' => 0, 'tsu' => time()) ), array('w' => 1) );
			} else {
				// file does not exist! delete all this
				echo 'media entry in file does not exist!'."\n";
				$remove_job = $farmdb->jobs->remove( array('_id' => $job['_id']), array('w' => 1) );
			}

		}
	}

} else {
	echo 'Uhhh nothing to check!';
}

echo '</pre>';
