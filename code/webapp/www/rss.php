<?php

// rss feed generator for things not media-related

// for media RSS feeds, see API script

/*

	feeds:

		- news
		- new publicly visible...
			- categories
			- events
			- groups

*/

if (!isset($_GET['w']) || trim($_GET['w']) == '') {
	die('No route given to make an RSS feed out of.');
}

$what = strtolower(trim($_GET['w']));

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/dbconn_mongo.php');
require_once('/median-webapp/includes/meta_functions.php');

$baseXML = '<?xml version="1.0"?>
<rss version="2.0">
<channel>
<title>Median</title>
<link>'.$median_base_url.'</link>
<description>Median is a media content storage and delivery system.</description>
<language>en-us</language>
<pubDate></pubDate>
</channel>
</rss>';
$root = simplexml_load_string($baseXML);
$channel = $root->channel;
$xml_pubDate = date(DATE_RFC822);
$channel->pubDate = $xml_pubDate;

if ($what == 'news') {

	$channel->title = 'Median News';

	$news = getNews(10);

	foreach ($news as $news_item) {
		$item = $channel->addChild('item');
		$pieces = explode(" ", $news_item['c']);
		$first_part = implode(" ", array_splice($pieces, 0, 3));
		$item->addChild('title', htmlentities($first_part.'...', ENT_QUOTES));
		$item->addChild('description', htmlentities($news_item['c'], ENT_QUOTES));
		$item->addChild('link', htmlentities($median_base_url));
		$item->addChild('guid', ''.$news_item['_id'].'');
		$item->addChild('pubDate', date(DATE_RFC822, $news_item['tsc']));
	}

} else if ($what == 'categories') {

	$channel->title = 'Median - New Public Categories';

	$show_only = array('id' => true, 'ti' => true, 'de' => true, 'ts' => true);
	$find_stuff_query = array('w' => 'cat', 'ul_v' => array('$gte' => 6));
	$get_stuff = $mdb->meta->find($find_stuff_query, $show_only);
	$get_stuff->sort(array('ts' => -1));
	$get_stuff->limit(20);
	foreach ($get_stuff as $stuff) {
		$item = $channel->addChild('item');
		$item->addChild('title', htmlentities($stuff['ti'], ENT_QUOTES));
		$description = '';
		if (isset($stuff['de']) && trim($stuff['de']) != '') {
			$description = trim($stuff['de']);
		}
		$item->addChild('description', htmlentities($description, ENT_QUOTES));
		$item->addChild('link', htmlentities($median_base_url.'category/'.$stuff['id'].'/'));
		$item->addChild('guid', ''.$stuff['_id'].'');
		$item->addChild('pubDate', date(DATE_RFC822, $stuff['ts']));
	}

} else if ($what == 'events') {

	$channel->title = 'Median - New Public Events';

	$show_only = array('id' => true, 'ti' => true, 'de' => true, 'ts' => true);
	$find_stuff_query = array('w' => 'event', 'ul_v' => array('$gte' => 6));
	$get_stuff = $mdb->meta->find($find_stuff_query, $show_only);
	$get_stuff->sort(array('ts' => -1));
	$get_stuff->limit(20);
	foreach ($get_stuff as $stuff) {
		$item = $channel->addChild('item');
		$item->addChild('title', htmlentities($stuff['ti'], ENT_QUOTES));
		$description = '';
		if (isset($stuff['de']) && trim($stuff['de']) != '') {
			$description = trim($stuff['de']);
		}
		$item->addChild('description', htmlentities($description, ENT_QUOTES));
		$item->addChild('link', htmlentities($median_base_url.'event/'.$stuff['id'].'/'));
		$item->addChild('guid', ''.$stuff['_id'].'');
		$item->addChild('pubDate', date(DATE_RFC822, $stuff['ts']));
	}

} else if ($what == 'groups') {

	$channel->title = 'Median - New Public Groups';

	$get_groups = $mdb->groups->find(array('v' => array('$gte' => 6) ));
	$get_groups->sort( array('ts' => -1) );
	$get_groups->limit(20);
	foreach ($get_groups as $group) {
		$item = $channel->addChild('item');
		$item->addChild('title', htmlentities($group['n'], ENT_QUOTES));
		$description = '';
		if (isset($group['d']) && trim($group['d']) != '') {
			$description = trim($group['d']);
		}
		$item->addChild('description', htmlentities($description, ENT_QUOTES));
		$item->addChild('link', htmlentities($median_base_url.'group/'.$group['gid'].'/'));
		$item->addChild('guid', ''.$group['_id'].'');
		$item->addChild('pubDate', date(DATE_RFC822, $group['ts']));
	}

} else {
	die('Invalid route provided.');
}

echo $root->asXML();
