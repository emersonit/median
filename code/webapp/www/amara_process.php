<?php

/*

	new amara tools processing

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

if (!isset($_GET['mid']) || !is_numeric($_GET['mid'])) {
	die('ERROR: No MID given, not sure what to do!');
}

$mid = (int) $_GET['mid'] * 1;

if (!isset($_GET['a']) || trim($_GET['a']) == '') {
    die('ERROR: No action given, not sure what to do!');
}

$action = trim($_GET['a']);

require_once('/median-webapp/includes/permission_functions.php');

if (canUseAmara($current_user['userid'])) {
	
    require_once('/median-webapp/includes/subtitle_functions.php');
    
    if ($action == 'n') {
        // send media info out to amara
        $send_result = send_entry_to_amara($mid);
        if ($send_result['ok'] == true) {
            echo '<p>Sent! <a href="/media/'.$mid.'/">Go back to the entry.</a></p>';
        } else {
            die('<p>ERROR: there was a problem creating the Amara-Median link for #'.$mid.': '.$send_result['error'].'</p>');
        }
    } else if ($action == 'sd') {
        // resync one last time and remove from amara
        $resync_result = sync_captions_from_amara($mid);
        if ($resync_result['ok'] == true) {
            echo '<p>Synced #'.$mid.' captions to database!</p>';
            $delete_result = delete_entry_from_amara($mid);
            if ($delete_result['ok'] == true) {
                echo '<p>Removed! <a href="/media/'.$mid.'/">Go back to the entry.</a></p>';
            } else {
                die('<p>ERROR: there was a problem deleting the Amara-Median link for #'.$mid.': '.$delete_result['error'].'</p>');
            }
        } else {
            die('<p>ERROR: there was a problem saving #'.$mid.' to database: '.$resync_result['error'].'</p>');
        }
    } else if ($action == 'd') {
		$delete_result = delete_entry_from_amara($mid);
		if ($delete_result['ok'] == true) {
			echo '<p>Removed! <a href="/media/'.$mid.'/">Go back to the entry.</a></p>';
		} else {
			die('<p>ERROR: there was a problem deleting the Amara-Median link for #'.$mid.': '.$delete_result['error'].'</p>');
		}
    } else {
        die('ERROR: Invalid action given, not sure what to do!');
    }
} else {
	die('ERROR: You cannot use this functionality, sorry.');
}