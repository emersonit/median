<?php

/*

	based on MID provided, go through views and list out dates and views per day

*/

if (!isset($_GET['mid']) || !is_numeric($_GET['mid'])) {
	die(json_encode(array('error' => 'no MID provided')));
}

$mid = (int) $_GET['mid'] * 1;

require_once('/median-webapp/includes/dbconn_mongo.php');

// by default, show the last week
$when_to_begin = strtotime('-1 week');
$when_to_stop = time();

// if the user has used the controls, change the data
if (isset($_POST['t']) && trim($_POST['t']) != '') {
	if (trim($_POST['t']) == 'r') {
		$unit_amount = (int) $_POST['h'] * 1;
		$unit_type = strtolower(trim($_POST['u']));
		$acceptable_units = array('days', 'weeks', 'months');
		if ($unit_amount > 0 && in_array($unit_type, $acceptable_units)) {
			$when_to_begin = strtotime('-'.$unit_amount.' '.$unit_type);
		}
	} else if (trim($_POST['t']) == 'a') {
		$date_start = strtotime(trim($_POST['s']) . ' 12:00AM');
		$date_end = strtotime(trim($_POST['e']) . ' 11:59PM');
		if ($date_start != false && $date_start > 0 && $date_end != false && $date_end > 0) {
			$when_to_begin = $date_start;
			$when_to_stop = $date_end;
		}
	}
}

$total_view_days = array();
$embed_view_days = array();

$when_so_far = $when_to_begin;
while (date('Y-m-d', $when_so_far) != date('Y-m-d', $when_to_stop)) {
	$total_view_days[date('Y-m-d', $when_so_far)] = 0;
	$embed_view_days[date('Y-m-d', $when_so_far)] = 0;
	$when_so_far += 60 * 60 * 24;
}

$total_view_days[date('Y-m-d', $when_so_far)] = 0;
$embed_view_days[date('Y-m-d', $when_so_far)] = 0;

$the_data = array(); // all the data series

$the_data[0] = array( // this will be used for TOTAL VIEWS
	'label' => 'total views',
	'color' => '#333333',
	'hoverable' => true,
	'clickable' => false,
	'data' => array(
	/*
		array(1366866000000, 0), // <--- format: timestamp * 1000 and the number of views
		array(1366952400000, 1),
		array(1367038800000, 2)
	*/
	)
);

$the_data[1] = array( // this will be used for just EMBED VIEWS
	'label' => 'views from embeds',
	'color' => '#ff0000',
	'hoverable' => true,
	'clickable' => false,
	'data' => array()
);

// ok now get the data from mongodb

$views = $mdb->views->find( array('mid' => $mid, '$and' => array( array('ts' => array('$gt' => $when_to_begin)), array('ts' => array('$lt' => $when_to_stop) )) ) )->sort( array( 'ts' => 1 ) );

foreach ($views as $view) {
	$total_view_days[date('Y-m-d', $view['ts'])] += 1;
	if (isset($view['em']) && $view['em'] == true) {
		$embed_view_days[date('Y-m-d', $view['ts'])] += 1;
	}
}

// now fit it into the data series format

foreach ($total_view_days as $the_date => $the_views) {
	$the_data[0]['data'][] = array( strtotime($the_date) * 1000, $the_views * 1 );
}

foreach ($embed_view_days as $the_date => $the_views) {
	$the_data[1]['data'][] = array( strtotime($the_date) * 1000, $the_views * 1 );
}

echo json_encode($the_data);
