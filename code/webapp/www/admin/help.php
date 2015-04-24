<?php

/*

    HELP PAGES
        cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
    bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

require_once('/median-webapp/includes/help_functions.php');

$page_uuid = 'admin-page';
$page_title = 'Edit Help Pages'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Edit Help Pages</h2>
        <ul>
        <?php
        $pages = getHelpPages();
        foreach ($pages as $page) {
            echo '<li><a href="help_edit.php?id='.$page['k'].'">'.$page['ti'].'</a></li>'."\n";
        }
        ?>
        </ul>
        <form action="help_edit.php" method="post">
        <fieldset>
        <legend>New Page</legend>
        <input required="required" class="four" type="text" name="k" placeholder="short name" />
        <input required="required" class="four" type="text" name="t" placeholder="page title" />
        <input class="button medium" type="submit" value="add!" />
        </fieldset>
        </form>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
