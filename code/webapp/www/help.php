<?php

/*

    MEDIAN HEEELLLPPP
        cyle gage, emerson college, 2014

*/

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/common_functions.php');
require_once('/median-webapp/includes/help_functions.php');

$selected_page = 'main';

if (isset($_GET['w']) && trim($_GET['w']) != '') {
    $selected_page = strtolower(trim($_GET['w']));
}

$page_uuid = 'help-page';
$page_title = 'Default Page Template'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <?php

        // get selected page, unless that page is main
        if ($selected_page == 'main') {
        ?>
        <h2>Help!</h2>
        <p>Here's a list of the available help pages, most viewed at the top:</p>
        <ul>
            <?php
            $pages = getHelpPages();
            foreach ($pages as $page) {
                echo '<li><a href="/help/'.$page['k'].'/">'.$page['ti'].'</a> ('.$page['v'].' view'.plural($page['v']).')</li>'."\n";
            }
            ?>
        </ul>
        <p>Here's a list of useful links:</p>
        <ul>
            <li><a href="<?php echo $median_outside_help; ?>">Submit a Median Support Request</a></li>
            <li><a href="/farming/">Median Video Transcoding Farm Status</a></li>
            <li><a href="/manage/account/">Manage Your Account Options</a></li>
        </ul>
        <?php
        } else {
            // get selected page, if there is one with the provided key...
            $page = getHelpPage($selected_page);
            if ($page != false) {
                echo '<p><a href="/help/">&laquo; go back to Help</a></p>';
                echo '<h2>Help - '.$page['ti'].'</h2>';
                echo '<div class="help-content">'.$page['c'].'</div>';
                echo '<div class="panel">'.$page['v'].' view'.plural($page['v']).', last updated '.date('m/d/y', $page['tsu']).'.</div>';
                //echo '<pre>'.print_r($page, true).'</pre>';
                updateHelpViewCount($selected_page);
            } else {
                ?>
                <h2>Help!</h2>
                <p>Sorry, there is no page that matches your request.</p>
                <?php
            }
        }
        ?>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
