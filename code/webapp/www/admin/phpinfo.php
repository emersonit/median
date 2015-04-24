<?php

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

if ($current_user['userlevel'] != 1) {
    die('You do not have access to this, sorry.');
}

phpinfo();
