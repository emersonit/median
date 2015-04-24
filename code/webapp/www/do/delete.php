<?php

/*

	oh jeepers, delete stuff

	do permission checks, then do the associated delete function

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

$uid = $current_user['userid'];

// a helper function to extract info from the request
function getThingsToDelete($what = '') {
	// search through GET and POST variables for ID numbers
	// always prefer GET, and single IDs
	$whats = array();
	if (isset($_GET[$what]) && is_numeric($_GET[$what])) {
		$whats[] = (int) $_GET[$what] * 1;
	} else if (isset($_POST[$what])) {
		if (is_array($_POST[$what])) {
			$whats = $_POST[$what];
		} else if (is_numeric($_POST[$what])) {
			$whats[] = (int) $_POST[$what] * 1;
		}
	} else if (isset($_POST[$what.'s'])) {
		if (is_array($_POST[$what.'s'])) {
			$whats = $_POST[$what.'s'];
		} else if (is_numeric($_POST[$what.'s'])) {
			$whats[] = (int) $_POST[$what.'s'] * 1;
		}
	}
	return $whats;
}

$mids = getThingsToDelete('mid');
$cids = getThingsToDelete('cid');
$eids = getThingsToDelete('eid');
$gids = getThingsToDelete('gid');
$plids = getThingsToDelete('plid');

require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/delete_functions.php');

$delete_results = array();

if (count($mids) > 0) {
	// delete some media entries, oh dear
	foreach ($mids as $mid) {
		if (!canEditMedia($uid, $mid)) {
			continue;
		}
		$delete_result = deleteMediaEntry($mid);
		$delete_results[] = array('status' => $delete_result, 'mid' => $mid);
	}
}

if (count($cids) > 0) {
	// delete some categories, oh dear
	foreach ($cids as $cid) {
		if (!canEditCategory($uid, $cid)) {
			continue;
		}
		$delete_result = deleteCategory($cid);
		$delete_results[] = array('status' => $delete_result, 'cid' => $cid);
	}
}

if (count($eids) > 0) {
	// delete some events, oh dear
	foreach ($eids as $eid) {
		if (!canEditEvent($uid, $eid)) {
			continue;
		}
		$delete_result = deleteEvent($eid);
		$delete_results[] = array('status' => $delete_result, 'eid' => $eid);
	}
}

if (count($gids) > 0) {
	// delete some groups, oh dear
	foreach ($gids as $gid) {
		if (!canEditGroup($uid, $gid)) {
			continue;
		}
		$delete_result = deleteGroup($gid);
		$delete_results[] = array('status' => $delete_result, 'gid' => $gid);
	}
}

if (count($plids) > 0) {
	// delete some playlists, oh dear
	foreach ($plids as $plid) {
		if (!canEditGroup($uid, $plid)) {
			continue;
		}
		$delete_result = deletePlaylist($plid);
		$delete_results[] = array('status' => $delete_result, 'plid' => $plid);
	}
}

unset($delete_result);

//echo '<pre>Results: '.print_r($delete_results, true).'</pre>';

$page_uuid = 'delete-page';
$page_title = 'Delete Results'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Deletion Results</h2>
        <?php
        foreach ($delete_results as $delete_result) {
            echo '<div class="alert-box '.(($delete_result['status']) ? 'success': 'alert').'">';
            if (isset($delete_result['mid'])) {
                echo 'Media ID #'.$delete_result['mid'];
            } else if (isset($delete_result['gid'])) {
                echo 'Group ID #'.$delete_result['gid'];
            } else if (isset($delete_result['cid'])) {
                echo 'Category ID #'.$delete_result['cid'];
            } else if (isset($delete_result['eid'])) {
                echo 'Event ID #'.$delete_result['eid'];
            } else if (isset($delete_result['plid'])) {
                echo 'Playlist ID #'.$delete_result['plid'];
            }
            echo ' ';
            echo (($delete_result['status']) ? 'was deleted successfully.' : 'encountered an error while being deleted.');
            echo '</div>';
        }
        ?>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
