<?php

// get group list

$login_required = false;
require_once('/median-webapp/includes/login_check.php');
require_once('/median-webapp/includes/meta_functions.php');

$manage = false;

if (isset($_GET['manage']) && trim($_GET['manage']) == 'yes') {
	$manage = true;
}

if ($manage) {
	$groups = getUserGroupsOwnership($current_user['userid'], false);
} else {
	$groups = getGroups($current_user['userid']);
}

//echo '<!-- <pre>'.print_r($groups, true).'</pre> -->';

if ($current_user['loggedin']) {
	echo '<div class="panel">';
	echo '<div class="rss"><a href="/rss/groups/"><img src="/images/icons/rss.png" title="RSS Feed for Latest Public Groups" /></a></div>';
	echo '<a href="/new/group/" class="button small">Start a new group &raquo;</a>';
	echo '</div>';
}

if (count($groups) == 0) {
	echo '<div class="alert-box alert">Sorry, there are no groups to display.</div>';
} else {

	foreach ($groups as $group) {
		echo '<div class="group row entry clickable" data-type="group" data-id="'.$group['gid'].'">';
		echo '<p class="group-name">'.$group['n'].'</p>';
		if (isset($group['d'])) {
			echo '<p class="group-description">'.$group['d'].'</p>';
		}
		echo '</div>'."\n";

	}

}

?>