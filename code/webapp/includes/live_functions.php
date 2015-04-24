<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH MEDIAN LIVE
		cyle gage, emerson college, 2014


	getLiveStreamManifestURL($stream_id)
	getLiveListFromNginx()
	getLiveList($uid)
	getLiveInfo($lid)
	addNewLiveStream($uid, $title, $access, $description)

*/

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/dbconn_mongo.php');
require_once('/median-webapp/includes/user_functions.php');

// this returns a URL to a live stream manifest file
function getLiveStreamManifestURL($stream_id) {
	if (!isset($stream_id) || !is_numeric($stream_id) || $stream_id * 1 == 0) {
		return false;
	}
	global $median_base_url;
	return $median_base_url.'manifest/live/'.$stream_id.'.json';
}

// get list of current live streams from the nginx-rtmp servers
function getLiveListFromNginx() {
	global $nginx_rtmp_servers;
	$stream_names = array();
	$ctx = stream_context_create(array('http' => array('timeout' => 1)));
	foreach ($nginx_rtmp_servers as $nginx_server) {
		$live_stats_xml = @file_get_contents('http://'.$nginx_server.':1930/stat', 0, $ctx);
		if ($live_stats_xml == false) {
			continue;
		}
		$live_stats = simplexml_load_string($live_stats_xml);
		foreach ($live_stats->server->application as $app) {
			//echo '<pre>'.print_r($app, true).'</pre>';
			if ($app->name == 'live') {
				if ($app->live->nclients > 0) {
					foreach ($app->live->stream as $stream) {
						//echo '<pre>'.print_r($stream, true).'</pre>';
						//echo '<p>'.$stream->name.'</p>';
						$stream_names[] = array('stream' => trim(''.$stream->name.''), 'server' => 'rtmp://'.$nginx_server.'/live/');
					}
				} else {
					//echo '<p>none!</p>';
				}
			}
		}
	}
	return $stream_names;
}

function getLiveList($uid = 0) {
	// get the list of live streams this user either owns or has access to
	// include akamai streams

	global $akamai_streams;

	if (!is_numeric($uid)) {
	    return false;
	}

	$uid = (int) $uid * 1;

	$query = array();
	$query['$or'] = array();

	$current_nginx_streams = getLiveListFromNginx();

	if (count($current_nginx_streams) > 0) {
		// if provided, these are the streams currently playing. that's useful, and changes things a bit
		$current_streams = array();
		foreach ($current_nginx_streams as $current_nginx_stream) {
			if (preg_match('/^ms(\d+)$/', $current_nginx_stream['stream'], $lid_match) > 0) {
				$current_streams[] = (int) $lid_match[1] * 1;
			} else if (is_numeric($current_nginx_stream['stream']) && $current_nginx_stream['stream'] * 1 > 0) {
				$current_streams[] = (int) $current_nginx_stream['stream'] * 1;
			}
		}
		if (count($current_streams) > 0) {
			$current_streams = array_unique($current_streams);
			$current_streams = array_merge($current_streams, $akamai_streams);
			$query['lid'] = array('$in' => $current_streams);
		} else {
			$query['lid'] = array('$in' => $akamai_streams);
		}
	} else {
		//$query['$or'][] = array('lid' => array('$in' => $akamai_streams));
		$query['lid'] = array('$in' => $akamai_streams);
	}

	$query['$or'][] = array('ow.u' => $uid);

	$user_level = getUserLevel($uid);
	$query['$or'][] = array('ul' => array( '$gte' => $user_level ));

	if ($uid > 0) {
		$user_groups = getUserGroupsOwnership($uid, true);
		$query['$or'][] = array('ow.g' => $user_groups);
	}

	global $mdb;

	$streams = array();

	$get_live = $mdb->live->find($query);
	if ($get_live->count() > 0) {
		$get_live->sort(array('ti' => 1));
		foreach ($get_live as $live) {
			$streams[] = $live;
		}
	}

	return $streams;

}

// get info about a live stream
function getLiveInfo($lid = 0) {

	if (!is_numeric($lid)) {
	    return false;
	}

	$lid = (int) $lid * 1;

	global $mdb;

	$live = $mdb->live->findOne(array('lid' => $lid));
	if (!isset($live)) {
		return false;
	} else {
		return $live;
	}

}

// add a new live stream with a unique ID number
function addNewLiveStream($uid, $title = 'Untitled', $access = 5, $desc = '') {

	if (!is_numeric($uid) || $uid * 1 == 0) {
	    return false;
	}

	$uid = (int) $uid * 1;

	if (isset($access) && is_numeric($access)) {
		$access = (int) $access * 1;
	} else {
		$access = 5;
	}

	global $mdb;

	$get_latest_id = $mdb->increments->findOne(array('w' => 'lid'));
	$id = $get_latest_id['id'];
	$next_highest_id = $id + 1;

	$new_stream = array();
	$new_stream['lid'] = $next_highest_id;
	$new_stream['uid'] = $uid;
	$new_stream['ow'] = array('u' => array($uid));
	$new_stream['ti'] = trim($title);
	$new_stream['ul'] = $access;
	$new_stream['a'] = false;
	$new_stream['tsc'] = time();
	if (isset($desc) && trim($desc) != '') {
		$new_stream['d'] = trim($desc);
	}

	try {
		$new_stream_result = $mdb->live->insert($new_stream, array('w' => 1));
		$increase_id = $mdb->increments->update(array('w' => 'lid'), array('$inc' => array('id' => 1) ), array('w' => 1) );
	} catch(MongoCursorException $e) {
		return false;
	}

	return $next_highest_id;

}
