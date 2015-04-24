<?php

/*

    YOUR MEDIAN ACCOUNT
        cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');

$page_uuid = 'manage-page';
$page_title = 'Manage Your Account'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Manage Your Account</h2>
        <p>Edit available site-wide account options here.</p>
        <?php
        if (isset($_GET['success']) && trim($_GET['success']) != '') {
        ?>
        <div class="alert-box success">Successfully saved your changes.</div>
        <?php
        }
        ?>
    </div>
</div>

<div class="row">
    <div class="column full">
        <div class="sub-nav">
            Viewing:
            <a href="/manage/media/">Your Media</a>
            or <a href="/manage/playlists/">Your Playlists</a>
            or <a href="/manage/groups/">Your Groups</a>
            or <a class="manage-list-active" href="/manage/account/">Your Account Options</a>
        </div>
        <form action="/submit/edit/account/" method="post">
            <p><label><input type="checkbox" name="st" value="1" <?php if (alwaysUseSubtitles($current_user['userid'])) { echo 'checked="checked"'; } ?> /> Always enable subtitles when available?</label></p>
            <input type="submit" class="button medium success" value="Save Changes" />
        </form>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
