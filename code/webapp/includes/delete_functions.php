<?php

/*

	FUNCTIONS THAT DELETE THINGS
		cyle gage, emerson college, 2012


	deleteMediaEntry($mid)
	deleteCategory($cid)
	deleteEvent($eid)
	deleteGroup($gid)
	deletePlaylist($plid)

*/

require_once('/median-webapp/includes/dbconn_mongo.php');
require_once('/median-webapp/includes/media_functions.php');
require_once('/median-webapp/includes/file_functions.php');
require_once('/median-webapp/includes/subtitle_functions.php');

function deleteMediaEntry($mid = 0) {
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
			$delete_infile = addDeleteFileToOperationsQueue($media_paths['in']);
		}
		if (isset($media_paths['c']) && is_array($media_paths['c']) && isset($media_paths['c'][0])) { // for videos
			foreach ($media_paths['c'] as $media_path) {
				$delete_outfile = addDeleteFileToOperationsQueue($media_path['p']);
			}
		} else if (isset($media_paths['c']) && is_array($media_paths['c']) && isset($media_paths['c']['p'])) { // for audio
			$delete_outfile = addDeleteFileToOperationsQueue($media_paths['c']['p']);
		} else if (isset($media_paths['c']) && is_string($media_paths['c'])) { // for images and documents
			$delete_outfile = addDeleteFileToOperationsQueue($media_paths['c']);
		}
	}

	// get thumbs -- delete them
	$media_thumbs = getThumbnails($mid);
	if ($media_thumbs != false) {
		if (isset($media_thumbs['big']) && $media_thumbs['big'] != '') {
			$delete_big_thumb = addDeleteFileToOperationsQueue('/median'.$media_thumbs['big']);
		}
		if (isset($media_thumbs['small']) && $media_thumbs['small'] != '') {
			$delete_small_thumb = addDeleteFileToOperationsQueue('/median'.$media_thumbs['small']);
		}
	}
	
	// delete subtitles if there are any
	$delete_subtitles = delete_subtitles($mid);

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

function deleteCategory($cid = 0) {
	// delete category, subcategories under this, and everything associated with it!
	if (!isset($cid) || !is_numeric($cid)) {
		return false;
	}

	$cid = (int) $cid * 1;

	global $mdb;

	// get child subcategories
	$cids = array();
	$cids[] = $cid;
	$find_subcats = $mdb->meta->find( array('w' => 'cat', 'pid' => $cid ) );
	if ($find_subcats->count() > 0) {
		foreach ($find_subcats as $subcat) {
			$cids[] = $subcat['id'];
			$find_subsubcats = $mdb->meta->find( array('w' => 'cat', 'pid' => $subcat['id'] ) );
			if ($find_subsubcats->count() > 0) {
				foreach ($find_subsubcats as $subsubcat) {
					$cids[] = $subsubcat['id'];
				}
			}
		}
	}

	// get mids using these categories
	$get_media = $mdb->media->find(array('as.ca' => array('$in' => $cids) ), array('mid' => true) );
	$mids = array();
	foreach ($get_media as $media_entry) {
		$mids[] = $media_entry['mid'];
	}
	unset($get_media);

	// delete art if there was any
	$meta_art = $mdb->meta->find( array('w' => 'cat', 'art_p' => array('$exists' => true), 'id' => array('$in' => $cids) ), array('art_p' => true) );
	if ($meta_art->count() > 0) {
		foreach ($meta_art as $temp_art) {
			$delete_art = addDeleteFileToOperationsQueue($temp_art['art_p']);
		}
	}

	try {
		// delete these and be safe about it
		$delete_cats = $mdb->meta->remove( array('w' => 'cat', 'id' => array('$in' => $cids) ), array('w' => 1) );

		// remove all mention of the category from media association
		$delete_from_media = $mdb->media->update( array(), array('$pullAll' => array('as.ca' => $cids)), array('multiple' => true, 'w' => 1));

		// if those media had no other category, add them to Uncategorized
		$update_media = $mdb->media->update( array('mid' => array('$in' => $mids), 'as.ca' => array('$size' => 0) ), array( '$push' => array( 'as.ca' => 1 ) ), array('multiple' => true, 'w' => 1) );

		// delete these at your leisure
		$delete_activity = $mdb->activity->remove(array('cid' => array('$in' => $cids) ));
		$delete_threads = $mdb->threads->remove(array('cid' => array('$in' => $cids) ));

	} catch(MongoCursorException $e) {
		return false;
	}

	return true;
}

function deleteEvent($eid = 0) {
	// delete event and everything associated with it!

	if (!isset($eid) || !is_numeric($eid)) {
		return false;
	}

	$eid = (int) $eid * 1;

	global $mdb;

	// delete art if there was any
	$meta_art = $mdb->meta->find( array('w' => 'event', 'art_p' => array('$exists' => true), 'id' => $eid ), array('art_p' => true) );
	if ($meta_art->count() > 0) {
		foreach ($meta_art as $temp_art) {
			$delete_art = addDeleteFileToOperationsQueue($temp_art['art_p']);
		}
	}

	try {
		// delete these and be safe about it
		$delete_event = $mdb->meta->remove( array('w' => 'event', 'id' => $eid ), array('w' => 1) );
		// delete these at your leisure
		$delete_activity = $mdb->activity->remove(array('eid' => $eid));
		$delete_threads = $mdb->threads->remove(array('eid' => $eid));

		// remove all mention of the event from media association
		$delete_from_media = $mdb->media->update( array(), array('$pull' => array('as.ev' => $eid)), array('multiple' => true));

	} catch(MongoCursorException $e) {
		return false;
	}

	return true;
}

function deleteGroup($gid = 0) {
	// delete group and everything associated with it!
	if (!isset($gid) || !is_numeric($gid)) {
		return false;
	}

	$gid = (int) $gid * 1;

	global $mdb;

	// delete art if there was any
	$meta_art = $mdb->groups->findOne( array('a' => array('$exists' => true), 'gid' => $gid ), array('a' => true) );
	if (isset($meta_art) && isset($meta_art['a']['p'])) {
		$delete_art = addDeleteFileToOperationsQueue($meta_art['a']['p']);
	}

	try {
		// delete these and be safe about it
		$delete_group = $mdb->groups->remove(array('gid' => $gid), array('w' => 1) );
		// delete these at your leisure
		$delete_activity = $mdb->activity->remove(array('gid' => $gid));
		$delete_threads = $mdb->threads->remove(array('gid' => $gid));

		// remove all mention of the group from media ownership or restriction
		$delete_from_media1 = $mdb->media->update( array(), array('$pull' => array('ow.g' => $gid)), array('multiple' => true));
		$delete_from_media2 = $mdb->media->update( array(), array('$pull' => array('as.gr' => $gid)), array('multiple' => true));

		// remove all mention of the group from meta ownership
		$delete_from_meta = $mdb->meta->update( array(), array('$pull' => array('g_o' => $gid)), array('multiple' => true));


	} catch(MongoCursorException $e) {
		return false;
	}

	return true;
}

function deletePlaylist($plid = 0) {
	// delete playlist and everything associated with it!

	if (!isset($plid) || !is_numeric($plid)) {
		return false;
	}

	$plid = (int) $plid * 1;

	global $mdb;

	// delete art if there was any
	$meta_art = $mdb->playlists->find( array('id' => $plid, 'art_p' => array('$exists' => true) ), array('art_p' => true) );
	if ($meta_art->count() > 0) {
		foreach ($meta_art as $temp_art) {
			$delete_art = addDeleteFileToOperationsQueue($temp_art['art_p']);
		}
	}

	try {
		// delete these and be safe about it
		$delete_event = $mdb->playlists->remove( array('id' => $plid ), array('w' => 1) );
		// delete these at your leisure
		$delete_activity = $mdb->activity->remove(array('plid' => $plid));

		// remove all mention of the event from media association
		$delete_from_media = $mdb->media->update( array(), array('$pull' => array('as.pl' => $plid)), array('multiple' => true));

	} catch(MongoCursorException $e) {
		return false;
	}

	return true;
}
