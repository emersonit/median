<?php

/*

    bulk add entries to a class

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
    bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

$page_uuid = 'admin-page';
$page_title = 'Bulk Add Entries'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Bulk Add Entries</h2>
        <form action="bulk_process.php" method="post">
        <p>Course code: <input type="text" placeholder="CC100-0" name="cc" /></p>
        <p><a href="https://tagteam.emerson.edu/tools/lookup/semesters.php">Semester code</a>: <input type="text" placeholder="201520" name="sc" /></p>
        <p>Median Entry IDs, one per line or comma-separated:</p>
        <p><textarea name="mids" placeholder="101, 38213, 829"></textarea></p>
        <p><input class="button medium" type="submit" value="add!" /></p>
        </form>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
