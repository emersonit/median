<?php

/*

	REQUEST A NEW CATEGORY
		cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/common_functions.php');

$page_uuid = 'new-thing-page';
$page_title = 'Request a New Category';
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Request a New Category</h2>
        <p>For now, please <a href="<?php echo $median_outside_help; ?>">submit a support request</a> to request a new category.</p>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
