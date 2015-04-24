<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH FARMING
	(specifically tailored for the file API)
	
		introduced for median 5
        continued use in median 6
		cyle gage, emerson college, 2012-2014


	getFarmingStatus($mid) -- done
	getFarmingStatusByOutPath($path) -- done
	addFarmingJob($mid, $paths, $options) -- done

*/

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/dbconn_mongo.php');

// get the status of a median entry's farming jobs
function getFarmingStatus($mid = 0) {
	// get full status of all media being transcoded for the MID

	if (!isset($mid) || !is_numeric($mid) || $mid < 1) {
		return false;
	}

	$mid = (int) $mid * 1;

	global $m;
	$farmdb = $m->farm;

	$jobs = array();

	$find_jobs = $farmdb->jobs->find(array('mid' => $mid));
	if ($find_jobs->count() > 0) {
		foreach ($find_jobs as $job) {
			$jobs[] = $job;
		}
	}

	return $jobs;

}

// get the status of a farming job, using the eventual path as an index
function getFarmingStatusByOutPath($path = '') {
	// get the latest status of a particular farming job by its OUT path

	if (!isset($path) || trim($path) == '') {
		return false;
	}

	$path = trim($path);

	global $m;
	$farmdb = $m->farm;

	$status = 'Unknown';

	$find_jobs = $farmdb->jobs->find(array('out' => $path));
	if ($find_jobs->count() == 0) {
		// no job found for that out path, whoops
		$status = 'No job found for that path.';
		return $status;
	} else if ($find_jobs->count() == 1) {
		$thejob = $find_jobs->getNext();
	} else {
		// sort by latest, take the first one
		$find_jobs->sort(array('tsu' => -1));
		$thejob = $find_jobs->getNext();
	}

	if (!isset($thejob['s']) || !is_numeric($thejob['s'])) {
		$status = 'No status found for that path.';
		return $status;
	}

	switch ($thejob['s']) {
		case 0:
		$status = 'Pending';
		break;
		case 1:
		$status = 'Transcoding';
		break;
		case 2:
		$status = 'Finished';
		break;
		case 3:
		$status = 'Error';
		break;
		default:
		$status = 'Unknown';
	}

	return $status;
}

// add a new farming job to the queue
function addFarmingJob($mid = 0, $paths = array(), $options = '') {
	// add a new farming job for mid with options...
	// if options is a string, use that as a preset
	// if options is an array, use those explicit settings

	global $tiers;

	if (!isset($mid) || !is_numeric($mid) || $mid < 1) {
		return false;
	}

	$mid = (int) $mid * 1;

	if (!isset($paths) || !is_array($paths)) {
		return false;
	}

	if (!isset($paths['in']) || !isset($paths['out'])) {
		return false;
	}

	$transcode_options = array();

	$tier_keys = array_keys($tiers);

	if (isset($options) && is_string($options) && in_array(strtolower($options), $tier_keys))  {
		// use the provided preset option
		$transcode_options = $tiers[strtolower($options)];
	} else if (isset($options) && is_array($options)) {
		$transcode_options = $options;
	} else {
		return false;
	}

	global $m;
	$farmdb = $m->farm;

	$new_job = array();
	$new_job['mid'] = $mid;
	$new_job['p'] = 1; // priority 1 for median jobs
	$new_job['o'] = 1; // origin id #1 for median
	$new_job['s'] = 0; // status of 0 for new jobs
	$new_job['fid'] = 0; // unknown farmer ID as yet
	$new_job['in'] = trim($paths['in']);
	$new_job['out'] = trim($paths['out']);
	$new_job['vw'] = (int) $transcode_options['vw'] * 1;
	$new_job['vh'] = (int) $transcode_options['vh'] * 1;
	$new_job['vb'] = (int) $transcode_options['vb'] * 1;
	$new_job['ab'] = (int) $transcode_options['ab'] * 1;
	$new_job['tsc'] = time();
	$new_job['tsu'] = time();

	// ok, add row
	try {
		$result = $farmdb->jobs->insert($new_job, array('w' => 1));
	} catch(MongoCursorException $e) {
		return false;
	}

	return true;

}
