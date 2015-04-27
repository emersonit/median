<?php

// the actual Median API

error_reporting(0);

$script_time_start = microtime(true);

if (!isset($_GET['wut']) || trim($_GET['wut']) == '') {
	die('No query command provided.');
}

$wut_commands = array('list', 'info');
$wut = trim(strtolower($_GET['wut']));

if (!in_array($wut, $wut_commands)) {
	die('Invalid query command.');
}

$is_rss = false;
if (isset($_GET['rss']) && trim($_GET['rss']) == 'yup') {
	$is_rss = true;
}

function bailout($message = '', $format = 'xml') {
	if ($format == 'json') {
		echo json_encode(array('error' => $message));
		die();
	} else if ($format == 'xml') {
		$doc = new DomDocument('1.0');
		$doc->formatOutput = true;
		$root = $doc->createElement('error');
		$root = $doc->appendChild($root);
		$keyvalue = $doc->createCDATASection($message);
		$keyvalue = $root->appendChild($keyvalue);
		$xml_string = $doc->saveXML();
		echo $xml_string;
		die();
	} else {
		die($message);
	}
}

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/dbconn_mongo.php');

// defaults...
$apikey = '0';
$ul = 6;
$can_access_fms_paths = false;
$can_access_html5_paths = false;

if (isset($_GET['apikey']) && trim($_GET['apikey']) != '') {
	$apikey = trim($_GET['apikey']);
	$api_entry = $mdb->api->findOne(array('key' => $apikey, 'e' => true));
	if (isset($api_entry)) {
		if (isset($api_entry['ul'])) {
			$ul = $api_entry['ul'];
		}
		if (isset($api_entry['a_fms'])) {
			$can_access_fms_paths = $api_entry['a_fms'];
		}
		if (isset($api_entry['a_raw'])) {
			$can_access_html5_paths = $api_entry['a_raw'];
		}
		$update_api_entry = $mdb->api->update( array('api' => $apikey), array('$set' => array('tsu' => time())) );
	}
}

require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/media_functions.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/group_functions.php');
require_once('/median-webapp/includes/user_functions.php');

$format_types = array('xml', 'json');
if ($is_rss) {
	$return_format = 'rss';
	header('Content-type: application/rss+xml');
} else {
	$return_format = (isset($_GET['format']) && in_array(strtolower(trim($_GET['format'])), $format_types)) ? strtolower(trim($_GET['format'])) : 'xml';
}

if ($wut == 'info') {

	/*

			get info for media entry

	*/

	if (!isset($_GET['mid']) || !is_numeric($_GET['mid'])) {
		bailout('You need to supply a media ID.', $return_format);
	} else {
		$mid = (int) $_GET['mid'] * 1;
	}

	$media_info = getMediaInfo($mid);

	if ($media_info == false) {
		bailout('The media entry with that ID does not exist.', $return_format);
	}

	if (canViewMedia(0, $mid, $ul) !== true) {
		bailout('You do not have permission to view this entry.', $return_format);
	}

	if ($media_info['mt'] == 'clip') {
		bailout('Currently information about clips is not available.', $return_format);
	}

	$all_info = array();

	$all_info['id'] = $media_info['mid'];
	$all_info['comment_count'] = ((isset($media_info['cc'])) ? $media_info['cc'] : 0);
	$all_info['view_count'] = $media_info['vc'];
	$all_info['metatype'] = $media_info['mt'];
	$all_info['duration'] = ((isset($media_info['du'])) ? $media_info['du'] : 0);
	$all_info['groupid'] = $media_info['ul'];
	$all_info['title'] = htmlentities($media_info['ti'], ENT_QUOTES, 'UTF-8');
	if (isset($media_info['th'])) {
		$all_info['thumb'] = ((isset($media_info['th']['s'])) ? substr($median_base_url, 0, -1).str_replace('/thumbs/', '/files/thumb/', $media_info['th']['s']) : $median_base_url.'images/nothumb.jpg');
		$all_info['bigthumb'] = ((isset($media_info['th']['b'])) ? substr($median_base_url, 0, -1).str_replace('/thumbs/', '/files/thumb/', $media_info['th']['b']) : $median_base_url.'images/nothumb.jpg');
	} else {
		$all_info['thumb'] = $median_base_url.'images/nothumb.jpg';
		$all_info['bigthumb'] = $median_base_url.'images/nothumb.jpg';
	}
	$all_info['ts'] = $media_info['tsc'];
	$all_info['url'] = $median_base_url.'media/'.$media_info['mid'].'/';

	// get user owners names (if any)
	if (isset($media_info['ow']['u']) && count($media_info['ow']['u']) > 0) {
		$all_info['user_owners'] = array();
		foreach ($media_info['ow']['u'] as $user_owner) {
			$all_info['user_owners'][] = array('id' => $user_owner, 'name' => getUserName($user_owner));
		}
	}

	// get groups owners names (if any)
	if (isset($media_info['ow']['g']) && count($media_info['ow']['g']) > 0) {
		$all_info['group_owners'] = array();
		foreach ($media_info['ow']['g'] as $group_owner) {
			$group_info = getGroupInfo($group_owner);
			if (is_array($group_info)) {
				$all_info['group_owners'][] = array('id' => $group_owner, 'name' => $group_info['n']);
			}
		}
	}

	// go through metadata and get meta names (if any)
	$all_info['metadata'] = array();
	if (count($media_info['me']) > 0) {
		$meta_fields = getMetaDataList();
		$meta_field_codes = array();
		$meta_field_translate = array();
		foreach ($meta_fields as $field) {
			$meta_field_codes[] = $field['id'];
			$meta_field_translate[$field['id']] = $field['d'];
		}
		unset($field);

		foreach ($media_info['me'] as $selected_metadata_key => $selected_metadata_val) {
			if (in_array($selected_metadata_key, $meta_field_codes)) {
				$all_info['metadata'][] = array('k' => $meta_field_translate[$selected_metadata_key], 'v' => $selected_metadata_val);
			} else {
				$all_info['metadata'][] = array('k' => $selected_metadata_key, 'v' => $selected_metadata_val);
			}
		}
	}

	// add in media paths if allowed
	if ($can_access_fms_paths && ($media_info['mt'] == 'video' || $media_info['mt'] == 'audio')) {
		$all_info['media_paths'] = array();
		if ($media_info['mt'] == 'video') {
			foreach ($media_info['pa']['c'] as $media_path) {
				if (!isset($media_path['e']) || $media_path['e'] == false) {
					continue;
				}
				if ($media_info['mt'] == 'video') {
					$all_info['media_paths'][] = array( 'f' => $media_path['f'], 'b' => $media_path['b'], 'w' => $media_path['w'], 'h' => $media_path['h'] );
				} else if ($media_info['mt'] == 'audio') {
					$all_info['media_paths'][] = array( 'f' => $media_path['f'], 'b' => $media_path['b'] );
				}
			}
		} else if ($media_info['mt'] == 'audio') {
			if (!isset($media_info['pa']['c']['e']) || $media_info['pa']['c']['e'] == false) {
				continue;
			}
			$all_info['media_paths'][] = array( 'f' => $media_info['pa']['c']['f'], 'b' => $media_info['pa']['c']['b'] );
		}

		$all_info['fms_server'] = 'rtmp://odin.emerson.edu/vod';
	}

	// add in HTML5 URL if allowed
	if ($can_access_html5_paths) {
		$all_info['html5'] = $median_base_url.'files/html5/'.$mid.'/';
	}

	if ($return_format == 'json') {

		echo json_encode($all_info);

	} else if ($return_format == 'xml') {

		$doc = new DomDocument('1.0');
		$doc->formatOutput = true;
		$root = $doc->createElement('items');
		$root = $doc->appendChild($root);
		// for loop for each element inside
		$itemchild = $doc->createElement('item');
		$itemchild = $root->appendChild($itemchild);
		foreach ($all_info as $xmlkey => $xmlvalue) {
			if ($xmlkey == 'user_owners') {
				$userschild = $doc->createElement('user_owners');
				$userschild = $itemchild->appendChild($userschild);
				foreach ($all_info['user_owners'] as $uservalue) {
					$keychild = $doc->createElement('user');
					$keychild = $userschild->appendChild($keychild);
					$keychild->setAttribute('id', $uservalue['id']);
					$keyvalue = $doc->createCDATASection($uservalue['name']);
					$keyvalue = $keychild->appendChild($keyvalue);
				}
			} else if ($xmlkey == 'group_owners') {
				$userschild = $doc->createElement('group_owners');
				$userschild = $itemchild->appendChild($userschild);
				foreach ($all_info['group_owners'] as $groupvalue) {
					$keychild = $doc->createElement('group');
					$keychild = $userschild->appendChild($keychild);
					$keychild->setAttribute('id', $groupvalue['id']);
					$keyvalue = $doc->createCDATASection($groupvalue['name']);
					$keyvalue = $keychild->appendChild($keyvalue);
				}
			} else if ($xmlkey == 'media_paths') {
				$mediachild = $doc->createElement('media_paths');
				$mediachild = $itemchild->appendChild($mediachild);
				foreach ($all_info['media_paths'] as $mediavalue) {
					$keychild = $doc->createElement('media');
					$keychild = $mediachild->appendChild($keychild);
					$keychild->setAttribute('bitrate', $mediavalue['b']);
					if (isset($mediavalue['w'])) {
						$keychild->setAttribute('width', $mediavalue['w']);
					}
					if (isset($mediavalue['h'])) {
						$keychild->setAttribute('height', $mediavalue['h']);
					}
					$keyvalue = $doc->createCDATASection($mediavalue['f']);
					$keyvalue = $keychild->appendChild($keyvalue);
				}
			} else if ($xmlkey == 'metadata') {
				$metachild = $doc->createElement('metadata');
				$metachild = $itemchild->appendChild($metachild);
				foreach ($all_info['metadata'] as $meta_piece) {
					// add a container for key and value
					$containerchild = $doc->createElement('item');
					$containerchild = $metachild->appendChild($containerchild);
					// add the key to the container
					$keychild = $doc->createElement('key');
					$keychild = $containerchild->appendChild($keychild);
					$keyvalue = $doc->createCDATASection(htmlentities($meta_piece['k'], ENT_QUOTES, 'UTF-8'));
					$keyvalue = $keychild->appendChild($keyvalue);
					// add the value to the container
					$valchild = $doc->createElement('val');
					$valchild = $containerchild->appendChild($valchild);
					$valvalue = $doc->createCDATASection(htmlentities($meta_piece['v'], ENT_QUOTES, 'UTF-8'));
					$valvalue = $valchild->appendChild($valvalue);
				}
			} else {
				// just nodes
				$keychild = $doc->createElement($xmlkey);
				$keychild = $itemchild->appendChild($keychild);
				$keyvalue = $doc->createCDATASection(htmlentities($xmlvalue, ENT_QUOTES, 'UTF-8'));
				$keyvalue = $keychild->appendChild($keyvalue);
			}
		}

		$xml_string = $doc->saveXML();
		echo $xml_string;

	} else {
		bailout('Invalid result format given.', $return_format);
	}

} else if ($wut == 'list') {

	/*

			get list of media

	*/

	$options = array();
	$page = 1;

	if (isset($_GET['order']) && trim($_GET['order']) != '') {
		$options['sort'] = strtolower(trim($_GET['order']));
	}

	if (isset($_GET['type']) && trim($_GET['type']) != '') {
		$options['filter']['type'] = strtolower(trim($_GET['type']));
	}

	if (isset($_GET['group']) && is_numeric($_GET['group'])) {
		$user_level_filter = (int) $_GET['group'] * 1;
		if ($user_level_filter >= $ul) {
			$options['filter']['ul'] = $user_level_filter;
		} else {
			bailout('You cannot filter with that user level/group ID, sorry.', $return_format);
		}
	}

	if (isset($_GET['page']) && is_numeric($_GET['page'])) {
		$options['page'] = (int) $_GET['page'] * 1;
		$page = $options['page'];
	}

	if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
		$options['howmany'] = (int) $_GET['limit'] * 1;
		if ($options['howmany'] > 40) {
			$options['howmany'] = 40;
		}
	} else {
		$options['howmany'] = 20;
	}

	if (isset($_GET['user']) && trim($_GET['user']) != '') {
		if (is_numeric($_GET['user'])) {
			$options['filter']['uid'] = (int) $_GET['user'] * 1;
		} else {
			$user_id = getUserId(strtolower(trim($_GET['user'])));
			if ($user_id == 0) {
				bailout('There is no user with that name.', $return_format);
			}
			$options['filter']['uid'] = $user_id;
		}
	}

	if (isset($_GET['cid']) && is_numeric($_GET['cid'])) {
		$options['filter']['cid'] = (int) $_GET['cid'] * 1;
		if (!canViewCategory(0, $options['filter']['cid'], $ul)) {
			bailout('Sorry, but you do not have permission to view that category.', $return_format);
		}
	}

	if (isset($_GET['eid']) && is_numeric($_GET['eid'])) {
		$options['filter']['eid'] = (int) $_GET['eid'] * 1;
		if (!canViewEvent(0, $options['filter']['eid'], $ul)) {
			bailout('Sorry, but you do not have permission to view that event.', $return_format);
		}
	}

	if (isset($_GET['gid']) && is_numeric($_GET['gid'])) {
		$options['filter']['gid'] = (int) $_GET['gid'] * 1;
		if (!canViewGroup(0, $options['filter']['gid'], $ul)) {
			bailout('Sorry, but you do not have permission to view that group.', $return_format);
		}
	}

	if (isset($_GET['plid']) && is_numeric($_GET['plid'])) {
		$options['filter']['plid'] = (int) $_GET['plid'] * 1;
		if (!canViewPlaylist(0, $options['filter']['plid'], $ul)) {
			bailout('Sorry, but you do not have permission to view that playlist.', $return_format);
		}
	}

	if (isset($_GET['clid']) && trim($_GET['clid']) != '') {
		if ($ul < 5) {
			$options['filter']['clid'] = strtoupper(trim($_GET['clid']));
		} else {
			bailout('You are not allowed to filter with a class ID, sorry.', $return_format);
		}
	}

	$options['ul'] = $ul;

	$media_list = getMediaListing(0, $options);

	$total_entries = $media_list['total'];
	$total_pages = $media_list['pages'];
	$perpage = $media_list['perpage'];

	unset($media_list['total'], $media_list['pages'], $media_list['perpage']);

	$all_media = array();

	foreach ($media_list as $media_entry) {

		$temp_media = array();
		$temp_media['mid'] = $media_entry['mid'];
		$temp_media['title'] = htmlentities($media_entry['ti'], ENT_QUOTES, 'UTF-8');
		$temp_media['url'] = $median_base_url.'media/'.$media_entry['mid'].'/';
		$temp_media['metatype'] = $media_entry['mt'];
		if (isset($media_entry['th']) && isset($media_entry['th']['s'])) {
			$temp_media['thumb'] = substr($median_base_url, 0, -1).str_replace('/thumbs/', '/files/thumb/', $media_entry['th']['s']);
		} else {
			$temp_media['thumb'] = $median_base_url.'images/nothumb.jpg';
		}
		$temp_media['views'] = ((isset($media_entry['vc'])) ? $media_entry['vc']: 0);
		$temp_media['comments'] = ((isset($media_entry['cc'])) ? $media_entry['cc']: 0);

		// get user owners names (if any)
		if (isset($media_entry['ow']['u']) && count($media_entry['ow']['u']) > 0) {
			$temp_media['user_owners'] = array();
			foreach ($media_entry['ow']['u'] as $user_owner) {
				$temp_media['user_owners'][] = array('id' => $user_owner, 'name' => getUserName($user_owner));
			}
		}

		// get groups owners names (if any)
		if (isset($media_entry['ow']['g']) && count($media_entry['ow']['g']) > 0) {
			$temp_media['group_owners'] = array();
			foreach ($media_entry['ow']['g'] as $group_owner) {
				$group_info = getGroupInfo($group_owner);
				if (is_array($group_info) && canViewGroup(0, $group_owner, $ul)) {
					$temp_media['group_owners'][] = array('id' => $group_owner, 'name' => $group_info['n']);
				}
			}
		}

		$entry_authors = array();
		if (isset($media_entry['ow']['s']) && count($media_entry['ow']['s']) > 0) {
			// ok so show who they want to be shown as the owner
			if ($media_entry['ow']['s']['t'] == 'g') {
				// show the group
				$entry_authors = getGroupNames(array($media_entry['ow']['s']['id']));
			} else if ($media_info['ow']['s']['t'] == 'u') {
				// show the user
				$entry_authors = getUserNames(array($media_entry['ow']['s']['id']));
			}
		} else {
			// otherwise just show them all
			if (isset($media_entry['ow']['g']) && count($media_entry['ow']['g']) > 0) {
				// groups
				$entry_authors = array_merge($entry_authors, getGroupNames($media_entry['ow']['g']));
			}
			if (isset($media_entry['ow']['u']) && count($media_entry['ow']['u']) > 0) {
				// users
				$entry_authors = array_merge($entry_authors, getUserNames($media_entry['ow']['u']));
			}
		}

		$temp_media['show_owner_as'] = '';
		foreach ($entry_authors as $entry_author) {
			if ($temp_media['show_owner_as'] != '') {
				$temp_media['show_owner_as'] .= ', ';
			}
			$temp_media['show_owner_as'] .= $entry_author['name'];
		}

		if (isset($media_entry['du'])) {
			$temp_media['duration_time'] = getTimeCodeFromSeconds($media_entry['du']);
			$temp_media['duration_seconds'] = $media_entry['du'];
		}
		$temp_media['ts'] = $media_entry['tsc'];

		$all_media[] = $temp_media;
	}

	unset($media_entry);

	//print_r($all_media);

	// return it as json, xml, or rss/xml
	if ($return_format == 'json') {

		$to_json = array();
		$to_json['perpage'] = $perpage;
		$to_json['pages'] = $total_pages;
		$to_json['count'] = $total_entries;
		$to_json = array_merge($to_json, $all_media);
		echo json_encode($to_json);

	} else if ($return_format == 'rss') {

		$baseXML = '<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">
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

		/*

		load extra metadata depending on route taken... category, event, etc...

		*/

		if (isset($options['filter']['cid'])) { // get category data

			$cat_info = getCategoryInfo($options['filter']['cid']);
			$channel->title = htmlentities($cat_info['ti'], ENT_QUOTES, 'UTF-8');
			if (isset($cat_info['at']) && trim($cat_info['at']) != '') {
				$channel->addChild('itunes:author', htmlentities($cat_info['at'], ENT_QUOTES, 'UTF-8'), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
			} else {
				$channel->addChild('itunes:author', htmlentities($cat_info['ti'], ENT_QUOTES, 'UTF-8'), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
			}
			$description = '';
			if (isset($cat_info['de']) && trim($cat_info['de']) != '') {
				$description = trim($cat_info['de']);
			}
			$channel->description = htmlentities($description, ENT_QUOTES, 'UTF-8');
			$channel->link = htmlentities($median_base_url.'category/'.$options['filter']['cid'].'/');
			if (isset($cat_info['art_u']) && trim($cat_info['art_u']) != '') {
				$image = $channel->addChild('itunes:image', null, 'http://www.itunes.com/dtds/podcast-1.0.dtd');
				$image->addAttribute('href', substr($median_base_url, 0, -1).str_replace('http://median.emerson.edu', '', $cat_info['art_u']));
			}

			if (isset($cat_info['sd']) && trim($cat_info['sd']) != '') {
				$channel->addChild('itunes:subtitle', htmlentities(trim($cat_info['sd'])), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
			}

		} else if (isset($options['filter']['eid'])) { // get event data

			$event_info = getEventInfo($options['filter']['eid']);
			$channel->title = htmlentities($event_info['ti'], ENT_QUOTES, 'UTF-8');
			$description = '';
			if (isset($event_info['de']) && trim($event_info['de']) != '') {
				$description = trim($event_info['de']);
			}
			$channel->description = htmlentities($description, ENT_QUOTES, 'UTF-8');
			$channel->link = htmlentities($median_base_url.'event/'.$options['filter']['eid'].'/', ENT_QUOTES, 'UTF-8');
			if (isset($event_info['art_u']) && trim($event_info['art_u']) != '') {
				$image = $channel->addChild('itunes:image', null, 'http://www.itunes.com/dtds/podcast-1.0.dtd');
				$image->addAttribute('href', substr($median_base_url, 0, -1).str_replace('http://median.emerson.edu', '', $event_info['art_u']));
			}

			if (isset($event_info['sde']) && trim($event_info['sde']) != '') {
				$channel->addChild('itunes:subtitle', htmlentities(trim($event_info['sde'])), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
			}

		} else if (isset($options['filter']['uid'])) { // get user data

			$channel->title = htmlentities(getUserName($options['filter']['uid']), ENT_QUOTES, 'UTF-8');
			$channel->description = 'A Median user\'s feed.';
			$channel->addChild('itunes:subtitle', htmlentities('A Median user\'s feed.'), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
			$channel->link = htmlentities($median_base_url.'user/'.$options['filter']['uid'].'/', ENT_QUOTES, 'UTF-8');
			//$channel->author = htmlentities(getUserName($options['filter']['uid']), ENT_QUOTES, 'UTF-8');
			$channel->addChild('itunes:author', htmlentities(getUserName($options['filter']['uid']), ENT_QUOTES, 'UTF-8'), 'http://www.itunes.com/dtds/podcast-1.0.dtd');

		} else if (isset($options['filter']['gid'])) { // get group data

			$group_info = getGroupInfo($options['filter']['gid']);
			$channel->title = htmlentities($group_info['n'], ENT_QUOTES, 'UTF-8');
			$description = '';
			if (isset($group_info['d']) && trim($group_info['d']) != '') {
				$description = trim($group_info['d']);
			}
			$channel->description = htmlentities($description, ENT_QUOTES, 'UTF-8');
			$channel->link = htmlentities($median_base_url.'group/'.$options['filter']['gid'].'/', ENT_QUOTES, 'UTF-8');
			if (isset($group_info['a']) && isset($group_info['a']['u']) && trim($group_info['a']['u']) != '') {
				$image = $channel->addChild('itunes:image', null, 'http://www.itunes.com/dtds/podcast-1.0.dtd');
				$image->addAttribute('href', substr($median_base_url, 0, -1).str_replace('http://median.emerson.edu', '', $group_info['a']['u']));
			}
			if (isset($group_info['sd']) && trim($group_info['sd']) != '') {
				$channel->addChild('itunes:subtitle', htmlentities(trim($group_info['sd'])), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
			}
			//$channel->author = htmlentities($group_info['n'], ENT_QUOTES, 'UTF-8');
			$channel->addChild('itunes:author', htmlentities($group_info['n'], ENT_QUOTES, 'UTF-8'), 'http://www.itunes.com/dtds/podcast-1.0.dtd');

		} else if (isset($options['filter']['plid'])) { // get playlist data

			$playlist_info = getPlaylistInfo($options['filter']['plid']);
			$channel->title = htmlentities($playlist_info['ti'], ENT_QUOTES, 'UTF-8');
			$description = '';
			if (isset($playlist_info['de']) && trim($playlist_info['de']) != '') {
				$description = trim($playlist_info['de']);
			}
			$channel->description = htmlentities($description, ENT_QUOTES, 'UTF-8');
			$channel->link = htmlentities($median_base_url.'playlist/'.$options['filter']['plid'].'/', ENT_QUOTES, 'UTF-8');
			if (isset($playlist_info['art_u']) && trim($playlist_info['art_u']) != '') {
				$image = $channel->addChild('itunes:image', null, 'http://www.itunes.com/dtds/podcast-1.0.dtd');
				$image->addAttribute('href', substr($median_base_url, 0, -1).str_replace('http://median.emerson.edu', '', $playlist_info['art_u']));
			}
			if (isset($playlist_info['sd']) && trim($playlist_info['sd']) != '') {
				$channel->addChild('itunes:subtitle', htmlentities(trim($playlist_info['sd'])), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
			}
			$list_authors = '';
			if (isset($playlist_info['uid'])) {
				$list_authors = getUserName($playlist_info['uid']);
			} else if (isset($playlist_info['gid'])) {
				$playlist_group_info = getGroupInfo($playlist_info['gid']);
				$list_authors = getGroupName($playlist_group_info['n']);
			} else if (isset($playlist_info['clid'])) {
				$list_authors = strtoupper($playlist_info['clid']['c']);
			}
			//$channel->author = htmlentities($list_authors, ENT_QUOTES, 'UTF-8');
			$channel->addChild('itunes:author', htmlentities($list_authors, ENT_QUOTES, 'UTF-8'), 'http://www.itunes.com/dtds/podcast-1.0.dtd');

		} else { // well, just general latest media then...

			$channel->title = 'Median - Latest Public Media';
			$channel->description = 'The latest public media entries on Median.';
			$channel->addChild('itunes:subtitle', htmlentities('The latest public media entries on Median.'), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
			$channel->link = htmlentities($median_base_url, ENT_QUOTES, 'UTF-8');

		}

		foreach ($all_media as $media_entry) {

			$item = $channel->addChild('item');

			// take what you can from what was provided
			$item->addChild('title', htmlentities($media_entry['title'], ENT_QUOTES, 'UTF-8'));
			$item->addChild('link', htmlentities($media_entry['url'], ENT_QUOTES, 'UTF-8'));
			$item->addChild('guid', htmlentities($media_entry['url'], ENT_QUOTES, 'UTF-8'));
			$item->addChild('pubDate', date(DATE_RFC822, $media_entry['ts']));

			$item->addChild('itunes:author', htmlentities($media_entry['show_owner_as'], ENT_QUOTES, 'UTF-8'), 'http://www.itunes.com/dtds/podcast-1.0.dtd');

			if (isset($media_entry['duration_seconds'])) {
				$item->addChild('itunes:duration', $media_entry['duration_seconds'], 'http://www.itunes.com/dtds/podcast-1.0.dtd');
			}


			// whatever else, get the hard way

			$this_media_info = getMediaInfo($media_entry['mid']);

			if (isset($this_media_info['me']) && isset($this_media_info['me']['notes'])) {
				$notes = $this_media_info['me']['notes'];
			} else {
				$notes = '';
			}

			//$item->addChild('description', utf8_encode(htmlentities($notes, ENT_QUOTES, 'UTF-8')));
			//$item->addChild('itunes:summary', utf8_encode(htmlentities($notes, ENT_QUOTES, 'UTF-8')), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
			$item->addChild('description', utf8_encode(htmlspecialchars($notes, ENT_COMPAT, 'UTF-8')));
			$item->addChild('itunes:summary', utf8_encode(htmlspecialchars($notes, ENT_COMPAT, 'UTF-8')), 'http://www.itunes.com/dtds/podcast-1.0.dtd');

			if (isset($this_media_info['as']['ca']) && count($this_media_info['as']['ca']) > 0) {
				foreach ($this_media_info['as']['ca'] as $media_category) {
					$cat_info = getCategoryInfo($media_category);
					$item->addChild('category', htmlentities($cat_info['ti'], ENT_QUOTES, 'UTF-8'));
				}
			}

			if (isset($this_media_info['as']['tg']) && count($this_media_info['as']['tg']) > 0) {
				$itunes_tags = array();
				foreach ($this_media_info['as']['tg'] as $tag) {
					$itunes_tags[] = $tag;
				}
				$item->addChild('itunes:keywords', utf8_encode(htmlentities(implode(', ', $itunes_tags), ENT_QUOTES, 'UTF-8')), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
			}

			// get URLs to media files for videos, audio, and images

			$can_download = canBeHTML5($media_entry['mid']);

			if ($can_download) {
				$content_type = 'application/octet-stream';
				if ($media_entry['metatype'] == 'video') {
					$content_type = 'video/mp4';
				} else if ($media_entry['metatype'] == 'audio') {
					$content_type = 'audio/mpeg';
				} else if ($media_entry['metatype'] == 'image') {
					if (isset($this_media_info['pa']['c'])) {
						$export_file_ext = strtolower(substr($this_media_info['pa']['c'], -3));
						switch ($export_file_ext) {
							case 'jpg':
							$content_type = 'image/jpeg';
							break;
							case 'gif':
							$content_type = 'image/gif';
							break;
							case 'png':
							$content_type = 'image/png';
							break;
						}
					}
				}
				$enclosure = $item->addChild('enclosure');
				$enclosure->addAttribute('url', getHTMLFIVElink($media_entry['mid']));
				$enclosure->addAttribute('type', $content_type);
				$enclosure->addAttribute('length', '0');
			}

		} // end add media loop

		echo $root->asXML();

	} else if ($return_format == 'xml') {

		/*
		$baseXML = '<?xml version="1.0"?>
				<items>
				  <pages></pages>
				  <count></count>
				  <perpage></perpage>
				</items>';
		$root = simplexml_load_string($baseXML);
		*/

		$doc = new DomDocument('1.0');
		$doc->formatOutput = true;
		$root = $doc->createElement('items');
		$root = $doc->appendChild($root);

		// pages, perpage, and count elements
		$pageschild = $doc->createElement('pages');
		$pageschild = $root->appendChild($pageschild);
		$pagesvalue = $doc->createTextNode($total_pages);
		$pagesvalue = $pageschild->appendChild($pagesvalue);

		$countchild = $doc->createElement('count');
		$countchild = $root->appendChild($countchild);
		$countvalue = $doc->createTextNode($total_entries);
		$countvalue = $countchild->appendChild($countvalue);

		$perpagechild = $doc->createElement('perpage');
		$perpagechild = $root->appendChild($perpagechild);
		$perpagevalue = $doc->createTextNode($perpage);
		$perpagevalue = $perpagechild->appendChild($perpagevalue);

		// for loop for each element inside
		for ($i = 0; $i < count($all_media); $i++) {
			$itemchild = $doc->createElement('item');
			$itemchild = $root->appendChild($itemchild);
			foreach ($all_media[$i] as $xmlkey => $xmlvalue) {
				if ($xmlkey == 'user_owners') {
					// separate tree for this
					// create user_owners element, add user elements under it
					$userschild = $doc->createElement($xmlkey);
					$userschild = $itemchild->appendChild($userschild);
					foreach ($all_media[$i][$xmlkey] as $uservalue) {
						$keychild = $doc->createElement('user');
						$keychild = $userschild->appendChild($keychild);
						$keychild->setAttribute('id', $uservalue['id']);
						$keyvalue = $doc->createCDATASection($uservalue['name']);
						$keyvalue = $keychild->appendChild($keyvalue);
					}
				} else if ($xmlkey == 'group_owners') {
					// separate tree for this
					// create user_owners element, add user elements under it
					$userschild = $doc->createElement($xmlkey);
					$userschild = $itemchild->appendChild($userschild);
					foreach ($all_media[$i][$xmlkey] as $uservalue) {
						$keychild = $doc->createElement('group');
						$keychild = $userschild->appendChild($keychild);
						$keychild->setAttribute('id', $uservalue['id']);
						$keyvalue = $doc->createCDATASection($uservalue['name']);
						$keyvalue = $keychild->appendChild($keyvalue);
					}
				} else {
					// just nodes
					$keychild = $doc->createElement($xmlkey);
					$keychild = $itemchild->appendChild($keychild);
					$keyvalue = $doc->createCDATASection($xmlvalue);
					$keyvalue = $keychild->appendChild($keyvalue);
				}
			}

		}

		$xml_string = $doc->saveXML();
		echo $xml_string;


	} else {
		bailout('Invalid result format given.', $return_format);
	}

}

$script_time_end = microtime(true);
$script_time = $script_time_end - $script_time_start;
$script_time_ms = round($script_time * 1000);
//StatsD::timing('median5.script_time.api', $script_time_ms);

$new_log = array('r' => $_SERVER['REQUEST_URI'], 'ts' => time(), 'st' => $script_time_ms);
if (isset($apikey) && trim($apikey) != '' && trim($apikey) != '0') {
	$new_log['k'] = $apikey;
}
$m->median5_log->api->insert($new_log);

error_reporting(1);
