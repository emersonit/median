<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH MEDIA ENTRIES
	(specifically tailored for the file API)

		introduced for median 5
        continued use in median 6
		cyle gage, emerson college, 2012-2014


	getMediaInfo($mid)
	getMediaPaths($mid)
	getThumbnails($mid)
	generateNewMedianEntry($uid, $title)
	verifyAndUseDownloadCode($mid, $download_code)
	deleteMediaEntry($mid)

*/

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/dbconn_mongo.php');

// get all of the metadata from the database about a median entry
function getMediaInfo($mid = 0) {
	// get all the meta-info for a piece of media

	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}

	$mid = (int) $mid * 1;

	global $mdb;

	$media_info = array();

	$fetch_media_info = $mdb->media->findOne(array('mid' => $mid));
	if ($fetch_media_info == null) {
		return false;
	} else {
		$media_info = $fetch_media_info;
	}

	return $media_info;
}

// get just the media file path information
function getMediaPaths($mid = 0) {
	// get all the path info for a piece of media

	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}

	$mid = (int) $mid * 1;

	global $mdb;

	$paths = array();

	$fetch_media_info = $mdb->media->findOne(array('mid' => $mid), array('pa' => true));
	if ($fetch_media_info == null || !isset($fetch_media_info['pa'])) {
		return false;
	} else {
		$paths = $fetch_media_info['pa'];
	}

	return $paths;
}

// get just the entry's thumbnail information
function getThumbnails($mid = 0) {
	// get small and big thumbnails
	// $thumbs['big'] and $thumbs['small']

	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}

	$mid = (int) $mid * 1;

	global $mdb;

	$thumbs = array();
	$thumbs['big'] = '';
	$thumbs['small'] = '';

	$fetch_media_info = $mdb->media->findOne(array('mid' => $mid, 'th' => array('$exists' => true)), array('th' => true));
	if ($fetch_media_info == null) {
		return false;
	} else {
		if (isset($fetch_media_info['th']) && is_array($fetch_media_info['th'])) {
			if (isset($fetch_media_info['th']['b']) && trim($fetch_media_info['th']['b']) != '') {
				$thumbs['big'] = $fetch_media_info['th']['b'];
			}
			if (isset($fetch_media_info['th']['s']) && trim($fetch_media_info['th']['s']) != '') {
				$thumbs['small'] = $fetch_media_info['th']['s'];
			}
		}
	}

	if ($thumbs['big'] == '' && $thumbs['small'] == '') {
		return false;
	}

	return $thumbs;
}

// generate a new unqiue median entry and ID
function generateNewMedianEntry($uid = 0, $title = '') {
	// ok so make a new numeric media ID with a blank mongo record

	if (!isset($uid) || !is_numeric($uid)) {
		return false;
	}

	$uid = (int) $uid * 1;

	$new_mid = 0;

	global $mdb;

	$get_latest_mid = $mdb->increments->findOne(array('w' => 'mid'));
	$mid = $get_latest_mid['id'];

	$new_mid = $mid + 1;

	$new_entry = array();
	$new_entry['mid'] = $new_mid;
	$new_entry['uid'] = $uid;
	$new_entry['en'] = false;
	$new_entry['tsc'] = time();
	$new_entry['tsu'] = time();
	if (isset($title) && trim($title) != '') {
		$new_entry['ti'] = $title;
	}

	try {
		$result = $mdb->media->insert($new_entry, array('w' => 1));
		$increase_id = $mdb->increments->update(array('w' => 'mid'), array('$inc' => array('id' => 1) ), array('w' => 1) );
	} catch(MongoCursorException $e) {
		return false;
	}

	// return MID
	return $new_mid;
}

// verify a download code and mark it as used
function verifyAndUseDownloadCode($mid, $download_code) {

	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}

	if (!isset($download_code) || trim($download_code) == '') {
		return false;
	}

	$mid = (int) $mid * 1;
	$download_code = trim($download_code);

	global $mdb;

	$download_query = array( 'mid' => $mid, 'code' => $download_code );

	$get_download = $mdb->download_slots->findOne( $download_query );
	if ($get_download == null) {
		return false;
	} else {
		// ok, exists, now delete it so it can't be used again
		$delete_download = $mdb->download_slots->remove( $download_query, array('w' => 1) );
		// and return the file path
		return $get_download['file'];
	}

}

// delete a specific median entry
// along with its files and all metadata
function deleteMediaEntry($mid) {
	// delete media entry and everything associated with it!

	if (!isset($mid) || !is_numeric($mid)) {
		return false;
	}

	$mid = (int) $mid * 1;

	global $m, $mdb;

	// get paths -- delete files
	$media_paths = getMediaPaths($mid);
	if (is_array($media_paths)) {
		if (isset($media_paths['in']) && trim($media_paths['in']) != '') {
			if (file_exists($media_paths['in'])) {
				$delete_infile = unlink($media_paths['in']);
			}
		}
		if (isset($media_paths['c']) && is_array($media_paths['c']) && isset($media_paths['c'][0])) { // for videos
			foreach ($media_paths['c'] as $media_path) {
				if (file_exists($media_path['p'])) {
					$delete_outfile = unlink($media_path['p']);
				}
			}
		} else if (isset($media_paths['c']) && is_array($media_paths['c']) && isset($media_paths['c']['p'])) { // for audio
			if (file_exists($media_paths['c']['p'])) {
				$delete_outfile = unlink($media_paths['c']['p']);
			}
		} else if (isset($media_paths['c']) && is_string($media_paths['c'])) { // for images and documents
			if (file_exists($media_paths['c'])) {
				$delete_outfile = unlink($media_paths['c']);
			}
		}
	}

	// get thumbs -- delete them
	$media_thumbs = getThumbnails($mid);
	if ($media_thumbs != false) {
		if (isset($media_thumbs['big']) && $media_thumbs['big'] != '') {
			if (file_exists('/median'.$media_thumbs['big'])) {
				$delete_big_thumb = unlink('/median'.$media_thumbs['big']);
			}
		}
		if (isset($media_thumbs['small']) && $media_thumbs['small'] != '') {
			if (file_exists('/median'.$media_thumbs['small'])) {
				$delete_small_thumb = unlink('/median'.$media_thumbs['small']);
			}
		}
	}

	try {
		// delete these and be safe about it
		$delete_media_entry = $mdb->media->remove(array('mid' => $mid), array('w' => 1));
		$delete_media_comments = $mdb->comments->remove(array('mid' => $mid), array('w' => 1));
		// ok so these, delete at your leisure, it's not a big deal
		$delete_media_views = $mdb->views->remove(array('mid' => $mid));
		$delete_activity = $mdb->activity->remove(array('mid' => $mid));
		$delete_errors = $mdb->error_log->remove(array('mid' => $mid));
		$delete_from_meta = $mdb->meta->update( array(), array('$pull' => array('mids' => $mid)), array('multiple' => true));
		$delete_from_farm = $m->farm->jobs->remove(array('mid' => $mid));
	} catch(MongoCursorException $e) {
		return false;
	}

	return true;
}
