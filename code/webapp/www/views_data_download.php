<?php

/*

	based on MID provided, go through views and put them in CSV format

*/

if (!isset($_GET['mid']) || !is_numeric($_GET['mid'])) {
	die('Error: no MID provided');
}

$mid = (int) $_GET['mid'] * 1;

require_once('/median-webapp/includes/dbconn_mongo.php');

$media_info = $mdb->media->findOne( array('mid' => $mid), array( 'tsc' => 1 ) );

$file_so_far = '';
$filename = 'median_'.$mid.'_views.csv';

$when_to_begin = $media_info['tsc'] * 1;
$when_to_stop = time();

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

$views = $mdb->views->find( array('mid' => $mid) )->sort( array( 'ts' => 1 ) );

foreach ($views as $view) {
	$total_view_days[date('Y-m-d', $view['ts'])] += 1;
	if (isset($view['em']) && $view['em'] == true) {
		$embed_view_days[date('Y-m-d', $view['ts'])] += 1;
	}
}

$file_so_far = 'date,total views,embed views'."\r\n";

foreach ($total_view_days as $the_date => $the_count) {
	$file_so_far .= $the_date.','.$the_count.','.$embed_view_days[$the_date]."\r\n";
}

header('Content-type: text/plain'); // it's a plain text file!
header('Content-Length: '.strlen($file_so_far)); // the size is based on how many characters there are
header("Content-Disposition: attachment; filename=\"" . $filename . '"' ); // ok, so download it

echo $file_so_far; // echo out the contents of the file to be downloaded