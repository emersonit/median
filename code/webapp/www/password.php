<?php

/*

    GET THE PASSWORD!
        cyle gage, emerson college, 2014

*/

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

// include what's needed
require_once('/median-webapp/includes/common_functions.php');

$page_uuid = 'password-page';
$page_title = 'Password Required'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Password Required</h2>
        <p>What you are trying to access requires a password.</p>
        <form action="" method="post">
        <input type="password" name="p" />
        <input type="submit" value="Submit" class="button medium" />
        </form>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');