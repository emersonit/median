<?php

// report on median usage/filespace over time

die('needs to be reworked for median 6 and the file API');

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

require_once('/median-webapp/includes/dbconn_mongo.php');

?><!doctype html>
<html>
<head>
<title>Median Usage Over Time</title>
<style type="text/css">
table td, table th {
	padding: 4px;
	margin: 0;
}
table th {
	text-align: left;
	border-bottom: 3px solid black;
}
table td {
	border-bottom: 1px solid #ccc;
	border-right: 1px solid #ccc;
}
</style>
</head>
<body>
<?php

// report that shows either
//  1. new entries per X (hour, day, etc)
//  2. disk space used per X (hour, day, etc)

$type = 'r'; // r = relative, a = absolute
$action = 'entries';
$number = 1;
$unit = 'hours';

if (isset($_GET['t']) && trim($_GET['t']) != '') {
	$type = strtolower(trim($_GET['t']));
}

if (isset($_GET['w']) && trim($_GET['w']) != '') {
	$action = strtolower(trim($_GET['w']));
}

if (isset($_GET['u']) && trim($_GET['u']) != '') {
	$unit = strtolower(trim($_GET['u']));
}

?>
<div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
<p>This "relative range" filter is limited to 24 days/hours, for speed.</p>
<input type="hidden" name="t" value="r" />
View <select name="w"><option value="entries" <?php if ($action == 'entries') { echo 'selected="selected"'; } ?>>Entries</option><option value="size" <?php if ($action == 'size') { echo 'selected="selected"'; } ?>>Filesize</option></select> over the last <input type="number" min="1" max="24" name="n" value="<?php echo $number; ?>" /> <select name="u"><option value="hour" <?php if ($unit == 'hour') { echo 'selected="selected"'; } ?>>Hour(s)</option><option value="day" <?php if ($unit == 'day') { echo 'selected="selected"'; } ?>>Day(s)</option></select> <input type="submit" value="view &raquo;" />
</form>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
<p>This "absolute range" filter is not limited in any way. Might take awhile for the report to finish.</p>
<input type="hidden" name="t" value="a" />
View <select name="w"><option value="entries" <?php if ($action == 'entries') { echo 'selected="selected"'; } ?>>Entries</option><option value="size" <?php if ($action == 'size') { echo 'selected="selected"'; } ?>>Filesize</option></select> from <input type="datetime" name="st" /> to <input type="datetime" name="et" /> per <select name="u"><option value="hour" <?php if ($unit == 'hour') { echo 'selected="selected"'; } ?>>Hour(s)</option><option value="day" <?php if ($unit == 'day') { echo 'selected="selected"'; } ?>>Day(s)</option></select> <input type="submit" value="view &raquo;" />
</form>
</div>
<div>
<?php

$type = strtolower(trim($_GET['t']));

if ($type == 'r') {

	if (isset($_GET['n']) && trim($_GET['n']) != '' && is_numeric($_GET['n'])) {
		$number = (int) $_GET['n'] * 1;
	}

	if ($number < 1 || $number > 24) {
		$number = 1;
	}

	if (!in_array($unit, array('hour', 'day') )) {
		$unit = 'hour';
	}

	echo '<p>'.$action.' / '.$number.' / per '.$unit.'(s)</p>';

	$start_timestamp = strtotime('-'.$number.' '.$unit);
	$end_timestamp = time();

} else if ($type == 'a') {

	$start_timestamp = strtotime('-1 day');
	$end_timestamp = time();

	if (isset($_GET['st']) && trim($_GET['st']) != '') {
		$start_timestamp = strtotime(trim($_GET['st']));
	}

	if (isset($_GET['et']) && trim($_GET['et']) != '') {
		$end_timestamp = strtotime(trim($_GET['et']));
	}

	if (!in_array($unit, array('hour', 'day') )) {
		$unit = 'hour';
	}

	echo '<p>'.$action.' / '.date('Y-m-d h:i A', $start_timestamp).' to '.date('Y-m-d h:i A', $end_timestamp).' / per '.$unit.'(s)</p>';

}

$the_data = array();

if ($action == 'entries') {

	$total_count = 0;
	$total_steps = 0;

	// make a graph of # of entries per-unit, eg # of entries per hour over X hours

	echo '<table>'."\n";
	echo '<tr><th>From</th><th>To</th><th># of Entries</th></tr>'."\n";

	if ($unit == 'hour') {

		// step through the last X hours

		for ($now = $start_timestamp; $now <= $end_timestamp - 3600; $now += 3600) {
			//echo '<p>'.date('Y-m-d h:i', $now).'</p>';

			$get_entries = $mdb->media->find( array( 'tsc' => array( '$gte'  => $now, '$lt' => $now + 3600 ) ) )->sort( array('tsc' => -1) );
			echo '<tr><td>'.date('Y-m-d h:i A', $now).'</td><td>'.date('Y-m-d h:i A', $now + 3600).'</td><td>'.$get_entries->count().'</td></tr>'."\n";
			$the_data[] = array( 'label' => (date('Y-m-d h:i A', $now).' to '.date('Y-m-d h:i A', $now + 3600)), 'data' => $get_entries->count() );
			$total_count += $get_entries->count();
			$total_steps++;

		}

	} else if ($unit == 'day') {

		// step through the last X days

		for ($now = $start_timestamp; $now <= $end_timestamp - 86400; $now += 86400) {
			//echo '<p>'.date('Y-m-d h:i', $now).'</p>';

			$get_entries = $mdb->media->find( array( 'tsc' => array( '$gte'  => $now, '$lt' => $now + 86400 ) ) )->sort( array('tsc' => -1) );
			echo '<tr><td>'.date('Y-m-d h:i A', $now).'</td><td>'.date('Y-m-d h:i A', $now + 86400).'</td><td>'.$get_entries->count().'</td></tr>'."\n";
			$the_data[] = array( 'label' => (date('Y-m-d h:i A', $now).' to '.date('Y-m-d h:i A', $now + 86400)), 'data' => $get_entries->count() );
			$total_count += $get_entries->count();
			$total_steps++;

		}

	}

	echo '<tr><td>Total count:</td><td></td><td>'.$total_count.'</td></tr>'."\n";
	echo '<tr><td>Average:</td><td></td><td>'.number_format(($total_count/$total_steps), 2).' per '.$unit.'</td></tr>'."\n";

	echo '</table>'."\n";

} else if ($action == 'size') {

	$total_filesize = 0;
	$total_steps = 0;

	// make a graph of filesize of entries per-unit, eg # of MB per hour over X hours

	echo '<table>'."\n";
	echo '<tr><th>From</th><th>To</th><th>Filesize of Entries</th></tr>'."\n";

	if ($unit == 'hour') {

		// step through the last X hours

		for ($now = $start_timestamp; $now <= $end_timestamp - 3600; $now += 3600) {
			//echo '<p>'.date('Y-m-d h:i', $now).'</p>';

			$total_megabytes_this_time = 0;

			$get_entries = $mdb->media->find( array( 'tsc' => array( '$gte'  => $now, '$lt' => $now + 3600 ) ) )->sort( array('tsc' => -1) );

			foreach ($get_entries as $entry) {
				//echo '<pre>'.print_r($entry, true).'</pre>';
				if ($entry['mt'] == 'video') {
					if (file_exists($entry['pa']['in'])) {
						$total_megabytes_this_time += (filesize($entry['pa']['in'])/1024/1024);
					}
					foreach ($entry['pa']['c'] as $path) {
						$total_megabytes_this_time += ($path['fs']/1024/1024);
					}
				} else if ($entry['mt'] == 'audio') {
					$total_megabytes_this_time += ($entry['pa']['c']['fs']/1024/1024);
				} else if ($entry['mt'] == 'image') {
					$total_megabytes_this_time += ($entry['pa']['fs']/1024/1024);
				} else if ($entry['mt'] == 'doc') {
					$total_megabytes_this_time += ($entry['pa']['fs']/1024/1024);
				}
			}

			//echo '<tr><td>'.date('Y-m-d h:i A', $now).'</td><td>'.date('Y-m-d h:i A', $now + 3600).'</td><td>'.number_format($total_megabytes_this_time, 2).'MB / '.number_format($total_megabytes_this_time/1024, 2).' GB</td></tr>'."\n";
			echo '<tr><td>'.date('Y-m-d h:i A', $now).'</td><td>'.date('Y-m-d h:i A', $now + 3600).'</td><td>'.number_format($total_megabytes_this_time/1024, 2).' GB</td></tr>'."\n";

			$the_data[] = array( 'label' => (date('Y-m-d h:i A', $now).' to '.date('Y-m-d h:i A', $now + 3600)), 'data' => number_format($total_megabytes_this_time/1024, 2) );
			$total_filesize_mb += $total_megabytes_this_time;

			$total_steps++;

		}

	} else if ($unit == 'day') {

		// step through the last X days

		for ($now = $start_timestamp; $now <= $end_timestamp - 86400; $now += 86400) {
			//echo '<p>'.date('Y-m-d h:i', $now).'</p>';

			$total_megabytes_this_time = 0;

			$get_entries = $mdb->media->find( array( 'tsc' => array( '$gte'  => $now, '$lt' => $now + 86400 ) ) )->sort( array('tsc' => -1) );

			foreach ($get_entries as $entry) {
				//echo '<pre>'.print_r($entry, true).'</pre>';
				if ($entry['mt'] == 'video') {
					if (file_exists($entry['pa']['in'])) {
						$total_megabytes_this_time += (filesize($entry['pa']['in'])/1024/1024);
					}
					foreach ($entry['pa']['c'] as $path) {
						$total_megabytes_this_time += ($path['fs']/1024/1024);
					}
				} else if ($entry['mt'] == 'audio') {
					$total_megabytes_this_time += ($entry['pa']['c']['fs']/1024/1024);
				} else if ($entry['mt'] == 'image') {
					$total_megabytes_this_time += ($entry['pa']['fs']/1024/1024);
				} else if ($entry['mt'] == 'doc') {
					$total_megabytes_this_time += ($entry['pa']['fs']/1024/1024);
				}
			}

			//echo '<tr><td>'.date('Y-m-d h:i A', $now).'</td><td>'.date('Y-m-d h:i A', $now + 86400).'</td><td>'.number_format($total_megabytes_this_time, 2).'MB / '.number_format($total_megabytes_this_time/1024, 2).' GB</td></tr>'."\n";
			echo '<tr><td>'.date('Y-m-d h:i A', $now).'</td><td>'.date('Y-m-d h:i A', $now + 86400).'</td><td>'.number_format($total_megabytes_this_time/1024, 2).' GB</td></tr>'."\n";
			$the_data[] = array( 'label' => (date('Y-m-d h:i A', $now).' to '.date('Y-m-d h:i A', $now + 86400)), 'data' => number_format($total_megabytes_this_time/1024, 2) );
			$total_filesize_mb += $total_megabytes_this_time;

			$total_steps++;

		}

	}

	echo '<tr><td>Total GB:</td><td></td><td>'.number_format($total_filesize_mb/1024, 2).' GB</td></tr>';
	echo '<tr><td>Average:</td><td></td><td>'.number_format((($total_filesize_mb/1024)/$total_steps), 2).' GB per '.$unit.'</td></tr>';

	echo '</table>'."\n";

} else {
	echo 'uhhh';
}




/*
echo '<pre>'."\n";
foreach ($get_entries as $entry) {
	echo $entry['mid']."\n";
	//print_r($entry['pa']);
	echo "\n";
}
echo '</pre>'."\n";
*/

?>
</div>
<div id="chart_div" style="width: 700px; height: 400px;"></div>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
var data = google.visualization.arrayToDataTable([

<?php

if ($action == 'entries') {
	echo '[\'Time\', \'Entries\'],'."\n";
} else if ($action == 'size') {
	echo '[\'Time\', \'GB\'],'."\n";
}

foreach ($the_data as $data_point) {
	echo '[\''.$data_point['label'].'\', '.$data_point['data'].'],'."\n";
}

?>
/* example:

['Year', 'Sales', 'Expenses'],
['2004',  1000,      400],
['2005',  1170,      460],
['2006',  660,       1120],
['2007',  1030,      540]
*/

]);

var options = {
	title: 'Stuff Over Time, oh...',
	legend: { position: "top" },
};

var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
chart.draw(data, options);
}
</script>
</body>
</html>