<?php

// return info about the current user in a specific format
// for the M6LiveBroadcaster.swf file
// send back in format 123.5.140000000 where
// first number is user ID
// second number is group level
// third number is current unix timestamp

$login_required = false;
require_once('/median-webapp/includes/login_check.php');
require_once('/median-webapp/includes/user_functions.php');

echo $current_user['userid'] . '.' . getUserLevel($current_user['userid']) . '.' . time();
