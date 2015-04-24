<?php

/*

    LISTING PAGE for GROUPS, EVENTS, CLASSES, etc
        cyle gage, emerson college, 2014

*/

$login_required = false;
require_once('/median-webapp/includes/login_check.php');
require_once('/median-webapp/includes/error_functions.php');

/*
	routing start
*/

if (!isset($_GET['w']) || trim($_GET['w']) == '') {
	bailout('No route provided.', $current_user['userid']);
}

$route_name = strtolower(trim($_GET['w']));

$allowed_routes = array('category', 'group', 'user', 'event', 'class', 'playlist');

if (!in_array($route_name, $allowed_routes)) {
	bailout('Not an acceptable route.', $current_user['userid'], null, $route_name);
}

$route_id = 0;

switch ($route_name) {
	case 'category':
	case 'group':
	case 'event':
	case 'playlist':
	if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { bailout('No valid route ID provided.', $current_user['userid']); }
	$route_id = (int) $_GET['id'] * 1;
	break;
	case 'user':
	if (!isset($_GET['id']) || trim($_GET['id']) == '') { bailout('No valid route ID provided.', $current_user['userid']); }
	if (is_numeric($_GET['id'])) {
		$route_id = (int) $_GET['id'] * 1;
	} else {
		$route_id = strtolower(trim($_GET['id']));
	}
	break;
	case 'class':
	$route_id = strtoupper(trim($_GET['id']));
	break;
}

// change username to user ID
if ($route_name == 'user' && !is_numeric($route_id)) {
	require_once('/median-webapp/includes/user_functions.php');
	$ecnet = $route_id;
	$route_id = getUserId($route_id);
}

if ($route_name != 'class' && $route_id == 0) {
	bailout('No valid route ID provided.', $current_user['userid']);
}

/*
	routing end
*/



/*
	permissions check start
*/

require_once('/median-webapp/includes/permission_functions.php');

$can_edit_this = false;

switch ($route_name) {
	case 'category':
	if (!canViewCategory($current_user['userid'], $route_id)) {
		if ($current_user['loggedin']) {
			bailout('Sorry, you do not have permission to view this category.', $current_user['userid'], null, $route_id);
		} else {
			bailout('Sorry, you do not have permission to view this category. Try <a href="/login.php?r='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'">logging in</a>.', $current_user['userid'], null, $route_id);
		}
	}
	$can_edit_this = canEditCategory($current_user['userid'], $route_id);
	break;
	case 'event':
	if (!canViewEvent($current_user['userid'], $route_id)) {
		if ($current_user['loggedin']) {
			bailout('Sorry, you do not have permission to view this event.', $current_user['userid'], null, $route_id);
		} else {
			bailout('Sorry, you do not have permission to view this event. Try <a href="/login.php?r='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'">logging in</a>.', $current_user['userid'], null, $route_id);
		}
	}
	$can_edit_this = canEditEvent($current_user['userid'], $route_id);
	break;
	case 'group':
	if (!canViewGroup($current_user['userid'], $route_id)) {
		if ($current_user['loggedin']) {
			bailout('Sorry, you do not have permission to view this group.', $current_user['userid'], null, $route_id);
		} else {
			bailout('Sorry, you do not have permission to view this group. Try <a href="/login.php?r='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'">logging in</a>.', $current_user['userid'], null, $route_id);
		}
	}
	$can_edit_this = canEditGroup($current_user['userid'], $route_id);
	break;
	case 'class':
	if (!canViewClass($current_user['userid'], $route_id)) {
		if ($current_user['loggedin']) {
			bailout('Sorry, you do not have permission to view this class.', $current_user['userid'], null, $route_id);
		} else {
			bailout('Sorry, you do not have permission to view this class. Try <a href="/login.php?r='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'">logging in</a>.', $current_user['userid'], null, $route_id);
		}
	}
	break;
	case 'playlist':
	if (!canViewPlaylist($current_user['userid'], $route_id)) {
		if ($current_user['loggedin']) {
			bailout('Sorry, you do not have permission to view this playlist.', $current_user['userid'], null, $route_id);
		} else {
			bailout('Sorry, you do not have permission to view this playlist. Try <a href="/login.php?r='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'">logging in</a>.', $current_user['userid'], null, $route_id);
		}
	}
	$can_edit_this = canEditPlaylist($current_user['userid'], $route_id);
	break;
}

/*
	permissions check end
*/



/*
	get meta content start
*/

$header_title = 'Listing Page';
$page_description = '';
$page_external_url = '';
$can_have_playlists = false;

switch ($route_name) {
	case 'category':
	require_once('/median-webapp/includes/meta_functions.php');
	$meta_info = getCategoryInfo($route_id);
	if ($meta_info == false) { bailout('Sorry, no category found with that ID.', $current_user['userid'], null, $route_id); }
	$header_title = $meta_info['ti'];
	if (isset($meta_info['de']) && trim($meta_info['de']) != '') { $page_description = $meta_info['de']; }
	if (isset($meta_info['pid'])) {
		$parent_category_info = getCategoryInfo($meta_info['pid']);
	}
	break;
	case 'event':
	require_once('/median-webapp/includes/meta_functions.php');
	$meta_info = getEventInfo($route_id);
	if ($meta_info == false) { bailout('Sorry, no event found with that ID.', $current_user['userid'], null, $route_id); }
	$header_title = $meta_info['ti'];
	if (isset($meta_info['de']) && trim($meta_info['de']) != '') { $page_description = $meta_info['de']; }
	if (isset($meta_info['url']) && trim($meta_info['url']) != '') { $page_external_url = $meta_info['url']; }
	break;
	case 'group':
	require_once('/median-webapp/includes/group_functions.php');
	$meta_info = getGroupInfo($route_id);
	if ($meta_info == false) { bailout('Sorry, no group found with that ID.', $current_user['userid'], null, $route_id); }
	$header_title = $meta_info['n'];
	if (isset($meta_info['d']) && trim($meta_info['d']) != '') { $page_description = $meta_info['d']; }
	$can_have_playlists = true;
	break;
	case 'user':
	require_once('/median-webapp/includes/user_functions.php');
	$meta_info = getUserInfo($route_id);
	if ($meta_info == false) { bailout('Sorry, no user found with that ID.', $current_user['userid'], null, $route_id); }
	$header_title = $meta_info['ecnet'];
	$can_have_playlists = true;
	break;
	case 'class':
	require_once('/median-webapp/includes/meta_functions.php');
	$meta_info = getClassInfo($route_id);
	if ($meta_info == false) { bailout('Sorry, no class found with that course code.', $current_user['userid'], null, $route_id); }
	$header_title = $meta_info['cc'].': '.$meta_info['ct'];
	$can_have_playlists = true;
	break;
	case 'playlist':
	require_once('/median-webapp/includes/meta_functions.php');
	$meta_info = getPlaylistInfo($route_id);
	if ($meta_info == false) { bailout('Sorry, no playlist found with that ID.', $current_user['userid'], null, $route_id); }
	$header_title = $meta_info['ti'];
	if (isset($meta_info['de']) && trim($meta_info['de']) != '') { $page_description = $meta_info['de']; }
	break;
}

//echo '<!-- '.print_r($meta_info, true).' -->';


/*
	get meta content end
*/

$page_uuid = 'listing-page';
$page_title = $header_title . ' - Median - '.$median_institution_name;
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">

        <?php if ($route_name == 'category' && isset($meta_info['pid'])) { ?><p>&laquo; <a href="/category/<?php echo $meta_info['pid']; ?>/">go to parent category <b><?php echo $parent_category_info['ti']; ?></b></a></p><?php } ?>
		<?php if ($route_name != 'class') { ?><div class="rss"><a href="/rss/<?php echo $route_name; ?>/<?php echo ((isset($route_id) && trim($route_id) != '') ? $route_id.'/' : ''); ?>"><img src="/images/icons/rss.png" title="RSS Feed for this listing" /></a></div><?php } ?>
        <h2><?php echo $header_title; ?></h2>
        <?php if (trim($page_description) != '') { echo '<p class="listing-description">'.$page_description.'</p>'; } ?>
        <?php if (trim($page_external_url) != '') { echo '<p class="listing-url"><a href="'.$page_external_url.'" target="_blank">Click here for more info &raquo</a></p>'; } ?>

        <?php
		// if this is a category that has subcategories, show a dropdown of the other categories
		if ($route_name == 'category') {
			$subcats = getSubcategories($route_id);
			if (count($subcats) > 0) {
				echo '<p><b>Visit a subcategory:</b> <select id="subcat-visit-list" class="three">';
				foreach ($subcats as $subcategory) {
					//echo '<pre>'.print_r($subcategory, true).'</pre>';
					echo '<option value="'.$subcategory['id'].'">'.$subcategory['ti'].'</option>';
				}
				echo '</select> <input type="button" value="go &raquo;" id="subcat-visit-button" /></p>'."\n";
			}
		}
		?>

        <div class="sub-nav">
            Viewing:
            <a class="listing-nav-active" href="/<?php echo $route_name.'/'.$route_id.'/'; ?>">Media List</a>
            <?php if ($can_have_playlists) { ?> or <a href="#" id="playlists-link">View Playlists</a> <?php } ?>
            <?php if ($route_name == 'category' && canHaveSubcats($route_id)) { ?> or <a href="/edit/subcats/<?php echo $route_id.'/'; ?>">Manage Subcategories</a> <?php } ?>
            <?php if ($can_edit_this) { ?> or <a href="/edit/<?php echo $route_name.'/'.$route_id.'/'; ?>">Edit Settings</a> <?php } ?>
        </div>

        <div id="media-list">

            <div class="nav-filter">
                <div class="filter-option-box">Filter:</div>
                <div class="filter-option-box"><select id="filter-type"><option value="all" selected="selected">All Media Types</option><option value="video">Video Only</option><option value="audio">Audio Only</option><option value="image">Images Only</option><option value="doc">Documents Only</option><option value="link">Links Only</option></select></div>
                <div class="filter-option-box"><select id="filter-sort">
                <option value="latest" selected="selected">Order by Date, Newest to Oldest</option>
                <option value="oldest">Order by Date, Oldest to Newest</option>
                <option value="alpha_asc">Order by Title, A to Z</option>
                <option value="alpha_desc">Order by Title, Z to A</option>
                <option value="time_asc">Order by Duration, Shortest to Longest</option>
                <option value="time_desc">Order by Duration, Longest to Shortest</option>
                <option value="views">Order by View Count, Highest to Lowest</option>
                <option value="comments">Order by Comment Count, Highest to Lowest</option>
                </select></div>
                <div class="filter-option-box"><input type="submit" value="filter!" id="filter-submit"></div>
                <div class="dummy"></div>
            </div>

            <div id="main-list"><?php require('dummy_listing.php'); ?></div>

            <?php
			if ($can_edit_this && $route_name != 'user') {
			?>
			<div>
				<fieldset>
				<legend>Advanced Options</legend>
				<a href="#" id="clear-mids-btn" class="button small alert">Clear Selected Media</a> <a href="/flush/media/from/<?php echo $route_name; ?>/<?php echo $route_id; ?>/" class="delete-major-btn button small alert">Clear All Media from This</a>
				</fieldset>
			</div>
			<?php
			}
			?>

        </div>

        <?php
		if ($can_have_playlists) {
		?>
		<div id="playlists-list" style="display:none;"></div>
		<?php
		}
		?>

    </div>
</div>

<input type="hidden" id="route-name" value="<?php echo $route_name; ?>" />
<input type="hidden" id="route-id" value="<?php echo $route_id; ?>" />
<input type="hidden" id="route-manager" value="<?php echo $can_edit_this * 1; ?>" />

<?php
require_once('/median-webapp/includes/footer.php');
