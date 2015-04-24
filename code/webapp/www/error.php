<?php

/*

    THERE WAS AN ERROR
        cyle gage, emerson college, 2014

*/

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/config/config.php');

$page_uuid = 'error-page';
$page_title = 'Error!'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>There was an error!</h2>
        <?php
        if (isset($error_message) && trim($error_message) != '') {
            echo '<div class="panel error"><p>'.$error_message.'</p></div>'."\n";
        }
        ?>
        <p>Please go back, or if you're still having trouble, call the IT Help Desk at 617-824-8080 or <a href="<?php echo $median_outside_help; ?>">fill out a support request</a>.</p>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
