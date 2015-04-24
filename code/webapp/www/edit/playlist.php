<?php

/*

	EDIT PLAYLIST
		cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/error_functions.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
	bailout('No valid ID provided.', $current_user['userid']);
}

$plid = (int) $_GET['id'] * 1;

require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/group_functions.php');

$meta_info = getPlaylistInfo($plid);

if ($meta_info == false) {
	// uhh media does not exist!
	bailout('Sorry, but a playlist with that ID does not exist!', $current_user['userid']);
}

// permission checks
$can_user_edit = canEditPlaylist($current_user['userid'], $plid);

if (!$can_user_edit) {
	bailout('Sorry, but you do not have permission to edit this playlist.', $current_user['userid']);
}

$page_uuid = 'edit-playlist-page';
$page_title = 'Editing '.$meta_info['ti'];
require_once('/median-webapp/includes/header.php');
?>

<?php echo '<!-- '.print_r($meta_info, true).' -->'; ?>

<div class="row">
    <div class="column full">
        <h2>Edit Playlist</h2>

        <form action="/submit/edit/playlist/" method="post" id="playlist-form" enctype="multipart/form-data">
        <input type="hidden" name="plid" value="<?php echo $plid; ?>" />
        <div class="row">
            <div class="column third">
                <label class="right required">Name:</label>
            </div>
            <div class="column two-thirds">
                <input id="playlist-name" type="text" placeholder="My Cool Videos" value="<?php echo $meta_info['ti']; ?>" name="t" />
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="right">Brief Description / Tagline:</label>
            </div>
            <div class="column two-thirds">
                <input type="text" value="<?php if (isset($meta_info['sd'])) { echo $meta_info['sd']; } ?>" name="sd" />
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="right">Description:</label>
            </div>
            <div class="column two-thirds">
                <textarea name="d"><?php if (isset($meta_info['de'])) { echo $meta_info['de']; } ?></textarea>
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="right">iTunes Artwork:</label>
            </div>
            <div class="column two-thirds">
                <input type="file" name="a" /> (Optional; JPG only; 200kb limit; leave alone to keep existing.)
            </div>
        </div>

        <input type="submit" value="Save" class="button large" />
        </form>

        <hr />
        <h4>Delete Playlist</h4>
        <p>This will delete the playlist entirely. It will not delete any media.</p>
        <a href="/delete/playlist/<?php echo $plid ?>/" class="delete-major-btn button small alert">Delete Playlist!</a>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
