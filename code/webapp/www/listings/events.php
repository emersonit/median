<?php

// get event list

$login_required = false;
require_once('/median-webapp/includes/login_check.php');
require_once('/median-webapp/includes/meta_functions.php');

$events = getEvents($current_user['userid']);

//echo '<!-- <pre>'.print_r($events, true).'</pre> -->';

if ($current_user['loggedin']) {
	echo '<div class="panel">';
	echo '<div class="rss"><a href="/rss/events/"><img src="/images/icons/rss.png" title="RSS Feed for Latest Public Events" /></a></div>';
	echo '<a href="/new/event/" class="button small">Start a new event &raquo;</a>';
	echo '</div>';
}

if (count($events) == 0) {
	echo '<div class="alert-box alert">Sorry, there are no events to display.</div>';
} else {

	foreach ($events as $event) {

		echo '<div class="event row entry clickable" data-type="event" data-id="'.$event['id'].'">';
		echo '<p class="event-name">'.$event['ti'].'</p>';
		if (isset($event['de'])) {
			echo '<p class="event-description">'.$event['de'].'</p>';
		}
		if ($event['dl'] > 0) {
			$end_time = date('m/d/Y', $event['dl']);
		} else {
			$end_time = 'Never';
		}
		echo '<p>Start: <span class="label radius date">'.date('m/d/Y', $event['sd']).'</span> End: <span class="label radius date">'.$end_time.'</span></p>';
		/*
		if (isset($event['url'])) {
			echo '<p><a href="'.$event['url'].'" target="_blank">Click here for more info &raquo;</a></p>';
		}
		*/
		echo '</div>'."\n";

	}

}

?>