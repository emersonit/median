<?php

/*

    SWAP FILES!
        cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/error_functions.php');
require_once('/median-webapp/includes/log_functions.php');

// sorry -- IE not allowed for uploading
if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
    bailout('Sorry, but the Median Uploader is not compatible with Internet Explorer. Please use Chrome, Firefox, or Safari.', $current_user['userid']);
}

if (!isset($_GET['mid']) || !is_numeric($_GET['mid'])) {
	bailout('Sorry, no Media ID provided.', $current_user['userid']);
}

$mid = (int) $_GET['mid'] * 1;

require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/meta_functions.php');

$media_info = getMediaInfo($mid);

if ($media_info == false) {
	// uhh media does not exist!
	bailout('Sorry, but the media entry with that ID does not exist!', $current_user['userid'], $mid);
}

$can_user_edit = canEditMedia($current_user['userid'], $media_info['mid']);

if (!$can_user_edit) {
	bailout('Sorry, but you are not allowed to edit this media entry.', $current_user['userid'], $mid);
}

$page_uuid = 'fileswap-page';
$page_title = 'Upload a New File for an Entry'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <input type="hidden" class="upload-field" name="is_swap" value="yeah" />
        <input type="hidden" class="upload-field" name="mid" value="<?php echo $mid; ?>" id="mid" />
		<input type="hidden" class="upload-field" name="user_id" value="<?php echo $current_user['userid']; ?>" id="uid" />

		<div id="upload-step">
			<h3><?php echo $page_title; ?></h3>
			<div class="panel">
			<p>Here you can select a new file to replace the one currently on Median for this entry.</p>
			<p>Please note that the old file will be <b>totally deleted</b>. Also, you must upload a file of the <b>same type</b> (only a video can replace a video, etc).</p>
			<p>Also please note that uploading a new video file may require it to be transcoded, so your entry will be disabled while that happens.</p>
			</div>
			<div id="file-entry-list">
				<div class="file-entry">
					<p>Select a new file to replace the old! <input class="upload-file" name="upload-file[]" type="file" /></p>
                    <input name="upload-title[]" class="upload-field" type="hidden" value="Untitled" />
				</div>
			</div>
			<p><a href="#" id="upload-btn" class="button large success radius">UPLOAD!</a></p>
		</div>

        <div id="uploading-step" style="display:none;">
            <div class="panel">
                <p><b>Uploading!</b> Please be patient. How long this takes depends on the size of the file you've selected and your connection speed.</p>
            </div>
            <div id="progress-bar-outside"><div id="progress-par-inside"></div></div>
            <p id="upload-debug-txt"></p>
        </div>

        <div id="uploaded-step" style="display:none;">
            <div class="panel"><p>Your media is done uploading. Check out the status of each upload below. If you uploaded a video, you can check out its transcoding status on <a href="/farming/" target="_blank">the Farm page</a> (opens in a new window).</p></div>
            <div id="upload-result"></div>
            <div><a href="/media/<?php echo $mid; ?>/" class="button medium success">&laquo; go back to the entry</a></div>
        </div>

        <div id="error-step" style="display:none;">
            <p>There was an error of some kind! It's possible the upload function is not available.</p>
        </div>

    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
