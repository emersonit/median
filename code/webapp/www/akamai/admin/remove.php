<?php

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (canUseAkamai($current_user['userid']) == false) {
	bailout('Sorry, but you do not have permission to access this page.', $current_user['userid']);
}

if (!isset($_GET['mid']) || !is_numeric($_GET['mid'])) {
	bailout('Sorry, but have not provided a Median ID to remove.', $current_user['userid']);
}

$mid = (int) $_GET['mid'] * 1;

require_once('/median-webapp/includes/akamai_functions.php');

$result = removeMediaFromAkamai($mid);

if ($result == false) {
	bailout('Sorry, but something went wrong removing the entry from Akamai.', $current_user['userid'], $mid);
} else {
	header('Location: /akamai/admin/?removed=yup');
}

?>