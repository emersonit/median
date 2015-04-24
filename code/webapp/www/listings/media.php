<?php
// make a listing of media in some fashion

$script_time_start = microtime(true);

$login_required = false;
require_once('/median-webapp/includes/login_check.php');
require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/media_functions.php'); // includes user_functions.php
require_once('/median-webapp/includes/common_functions.php');
require_once('/median-webapp/includes/error_functions.php');
require_once('/median-webapp/includes/permission_functions.php');

$users_favs = false;
if (isset($_GET['favs']) && trim($_GET['favs']) != '') {
	$users_favs = true;
}

$users_manage = false;
$manage_what = false;
if (isset($_GET['manage']) && trim($_GET['manage']) != '') {
	$users_manage = true;
	if (isset($_POST['owner']) && trim($_POST['owner']) != '') {
		switch (trim($_POST['owner'])) {
			case 'all':
			// manage everything -- pending, direct owner, and all under groups
			$manage_what = 'all';
			break;
			case 'mine':
			// show just entries that are directly mine
			$manage_what = 'mine';
			break;
		}
	}
}

$listing_manage = false;
if (isset($_POST['listmanage']) && trim($_POST['listmanage']) == 'yes') {
	$listing_manage = true;
}

$show_descriptions = false; // only certain listings will show a description per entry, if they have one

$options = array();
$page = 1;

if (isset($_POST['sort']) && trim($_POST['sort']) != '') {
	$options['sort'] = strtolower(trim($_POST['sort']));
}

if (isset($_POST['type']) && trim($_POST['type']) != '') {
	$options['filter']['type'] = strtolower(trim($_POST['type']));
}

if (isset($_POST['page']) && trim($_POST['page']) != '') {
	$options['page'] = (int) $_POST['page'] * 1;
	$page = $options['page'];
}

if (isset($_POST['num']) && trim($_POST['num']) != '') {
	$options['howmany'] = (int) $_POST['num'] * 1;
	setcookie('M_OPTS_PP', $options['howmany'], time()+60*60*24*30, '/');
} else if (isset($_COOKIE['M_OPTS_PP']) && is_numeric($_COOKIE['M_OPTS_PP'])) {
	$options['howmany'] = (int) $_COOKIE['M_OPTS_PP'] * 1;
	if ($options['howmany'] > 100) {
		$options['howmany'] = 100;
	}
	setcookie('M_OPTS_PP', $options['howmany'], time()+60*60*24*30, '/');
}

if (isset($_POST['rname']) && trim($_POST['rname']) != '') {

	if (!isset($_POST['rid']) || trim($_POST['rid']) == '') {
		bailout('No route ID provided.', $current_user['userid'], null, null, false);
	}

	$filter_name = strtolower(trim($_POST['rname']));

	switch ($filter_name) {
		case 'category':
		$show_descriptions = true;
		$options['filter']['cid'] = (int) $_POST['rid'] * 1;
		if (!canViewCategory($current_user['userid'], $options['filter']['cid'])) {
			die('Sorry, but you do not have permission to view that category.');
		}
		break;
		case 'group':
		$options['filter']['gid'] = (int) $_POST['rid'] * 1;
		if (!canViewGroup($current_user['userid'], $options['filter']['gid'])) {
			die('Sorry, but you do not have permission to view that group.');
		}
		break;
		case 'user':
		$options['filter']['uid'] = (int) $_POST['rid'] * 1;
		break;
		case 'event':
		$options['filter']['eid'] = (int) $_POST['rid'] * 1;
		if (!canViewEvent($current_user['userid'], $options['filter']['eid'])) {
			die('Sorry, but you do not have permission to view that event.');
		}
		break;
		case 'class':
		$options['filter']['clid'] = strtoupper(trim($_POST['rid']));
		if (!canViewClass($current_user['userid'], $options['filter']['clid'])) {
			die('Sorry, but you do not have permission to view that class.');
		}
		break;
		case 'playlist':
		$options['filter']['plid'] = (int) $_POST['rid'] * 1;
		if (!canViewPlaylist($current_user['userid'], $options['filter']['plid'])) {
			die('Sorry, but you do not have permission to view that playlist.');
		}
		break;
	}

}

if (isset($_POST['search']) && trim($_POST['search']) != '') {
	$options['filter']['title'] = trim($_POST['search']);
}

if ($users_favs) {
	$users_favs_list = getUserFavs($current_user['userid']);
	if ($users_favs_list == false) {
		echo 'You do not currently have any favorites.';
		die();
	}
	$options['mids'] = $users_favs_list;
}

if ($users_manage) {
	if ($manage_what != false && $manage_what != 'mine') {
		// get whatever the specific request is
		if ($manage_what == 'all') {
			$users_mids = getUserManageMedia($current_user['userid']);
			if ($users_mids == false) {
				echo 'You do not currently have any media to manage.';
				die();
			}
			$options['mids'] = $users_mids;
		} else {
			bailout('I am not sure what to do with the request you just gave me, sorry.', $current_user['userid'], null, $manage_what, false);
		}
	} else {
		// get whatever the user directly owns, including pending, by default
		$users_mids = getUserMedia($current_user['userid']);
		if ($users_mids == false) {
			echo 'You do not currently have any media to manage.';
			die();
		}
		$options['mids'] = $users_mids;
	}
	$options['manage'] = true;
}

//echo '<pre>'.print_r($options, true).'</pre>';

$media_list = getMediaListing($current_user['userid'], $options);

if ($media_list['total'] == 0) {
	echo 'Sorry, no media entries met your criteria.';
	die();
}

$total_entries = $media_list['total'];
$total_pages = $media_list['pages'];
$perpage = $media_list['perpage'];

unset($media_list['total'], $media_list['pages'], $media_list['perpage']);

if ($users_manage) {
	echo '<form action="/delete/media/" method="post" id="bulk-delete-form">';
}

foreach ($media_list as $media_entry) {

	/*

		need to add considerations for pending entries

	*/

	echo '<div class="media row entry'.((!$users_manage && !$listing_manage) ? ' clickable' : ' selectable').'" data-type="media" data-id="'.$media_entry['mid'].'">'."\n";
	if (isset($media_entry['th']) && isset($media_entry['th']['s']) && trim($media_entry['th']['s']) != '') {
		$thumb = str_replace('/thumbs/', '/files/thumb/', $media_entry['th']['s']);
	} else if ($media_entry['mt'] == 'audio') {
		$thumb = '/images/audio.gif';
	} else if ($media_entry['mt'] == 'link') {
		$thumb = '/images/link.png';
	} else if ($media_entry['mt'] == 'doc') {
		$thumb = '/images/doc.png';
	} else {
		$thumb = '/images/nothumb.jpg';
	}
	if ($listing_manage) {
		echo '<div class="options"><input type="checkbox" name="mid[]" class="entry-select" value="'.$media_entry['mid'].'" /></div>';
	} else if ($users_manage) {
		echo '<div class="options"><input type="checkbox" name="mid[]" class="entry-select" value="'.$media_entry['mid'].'" /> <a href="/edit/media/'.$media_entry['mid'].'/" class="button small round">edit</a> <a href="/delete/media/'.$media_entry['mid'].'/" class="button small round alert">delete</a></div>';
	}
	echo '<div class="thumb"><img src="'.$thumb.'" /></div>'."\n";
	echo '<div class="entry-info">'."\n";
	if ($users_manage && $media_entry['en'] == false) {
		echo '<p class="title">'.$media_entry['ti'].' (Pending)</p>'."\n";
	} else {
		echo '<p class="title"><a href="'.getPermalink($media_entry['mid']).'">'.$media_entry['ti'].'</a></p>'."\n";
	}
	if ($show_descriptions && isset($media_entry['me']['notes']) && trim($media_entry['me']['notes']) != '') {
		echo '<p class="description">';
		$desc_num_words_cap = 20;
		$desc_num_words = count(explode(' ', $media_entry['me']['notes']));
		echo implode(' ', array_slice(explode(' ', $media_entry['me']['notes']), 0, $desc_num_words_cap));
		if ($desc_num_words > $desc_num_words_cap) {
			echo ' ...';
		}
		echo '</p>'."\n";
	}
	echo '<p class="info">';
	if (trim($media_entry['mt']) == '') {
		$metatype = ucwords($media_entry['ty']);
	} else {
		$metatype = ucwords($media_entry['mt']);
	}
	echo '<span class="mtype">'.$metatype.'</span>, ';
	if (isset($media_entry['du'])) {
		echo '<span class="duration">'.getTimeCodeFromSeconds($media_entry['du']).'</span>, ';
	}
	$view_count = ((isset($media_entry['vc'])) ? $media_entry['vc'] : 0);
	echo '<span class="views">'.$view_count.' view'.plural($view_count).'</span>, ';
	/*

		once the whole "who to display as owner" thing is sorted out, implement it here...

		... or maybe show no owners at all ?

	*/
	//echo '<span class="uploaders"><a href="listing.php?w=user&id=4872">Colleen Shaughnessy</a></span>, ';
	echo '<span class="when">'.getRelativeTime($media_entry['tsc']).'</span>';
	echo '</p>'."\n";
	echo '</div>'."\n";
	echo '</div>'."\n";
}

unset($media_entry);

/*

	nav / pagination

*/

echo '<div id="nav-info">Total Entries: '.$total_entries.', per page: <select class="inline" id="media-perpage"><option value="10"'.(($perpage == 10) ? ' selected="selected"': '').'>10</option><option value="25"'.(($perpage == 25) ? ' selected="selected"': '').'>25</option><option value="50"'.(($perpage == 50) ? ' selected="selected"': '').'>50</option></select> <input type="hidden" id="media-page" value="'.$page.'" /></div>';

?>
<div>
<ul class="pagination">
	<li class="arrow<?php if ($page == 1) { echo ' unavailable'; } ?>"><a id="media-prev-btn" href="#">&laquo;</a></li>
	<li<?php if ($page == 1) { echo ' class="current"';} ?>><a class="media-page-btn" data-id="1" href="#">1</a></li>
	<?php
	if ($total_pages > 1 && $total_pages <= 10) {
		for ($i = 2; $i < $total_pages; $i++) {
			echo '<li'.(($page == $i) ? ' class="current"' : '').'><a class="media-page-btn" data-id="'.$i.'" href="#">'.$i.'</a></li>';
		}
	} else {
		if ($total_pages > 1 && $page != 2) {
			echo '<li class="unavailable"><a href="">&hellip;</a></li>';
		}
		if ($page > 1 && $page < $total_pages) {
			echo '<li class="current"><a class="media-page-btn" data-id="'.$page.'" href="#">'.$page.'</a></li>';
			if ($page != $total_pages - 1) {
				echo '<li class="unavailable"><a href="">&hellip;</a></li>';
			}
		}
	}
	if ($total_pages > 1) {
		echo '<li'.(($page == $total_pages) ? ' class="current"' : '').'><a class="media-page-btn" data-id="'.$total_pages.'" href="#">'.$total_pages.'</a></li>';
	}
	?>

	<li class="arrow<?php if ($page == $total_pages) { echo ' unavailable'; } ?>"><a id="media-next-btn" href="#">&raquo;</a></li>
</ul>
</div>
<?php
if ($users_manage) {

	?>
<div id="add-to-options" class="panel">
	<h4>Do with selected:</h4>
	<p><input type="submit" value="Delete them!" class="delete-major-btn button medium radius alert" /></p>
	</form> <!-- end of big huge listing form -->
	<p>Or add them to...</p>
	<form action="/update/media/" method="post" id="add-to-form">
	<?php
	// get events
	$events = getEvents($current_user['userid'], true);
	if (count($events) > 0) {
	?>
	<div class="row">
		<div class="third column"><label class="right inline">Event:</label></div>
		<div class="two-thirds column">
			<select name="event[]">
				<option value="0" selected="selected">None</option>
				<?php
				foreach ($events as $event) {
					echo '<option value="'.$event['id'].'">'.$event['ti'].'</option>'."\n";
				}
				?>
			</select> <a href="#" class="button green small add-another" data-context="event">Add another &raquo;</a>  <a href="#" class="button alert small remove-other" data-context="event">&times;</a>
		</div>
	</div>
	<?php
	} // end events check

	// get user groups
	$user_groups = getUserGroups($current_user['userid']);
	if (count($user_groups) > 0) {
	?>
	<div class="row">
		<div class="third column"><label class="right inline">Group(s):</label></div>
		<div class="two-thirds column">
			<select class="owner-field" name="groupowner[]">
				<option value="0" selected="selected">None</option>
				<?php
				foreach ($user_groups as $group) {
					echo '<option value="'.$group['gid'].'">'.$group['n'].'</option>'."\n";
				}
				?>
			</select> <a href="#" class="button green small add-another" data-context="group-owner">Add another &raquo;</a>  <a href="#" class="button alert small remove-other" data-context="group-owner">&times;</a>
		</div>
	</div>
	<?php
	} // end groups check

	// get categories
	$cats = getCategories($current_user['userid'], true);
	if (count($cats) > 0) {
	?>
	<div class="row">
		<div class="third column"><label class="right inline">Category:</label></div>
		<div class="two-thirds column">
			<select name="cat[]">
				<option value="0" selected="selected">None</option>
				<option value="1">Uncategorized</option>
				<?php
				foreach ($cats as $cat) {
					if (isset($cat['pid']) || $cat['id'] == 1) {
						continue;
					}
					echo '<option value="'.$cat['id'].'">'.$cat['ti'].'</option>';
					foreach ($cats as $subcat) {
						if (isset($subcat['pid']) && $subcat['pid'] == $cat['id']) {
							echo '<option value="'.$subcat['id'].'"> - '.$subcat['ti'].'</option>';
							foreach ($cats as $subsubcat) {
								if (isset($subsubcat['pid']) && $subsubcat['pid'] == $subcat['id']) {
									echo '<option value="'.$subsubcat['id'].'"> - - '.$subsubcat['ti'].'</option>';
								}
							}
						}
					}
				}
				?>
			</select> <a href="#" class="button green small add-another" data-context="cat">Add another &raquo;</a>  <a href="#" class="button alert small remove-other" data-context="cat">&times;</a>
		</div>
	</div>
	<?php
	} // end cats check

	// get classes
	$user_classes = getUserClasses($current_user['userid']);
	$all_classes = array();
	if (count($user_classes) == 2) {
		$all_classes = array_merge($user_classes['taking'], $user_classes['teaching']);
	?>
	<div class="row" class="class-row">
		<div class="third column"><label class="right inline">Class:</label></div>
		<div class="two-thirds column">
			<select name="class[]">
				<option value="0" selected="selected">None</option>
				<?php
				foreach ($all_classes as $class) {
					echo '<option value="'.$class['cc'].'">'.$class['cc'].': '.$class['name'].'</option>'."\n";
				}
				?>
			</select> <a href="#" class="button green small add-another" data-context="class">Add another &raquo;</a> <a href="#" class="button alert small remove-other" data-context="class">&times;</a>
		</div>
	</div>
	<?php
	} // end classes check
	?>

	<?php
	// get playlists
	$user_playlists = getAllPlaylists($current_user['userid']);
	if (count($user_playlists) > 0) {
	?>
	<div class="row" class="playlist-row">
		<div class="third column"><label class="right inline">Playlist:</label></div>
		<div class="two-thirds column">
			<select name="playlist[]">
				<option value="0" selected="selected">None</option>
				<?php
				foreach ($user_playlists as $playlist) {
					$playlist_source = 'Unknown';
					if (isset($playlist['uid'])) {
						$playlist_source = 'Yours';
					} else if (isset($playlist['gid'])) {
						$group_info = getGroupInfo($playlist['gid']);
						$playlist_source = 'Group: '.$group_info['n'];
					} else if (isset($playlist['clid'])) {
						$playlist_source = 'Class: '.$playlist['clid']['c'];
					}
					echo '<option value="'.$playlist['id'].'">'.$playlist['ti'].' ('.$playlist_source.')</option>'."\n";
				}
				?>
			</select> <a href="#" class="button green small add-another" data-context="playlist">Add another &raquo;</a> <a href="#" class="button alert small remove-other" data-context="playlist">&times;</a>
		</div>
	</div>
	<?php
	}
	?>

	<div class="row">
		<div class="third column"><label class="right inline">Tags:</label></div>
		<div class="two-thirds column"><input name="tags" class="ten" type="text" placeholder="tag, tag two, another tag" /></div>
	</div>
	<div class="row">
		<div class="full column">
			<input type="submit" value="Submit" class="button medium success" />
		</div>
	</div>
	</form>
</div>
	<?php


}


$script_time_end = microtime(true);
$script_time = $script_time_end - $script_time_start;
$script_time_ms = round($script_time * 1000);
//StatsD::timing('median5.script_time.media_listing', $script_time_ms);
