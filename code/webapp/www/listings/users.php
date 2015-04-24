<?php

// users search

//echo '<pre>'.print_r($_POST, true).'</pre>';

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

if (isset($_POST['search']) && trim($_POST['search']) != '') {
	$search_string = strtolower(trim($_POST['search']));
} else {
	die('Sorry, you did not provide a name to search for.');
}

require_once('/median-webapp/includes/user_functions.php');

$users = searchUsers($search_string);

if (count($users) > 0) {
	echo '<h4>Users:</h4>';
	echo '<ul>';
	foreach ($users as $user) {
		echo '<li><a href="/user/'.$user['ecnet'].'/">'.$user['ecnet'].'</a></li>';
	}
	echo '</ul>';
} else {
	die('Sorry, no users found.');
}

?>
