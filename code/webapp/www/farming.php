<?php

/*

	THE FARM
		cyle gage, emerson college, 2012

*/

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/dbconn_mongo.php');
require_once('/median-webapp/includes/media_functions.php');

$page_uuid = 'farming-page';
$page_title = 'The Farm'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
		<h2>The Farm</h2>
		<p><b>What am I looking at?</b> Well, you're looking at the "farm" of computers Median uses to transcode your videos into different formats. For example, when you upload a video, Median automatically creates a version that is iPhone-compatible. It does that in the farm.</p>
		<p><b>How does it work?</b> The "farmers" listed below are sitting around waiting for something to do. Every 60 seconds they ask Median, "hey, got a job for me?" Those are the jobs in the queue, listed below, which are first come, first served. The farmer is assigned a file, which it then transcodes using HandBrake. When it's done, it gives the new file to Median. If any of that process fails, it's listed here, too.</p>
		<?php
		$num_done = $farmdb->jobs->find(array('o' => 1, 's' => 2, 'hl' => array('$exists' => true)))->count();

		$get_avg = $farmdb->jobs->find(array('o' => 1, 's' => 2, 'hl' => array('$exists' => true)))->sort(array('hl' => 1));
		$average_seconds = 0;
		$avg_total = 0;
		$avg_count = $get_avg->count();
		foreach ($get_avg as $job) {
			$avg_total += $job['hl'];
		}
		unset($job);
		if ($avg_count > 0) {
			$average_seconds = $avg_total/$avg_count;
		}

		$get_top_95th = $farmdb->jobs->find(array('o' => 1, 's' => 2, 'hl' => array('$exists' => true)))->sort( array('hl' => 1) )->limit(round($num_done * 0.95));
		$average_9th_seconds = 0;
		$top_95th_total = 0;
		$top_95th_count = $get_top_95th->count();
		foreach ($get_top_95th as $job) {
			$top_95th_total += $job['hl'];
		}
		unset($job);
		if ($top_95th_count > 0) {
			$average_9th_seconds = $top_95th_total/$top_95th_count;
		}

		// to do average based on mongodb grouping and reducing:
		/*
		$average_keys = array();
		$average_init = array('count' => 0, 'total' => 0);
		$average_reduce = "function(obj, out) { out.count++; out.total += obj.hl; }";
		$average_cond = array('o' => 1, 's' => 2);
		$average_final = "function(out) { out.avg = out.total/out.count }";
		$average_wut = $farmdb->jobs->group($average_keys, $average_init, $average_reduce, array('condition' => $average_cond, 'finalize' => $average_final));
		$average_seconds = $average_wut['retval'][0]['avg'];
		//echo '<pre>'.print_r($average_wut, true).'</pre>';
		*/

		?>
		<p><b>How long does it take?</b> The 95th percentile average time to complete a transcoding job is <?php echo number_format($average_9th_seconds/60, 1); ?> minutes, out of <?php echo $num_done; ?> jobs done. That number is live-updating, so check back to see if it has improved. (I choose a 95th percentile because a very small percent of jobs are videos longer than 30 minutes. The average transcoding time for all jobs is <?php echo number_format($average_seconds/60, 1); ?> minutes.)</p>

		<h4>the farmers</h4>
		<?php
		$farmer_names = array();
		$get_farmers = $farmdb->farmers->find( array('e' => true) )->sort( array('tsh' => -1) );
		if ($get_farmers->count() == 0) {
			echo 'Well, there appear to be no farmers...';
		} else {
			echo '<table>';
			echo '<tr><th>farmer name</th><th>last heartbeat</th></tr>';
			foreach ($get_farmers as $farmer) {
				echo '<tr>';
				echo '<td>'.$farmer['n'].'</td>';
				$seconds_ago = time() - $farmer['tsh'];
				if ($seconds_ago > 600) {
					echo '<td class="error">';
				} else {
					echo '<td>';
				}
				echo date('m.d.Y h:i:s A', $farmer['tsh']).'</td>';
				echo '</tr>';
				$farmer_names[''.$farmer['_id'].''] = $farmer['n'];
			}
			echo '</table>';
		}
		unset($farmer);
		?>

		<h4>farming jobs currently being working on - sorted by time last updated</h4>
		<?php
		$get_working_on = $farmdb->jobs->find( array('s' => 1, 'o' => 1) )->sort( array('tsu' => -1) );
		if ($get_working_on->count() == 0) {
			echo '<p>Looks like nobody is working on anything. That\'s good I guess.</p>';
		} else {
			echo '<table>';
			echo '<tr><th>median id</th><th>farmer</th><th>quality</th><th>created</th><th>updated</th></tr>';
			foreach ($get_working_on as $job) {
				echo '<tr>';
				echo '<td>'.$job['mid'].'</td>';
				echo '<td>'.$farmer_names[''.$job['fid'].''].'</td>';
				echo '<td>'.bitrateToFriendly($job['vb'] + $job['ab']).'</td>';
				echo '<td>'.date('m.d.Y h:i:s A', $job['tsc']).'</td>';
				echo '<td>'.date('m.d.Y h:i:s A', $job['tsu']).'</td>';
				echo '</tr>';
			}
			unset($job);
			echo '</table>';
		}
		?>

		<h4>farming jobs in the queue - first come, first served</h4>
		<?php
		$get_queue = $farmdb->jobs->find( array('s' => 0, 'o' => 1) )->sort( array('tsc' => 1) );
		if ($get_queue->count() == 0) {
			echo '<p>Nothing in the queue.</p>';
		} else {
			echo '<table>';
			echo '<tr><th>median id</th><th>quality</th><th>created</th><th>updated</th></tr>';
			foreach ($get_queue as $job) {
				echo '<tr>';
				echo '<td>'.$job['mid'].'</td>';
				echo '<td>'.bitrateToFriendly($job['vb'] + $job['ab']).'</td>';
				echo '<td>'.date('m.d.Y h:i:s A', $job['tsc']).'</td>';
				echo '<td>'.((isset($job['tsu'])) ? date('m.d.Y h:i:s A', $job['tsu']) : '').'</td>';
				echo '</tr>';
			}
			unset($job);
			echo '</table>';
		}
		?>

		<h4>farming jobs that have error'd out</h4>
		<?php
		$get_errord = $farmdb->jobs->find( array('s' => 3, 'o' => 1) )->sort( array('tsc' => -1) );
		if ($get_errord->count() == 0) {
			echo '<p>Good, nothing has failed because of an error.</p>';
		} else {
			echo '<table>';
			echo '<tr><th>median id</th><th>farmer</th><th>quality</th><th>message</th><th>created</th><th>updated</th></tr>';
			foreach ($get_errord as $job) {
				echo '<tr>';
				echo '<td>'.$job['mid'].'</td>';
				echo '<td>'.$farmer_names[''.$job['fid'].''].'</td>';
				echo '<td>'.bitrateToFriendly($job['vb'] + $job['ab']).'</td>';
				echo '<td>'.((isset($job['m']) && trim($job['m']) != '') ? $job['m'] : 'Unknown error.').'</td>';
				echo '<td>'.date('m.d.Y h:i:s A', $job['tsc']).'</td>';
				echo '<td>'.date('m.d.Y h:i:s A', $job['tsu']).'</td>';
				echo '</tr>';
			}
			unset($job);
			echo '</table>';
		}
		?>

		<h4>the 20 latest finished farming jobs</h4>
		<?php
		$get_finished = $farmdb->jobs->find( array('s' => 2, 'o' => 1) )->sort( array('tsu' => -1) )->limit(20);
		if ($get_finished->count() == 0) {
			echo '<p>Nothing done yet.</p>';
		} else {
			echo '<table>';
			echo '<tr><th>median id</th><th>farmer</th><th>quality</th><th>created</th><th>updated</th><th>it took...</th></tr>';
			foreach ($get_finished as $job) {
				echo '<tr>';
				echo '<td>'.$job['mid'].'</td>';
				echo '<td>'.$farmer_names[''.$job['fid'].''].'</td>';
				echo '<td>'.bitrateToFriendly($job['vb'] + $job['ab']).'</td>';
				echo '<td>'.date('m.d.Y h:i:s A', $job['tsc']).'</td>';
				echo '<td>'.date('m.d.Y h:i:s A', $job['tsu']).'</td>';
				echo '<td>'.number_format($job['hl']/60, 1).' minutes</td>';
				echo '</tr>';
			}
			unset($job);
			echo '</table>';
		}
		?>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
