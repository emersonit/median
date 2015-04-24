<?php

// subscriptions and stuff!

require_once('/median-webapp/includes/login_check.php');
require_once('/median-webapp/includes/media_functions.php'); // includes user_functions.php
require_once('/median-webapp/includes/activity_functions.php');
require_once('/median-webapp/includes/common_functions.php');

$days = 4;

$activity_feed = getActivityForUser($current_user['userid'], $days);

if (count($activity_feed) == 0 || $activity_feed == false) {
	echo '<p>It looks like there has not been any activity over the last '.$days.' day'.plural($days).' to display.</p>';
	die();
}

foreach ($activity_feed as $feed_item) {
	//echo '<!--';
	//print_r($feed_item);
	//echo '-->';

	$why = $feed_item['why'];
	$type = $feed_item['t'];
	$data_type = '';
	$data_id = '';

	switch ($why) {
		case 'yourmedia':
		case 'watchgroup':
		case 'watchuser':
		case 'watchcat':
		case 'watchevent':
		$data_type = 'media';
		$data_id = $feed_item['mid'];
		break;
		case 'yourclass':
		$data_type = 'class';
		$data_id = $feed_item['clid']['c'];
		break;
		case 'yourcat':
		$data_type = 'category';
		$data_id = $feed_item['cid'];
		break;
		case 'yourevent':
		case 'watchevents':
		$data_type = 'event';
		$data_id = $feed_item['eid'];
		break;
		case 'yourgroup':
		case 'watchgroups':
		$data_type = 'group';
		$data_id = $feed_item['gid'];
		break;
	}

	echo '<div class="row '.$why.' entry activity-feed-item" data-type="'.$data_type.'" data-id="'.$data_id.'">';
	echo '<div class="relative-time">'.getRelativeTime($feed_item['ts']).'</div>';
	if ($type == 'newcomment' || $type == 'newmid' || $type == 'encoded') { // show new media entry or new comment or encoded!
		// get the media id's thumbnail!
		$mid = $feed_item['mid'];
		$thumbs = getThumbnails($mid);
		if ($thumbs != false && isset($thumbs['small'])) {
			echo '<div class="thumb"><img src="'.$thumbs['small'].'" /></div>';
		}
		echo '<div class="feed-info">';
		switch ($why) {
			case 'yourmedia':
			if ($type == 'encoded') {
				echo '<p>The '.$feed_item['b'].'kbps version of <a href="/media/'.$mid.'/">'.getMediaTitle($mid).'</a> is done!</p>';
			} else if ($type == 'newcomment') {
				echo '<p>You have a new comment on your media entry <a href="/media/'.$mid.'/">'.getMediaTitle($mid).'</a>.</p>';
			}
			break;
			case 'yourclass':
			$the_class_code = $feed_item['clid']['c'];
			echo '<p>A new media entry, <a href="/media/'.$mid.'/">'.getMediaTitle($mid).'</a>, has been added to <a href="/class/'.$the_class_code.'/">'.$the_class_code.'</a>.</p>';
			break;
			case 'watchgroup':
			case 'yourgroup':
			$the_gid = ((isset($feed_item['watchfor'])) ? $feed_item['watchfor'] : $feed_item['gid']);
			$group_info = getGroupInfo($the_gid);
			echo '<p>A new media entry, <a href="/media/'.$mid.'/">'.getMediaTitle($mid).'</a>, has been added to the group <a href="/group/'.$the_gid.'/">'.$group_info['n'].'</a>.</p>';
			break;
			case 'watchuser':
			$the_uid = $feed_item['watchfor'];
			echo '<p>A new media entry, <a href="/media/'.$mid.'/">'.getMediaTitle($mid).'</a>, has been added to user <a href="/user/'.$the_uid.'/">'.getUserName($the_uid).'</a>.</p>';
			break;
			case 'watchcat':
			case 'yourcat':
			$the_cid = ((isset($feed_item['watchfor'])) ? $feed_item['watchfor'] : $feed_item['cid']);
			$cat_info = getCategoryInfo($the_cid);
			echo '<p>A new media entry, <a href="/media/'.$mid.'/">'.getMediaTitle($mid).'</a>, has been added to the category <a href="/category/'.$the_cid.'/">'.$cat_info['ti'].'</a>.</p>';
			break;
			case 'watchevent':
			case 'yourevent':
			$the_eid = ((isset($feed_item['watchfor'])) ? $feed_item['watchfor'] : $feed_item['eid']);
			$event_info = getEventInfo($the_eid);
			echo '<p>A new media entry, <a href="/media/'.$mid.'/">'.getMediaTitle($mid).'</a>, has been added to the event <a href="/event/'.$the_eid.'/">'.$event_info['ti'].'</a>.</p>';
			break;
		}
		echo '</div>';
	} else if ($type == 'newpost') { // show new post
		echo '<div class="feed-info">';
		switch ($why) {
			case 'yourclass':
			$the_class_code = $feed_item['clid']['c'];
			echo '<p>A new thread post was added to <a href="/class/'.$the_class_code.'/">'.$the_class_code.'</a>.</p>';
			break;
			case 'yourcat':
			$the_cid = $feed_item['cid'];
			$cat_info = getCategoryInfo($the_cid);
			echo '<p>A new thread post was added to your category <a href="/category/'.$the_cid.'/">'.$cat_info['ti'].'</a>.</p>';
			break;
			case 'yourevent':
			$the_eid = $feed_item['eid'];
			$event_info = getEventInfo($the_eid);
			echo '<p>A new thread post was added to your event <a href="/event/'.$the_eid.'/">'.$event_info['ti'].'</a>.</p>';
			break;
			case 'yourgroup':
			$the_gid = $feed_item['gid'];
			$group_info = getGroupInfo($the_gid);
			echo '<p>A new thread post was added to your group <a href="/group/'.$the_gid.'/">'.$group_info['n'].'</a>.</p>';
			break;
		}
		echo '</div>';
	} else if ($type == 'newgroup') {
		echo '<div class="feed-info">';
		$the_gid = $feed_item['gid'];
		$group_info = getGroupInfo($the_gid);
		echo '<p>A new group has been created: <a href="/group/'.$the_gid.'/">'.$group_info['n'].'</a>.</p>';
		echo '</div>';
	} else if ($type == 'newevent') {
		echo '<div class="feed-info">';
		$the_eid = $feed_item['eid'];
		$event_info = getEventInfo($the_eid);
		echo '<p>A new event has been created: <a href="/event/'.$the_eid.'/">'.$event_info['ti'].'</a>.</p>';
		echo '</div>';
	}
	echo '</div>';

}

echo '<p>(There is a 20 item maximum on this list.)</p>';

?>