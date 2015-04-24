<?php

/*

    MEDIAN PLAYER PAGE
        cyle gage, emerson college, 2014

*/

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

// error functions first
require_once('/median-webapp/includes/error_functions.php');

// make sure there's a MID to watch
if (!isset($_GET['mid']) || !is_numeric($_GET['mid'])) {
	bailout('Sorry, no Media ID provided, I don\'t know what to show you.', $current_user['userid']);
}

// what's that median ID? we'll need that. yeah.
$mid = (int) $_GET['mid'] * 1;

// the tools we need
require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/common_functions.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/user_functions.php');

// get the media info for this entry
$media_info = getMediaInfo($mid);

// does the entry even exist?
if ($media_info == false) {
	// uhh media does not exist!
	bailout('Sorry, but a media entry with that ID does not exist!', $current_user['userid'], $mid);
}

// can the current user view this entry?
$can_view_result = canViewMedia($current_user['userid'], $mid);

if ($can_view_result < 1) {
	switch ($can_view_result) {
		case -100:
		if ($current_user['loggedin']) {
			bailout('Sorry, but this entry is restricted to a higher user level.', $current_user['userid'], $mid);
		} else {
			bailout('Sorry, but this entry is restricted to a higher user level. Try <a href="/login.php?r='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'">logging in</a> to see this entry, you\'ll be brought back to this page after logging in.', 0, $mid);
		}
		break;
		case -200:
		if ($current_user['loggedin']) {
			bailout('Sorry, but this entry is class-only for a class you are not in.', $current_user['userid'], $mid);
		} else {
			bailout('Sorry, but this entry is class-only. Try <a href="/login.php?r='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'">logging in</a> to see this entry, you\'ll be brought back to this page after logging in.', 0, $mid);
		}
		break;
		case -300:
		if ($current_user['loggedin']) {
			bailout('Sorry, but this entry is restricted to a certain group you are not in.', $current_user['userid'], $mid);
		} else {
			bailout('Sorry, but this entry is restricted to a certain group. Try <a href="/login.php?r='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'">logging in</a> to see this entry, you\'ll be brought back to this page after logging in.', 0, $mid);
		}
		break;
		case -400:
		bailout('Sorry, but this entry has not yet been enabled. Please be patient, Median is working as fast as possible to get this entry ready!', $current_user['userid'], $mid);
		break;
		case 0:
		default:
		bailout('Sorry, but you are not allowed to view this entry for some reason.', $current_user['userid'], $mid);
	}
}

if (isset($media_info['pending']) && $media_info['pending'] == true) {
	bailout('Sorry, but this entry is pending, meaning the uploader has not actually submtited the wizard.', $current_user['userid'], $mid);
}

// password check!
if (isset($media_info['pwd']) && isset($_POST['p'])) {
	// check password!
	$pwd_check = checkMediaPassword($mid, trim($_POST['p']));
	if ($pwd_check != true) {
		bailout('Sorry, but the password you entered is incorrect.', $current_user['userid'], $mid);
	}
} else if (isset($media_info['pwd']) && !isset($_POST['p']) && $current_user['userlevel'] > 1) {
	// show password form
	require_once('/median-webapp/www/password.php');
	die();
}

// permission checks
$can_user_edit = canEditMedia($current_user['userid'], $media_info['mid']);
$can_user_download = canDownloadMedia($current_user['userid'], $media_info['mid']);

// update view count
updateViewCount($mid, $current_user['userid']);

// check if an iOS device is viewing this...
$is_ios = false;
if (stripos($_SERVER['HTTP_USER_AGENT'], 'ipad')) {
	$is_ios = true;
	$is_ipad = true;
	$is_iphone = false;
} else if (stripos($_SERVER['HTTP_USER_AGENT'], 'iphone')) {
	$is_ios = true;
	$is_iphone = true;
	$is_ipad = false;
}

$can_be_html5 = canBeHTML5($mid);
if ($media_info['mt'] == 'clip') {
	$can_be_html5 = false;
}

$page_uuid = 'player-page';
$page_title = $media_info['ti']. ' - Median - '.$median_institution_name;
require_once('/median-webapp/includes/header.php');
?>

<?php if ($current_user['userlevel'] == 1) { echo '<!-- '.print_r($media_info, true).' -->'; } ?>

<div class="row">
    <div class="column full">

        <div class="entry-tags">

            <?php
            // go through and display the "tags" for the entry
			// categories, user and group owners (who SHOULD be "seen"), classes, date added

			$users = array();
			$groups = array();

			if (isset($media_info['ow']['s']) && count($media_info['ow']['s']) > 0) {
				// ok so show who they want to be shown as the owner
				if ($media_info['ow']['s']['t'] == 'g') {
					// show the group
					$groups = getGroupNames(array($media_info['ow']['s']['id']));
				} else if ($media_info['ow']['s']['t'] == 'u') {
					// show the user
					$users = getUserNames(array($media_info['ow']['s']['id']));
				}
			} else {
				// otherwise just show them all
				if (isset($media_info['ow']['g']) && count($media_info['ow']['g']) > 0) {
					// groups
					$groups = getGroupNames($media_info['ow']['g']);
				}
				if (isset($media_info['ow']['u']) && count($media_info['ow']['u']) > 0) {
					// users
					$users = getUserNames($media_info['ow']['u']);
				}
			}

			foreach ($groups as $group) {
				echo '<span class="group label clickable" data-id="'.$group['id'].'">'.$group['name'].'</span> ';
			}

			foreach ($users as $user) {
				echo '<span class="person label clickable" data-id="'.$user['id'].'">'.$user['name'].'</span> ';
			}

            // categories and classes
            if (isset($media_info['as'])) {
                if (isset($media_info['as']['ca'])) {
                    // categories
                    $categories = getCategoryNames($media_info['as']['ca']);
                    foreach ($categories as $category) {
                        echo '<span class="category label clickable" data-id="'.$category['id'].'">'.$category['name'].'</span> ';
                    }
                }
                if (isset($media_info['as']['cl'])) {
                    $current_semester = getCurrentSemester();
                    foreach ($media_info['as']['cl'] as $class) {
                        if ($class['s'] == $current_semester['academic_period']) {
                            echo '<span class="course label clickable" data-id="'.$class['c'].'">'.$class['c'].'</span> ';
                        }
                    }
                }
            }

			?>
			<span class="label date"><?php echo date('F jS, Y', $media_info['tsc']); ?></span>
        </div>

        <h2><?php echo $media_info['ti']; ?></h2>

		<?php

		/*

			start player selection and display based on media type and client capability

		*/

		if ($media_info['mt'] == 'video' || $media_info['mt'] == 'clip') { // videos

			if ($is_ios) {
				if (!$can_be_html5) {
					// sorry, no HTML5
					?>
					<div class="alert-box alert">Sorry, this entry cannot be played on iOS devices for security reasons. <a href="/help/medianmobile/">Read this help doc to find out why.</a></div>
					<?php
				} else {
					// ok, load up an HTML5 version of the video
					$file_location = getHTMLFIVElink($media_info['mid']);
					if ($file_location != false) {
						?>
						<div id="the-player">
						<video width="100%" height="300" controls="controls" preload="metadata">
							<source src="<?php echo $file_location; ?>"  />
							<div class="alert-box alert">Your browser or device does not support HTML5 H264 video, sorry.</div>
						</video>
						</div>
						<?php
					} else {
						?>
						<div class="alert-box alert">Sorry, I could not find a version of the video for your platform.</div>
						<?php
					}
				} // end HTML5 check
			} else {
				$manifest = getPlayerManifest($media_info['mid']);
				$flashvars = 'src='.$manifest;
				if (alwaysUseSubtitles($current_user['userid'])) {
					$flashvars .= '&cc=1';
				}
				?>
				<div id="the-player">
					<object width="100%" height="100%">
						<param name="movie" value="/m6_video_player.swf"></param>
						<param name="flashvars" value="<?php echo $flashvars; ?>"></param>
						<param name="allowFullScreen" value="true"></param>
						<param name="allowscriptaccess" value="always"></param>
						<param name="wmode" value="transparent"></param>
						<embed
							src="/m6_video_player.swf"
							type="application/x-shockwave-flash"
							allowscriptaccess="always"
							allowfullscreen="true"
							wmode="transparent"
							width="100%"
							height="100%"
							flashvars="<?php echo $flashvars; ?>">
						</embed>
					</object>
				</div>
				<?php
			} // end if iOS check
			?>
			<input type="hidden" id="video-currentTime" value="0" /><input type="hidden" id="video-duration" value="0" />
			<?php

		} else if ($media_info['mt'] == 'audio') { // audio

			// just display an <audio> tag for ALL devices
			$file_location = getHTMLFIVElink($media_info['mid']);
			if ($file_location != false) {
				?>
				<div id="the-player" class="audio-player">
					<audio height="30" width="100%" src="<?php echo $file_location; ?>" controls="controls" preload="metadata"><div class="alert-box alert">It seems your browser or device does not support audio, sorry. Please try using the latest Chrome, Firefox, or Safari.</div></audio>
				</div>
				<?php
			} else {
				?>
				<div class="alert-box alert">Sorry, I could not find a version of the audio for your platform.</div>
				<?php
			}

		} else if ($media_info['mt'] == 'image') { // image
			
			$file_location = getHTMLFIVElink($media_info['mid']);
			if ($file_location != false) {
				?>
				<div id="the-player" class="image-player"><a href="<?php echo $file_location; ?>"><img src="<?php echo $file_location; ?>" /></a></div>
				<?php
			} else {
				?>
				<div class="alert-box alert">Sorry, I could not find a version of the image for your platform.</div>
				<?php
			}

		} else if ($media_info['mt'] == 'doc') { // document
			?>
			<div id="the-player" class="doc">
				<div class="alert-box alert">This is a link to a document. Median does not screen or filter these documents, so please be careful.</div>
				<div><a class="button success large" target="_blank" href="/download/<?php echo $media_info['mid']; ?>">Download Document &raquo;</a></div>
			</div>
			<?php
		} else if ($media_info['mt'] == 'link') { // link
			?>
			<div id="the-player" class="link">
				<div class="alert-box alert">This is a link to an external site. Median does not screen or filter these links, so please be careful.</div>
				<div class="alert-box secondary">Site URL: <code><?php echo $media_info['url']; ?></code></div>
				<div><a class="button success large" href="<?php echo $media_info['url']; ?>" target="_blank">Go to site &raquo;</a></div>
			</div>
			<?php

		} else { // uhhhh unknown

			?>
			<div id="the-player">Uhhhhhh...</div>
			<?php

		} // end player selection and display

		?>

        <div class="views-view">
            <?php
            $view_count = 0;
            if (isset($media_info['vc']) && $media_info['vc'] > 0) {
                $view_count = $media_info['vc'];
            }
            ?>
            <a id="view-count" href="#"><?php echo number_format($view_count); ?> view<?php echo plural($view_count); ?></a>
        </div>

		<div id="views-graph" class="panel" style="display:none;">
			<div id="views-chart-container" style="width:600px;height:200px;"></div>
			<div>
			<p style="margin-bottom:10px;">Break down the chart by <select class="inline" id="breakdown-select"><option>day</option><option>week</option><option>month</option></select> <input class="inline" type="button" value="show &raquo;" id="breakdown-submit-btn" /></p>
			<p>Show views from last <input class="inline" type="number" id="relative-amount" value="7" /> <select class="inline" id="relative-unit"><option>days</option><option>weeks</option><option>months</option></select> <input class="inline" type="button" value="show &raquo;" id="relative-submit-btn" /></p>
			<p>Show views from <input class="inline" type="date" id="absolute-start" /> to <input class="inline" type="date" id="absolute-end" /> <input class="inline" type="button" value="show &raquo;" id="absolute-submit-btn" /></p>
			<p>Or... <a href="/views/download/<?php echo $mid; ?>/">click here to download all of the view data in CSV format</a> to use however you want.</p>
			</div>
		</div>

        <div class="sub-nav">
            <a href="#" data-panel-id="metadata" class="player-panel-active">Metadata / Comments</a>
			or <a href="#" data-panel-id="sharing">Sharing Options</a>
			or <a href="#" data-panel-id="download">Download</a>
			<?php if ($current_user['loggedin']) { ?>
			or <a href="#" data-panel-id="tools">Tools</a>
			<?php } ?>
			<?php if ($media_info['mt'] != 'link') { ?>
			or <a href="#" data-panel-id="advanced">Advanced Info</a>
			<?php } ?>
        </div>

		<div id="metadata" class="player-panel">

			<?php
			if ($can_user_edit) {
			?>
			<fieldset>
				<legend>Owner Options</legend>
				<p>
				<a href="/edit/media/<?php echo $mid; ?>/" class="button small success">Edit</a>
				<?php if ($media_info['mt'] == 'video' || $media_info['mt'] == 'audio' || $media_info['mt'] == 'image') { ?>
				<a href="/fileswap/<?php echo $mid; ?>/" class="button small">Swap File</a>
				<?php } ?>
				<a href="/delete/media/<?php echo $mid; ?>/" class="delete-major-btn button small alert">Delete</a>
				</p>
			</fieldset>
			<?php
			} // end can user edit check
			?>

			<h4>Metadata</h4>
			<table>
				<tr><th>Access</th><td><?php echo groupLevelToString($media_info['ul']); ?></td></tr>
				<tr><th>License</th><td><?php echo licenseString($media_info['li']); ?></td></tr>
				<?php
				$do_not_show_these_fields = array('intime', 'outtime', 'source_media_id', 'source_type', 'source_type_id');
				if (isset($media_info['me']) && count($media_info['me']) > 0) {
					foreach ($media_info['me'] as $meta_key => $meta_val) {
						if (in_array($meta_key, $do_not_show_these_fields)) {
							continue;
						}
						$display_meta = metaFieldDisplay(array('key' => $meta_key, 'val' => $meta_val));
						echo '<tr><th>'.$display_meta['key'].'</th><td>'.$display_meta['val'].'</td></tr>';
					}
				}
				?>
				<?php
				if (isset($media_info['co']) && $media_info['co'] == true) {
				?>
				<tr><th>Class only?</th><td>Yup.</td></tr>
				<?php
				}
				?>
				<?php
				if (isset($media_info['pword']) && is_array($media_info['pword'])) {
				?>
				<tr><th>Password protected?</th><td>Yup.</td></tr>
				<?php
				}
				?>
				<?php
				if (isset($media_info['as']['gr']) && is_array($media_info['as']['gr'])) {
				?>
				<tr><th>Restricted to a group?</th><td>Yup.</td></tr>
				<?php
				}
				?>
				<?php
				if (isset($media_info['ha']) && $media_info['ha'] == true) {
				?>
				<tr><th>Hidden?</th><td>Yup.</td></tr>
				<?php
				}
				?>
				<?php
				if ($can_user_edit) { // only owners can see this
				?>
				<tr><th>Downloads</th><td><?php echo $media_info['dc']; ?></td></tr>
				<?php
				}
				?>
			</table>
			<h4>Comments</h4>
			<div id="comments-list">
				<?php
				$media_comments = getMediaComments($media_info['mid']);
				if (count($media_comments) > 0) {
					foreach ($media_comments as $comment) {
						echo '<div class="comment">';
						echo '<p>';
						echo '<span class="person label clickable">'.getUserName($comment['uid']).'</span> ';
						echo '<span class="date label">'.date('n/j/Y', $comment['tsc']).'</span> ';
						if (isset($comment['tc']) && $comment['tc'] > 0) {
							echo '<span class="label">'.getTimeCodeFromSeconds($comment['tc']).'</span> ';
						}
						echo $comment['t'];
						if ($comment['uid'] == $current_user['userid']) {
							echo ' <a href="/do/delete_comment.php?id='.$comment['_id'].'" class="button small alert right">X</a>';
						}
						echo '</p>';
						echo '</div>';
					}
				}
				?>
			</div>
			<?php
			if ($current_user['loggedin']) {
			?>
			<div id="add-comment">
				<fieldset>
					<legend>Add a Comment!</legend>
					<textarea id="comment-text" placeholder="Comment here!"></textarea>
					<?php
					if ($media_info['mt'] == 'video') {
					?>
					<p>Comment Timecode (Optional): <input type="text" id="comment-timecode" style="width:75px;" /> <a href="#" id="comment-timecode-get" class="button secondary">Now!</a></p>
					<?php
					} // end if video check
					?>
					<input id="comment-submit" class="button medium" type="button" value="Add comment!" />
				</fieldset>
			</div>
			<?php
			} // end loggedin check for add comment form
			?>

        </div>

		<div id="sharing" class="player-panel" style="display: none;">
			<p>Permalink: <input type="text" id="media-permalink" value="<?php echo getPermalink($mid); ?>" /></p>
			<p>Embed code: <textarea id="media-embed"><?php
			$embed_frame = $medain_base_url.'embed/'.$mid.'/';
			$default_embed_width = 500;
			$default_embed_height = ($media_info['mt'] == 'audio') ? 25 : 400;
			echo '<iframe src="'.$embed_frame.'" frameborder="0" width="'.$default_embed_width.'" height="'.$default_embed_height.'" allowfullscreen></iframe>';
			?></textarea></p>
		</div>

		<div id="download" class="player-panel" style="display: none;">
			<?php
			if ($can_user_download && $media_info['mt'] != 'clip') {
			?>
			<p><a target="_blank" href="/download/<?php echo $mid; ?>/">Click here to download the source media for this entry!</a></p>
			<?php
			} else if ($media_info['mt'] == 'clip') {
			?>
			<p>Sorry, you are trying to download a clip of a larger entry. Please try downloading the original source entry <a href="/media/<?php echo $media_info['clip']['src']; ?>/">here</a>.</p>
			<?php
			} else {
			?>
			<p>Sorry, you do not have permission to download this entry.</p>
			<?php
			} // end if canDownloadMedia check
			?>
			<p><b>Who can download this entry?</b></p>
			<?php
			if ($media_info['ul'] > 0 && $media_info['ul'] < 6 && preg_match('/(copyright|und|undetermined|unknown)/', $media_info['li']) == 0) {
				if (isset($media_info['as']) && isset($media_info['as']['gr']) && count($media_info['as']['gr']) > 0) {
					?>
					<p>Right now it seems that <b>only members of a certain group</b> can download this entry because of its access level and licensing.</p>
					<?php
				} else if ($media_info['co'] == true) {
					?>
					<p>Right now it seems that <b>only members of a certain class</b> can download this entry because of its access level and licensing.</p>
					<?php
				} else {
					?>
					<p>Right now it seems that <b>only members of the Emerson community</b> can download this entry because of its access level and licensing.</p>
					<?php
				}
			} else if ($media_info['ul'] == 6 && preg_match('/(copyright|und|undetermined|unknown)/', $media_info['li']) == 0) {
				?>
				<p>Right now this entry can be downloaded by <b>anyone</b> because it's publicly accessible via its access level and licensing.</p>
				<?php
			} else {
				?>
				<p>Right now only the owners of the entry (whether users or groups) can download this entry because of how it has been restricted via licensing or access level.</p>
				<?php
			}
			?>
		</div>

		<?php
		if ($current_user['loggedin']) {
		?>
		<div id="tools" class="player-panel" style="display: none;">
			<fieldset>
				<legend>Add to...</legend>
					<div><a id="add-fav-btn" href="#" class="button small secondary">My Favorites</a></div>
					<?php
					$user_classes = getUserClasses($current_user['userid']);
					$all_classes = array();
					if (count($user_classes) == 2) {
						$all_classes = array_merge($user_classes['taking'], $user_classes['teaching']);
						if (count($all_classes) > 0) {
						?>
						<div>A class: <select id="add-to-class-id" class="three">
							<?php
							foreach ($all_classes as $class) {
								echo '<option value="'.$class['cc'].'">'.$class['name'].'</option>'."\n";
							}
							?>
						</select> <a id="add-to-class-btn" href="#" class="button small secondary">Add!</a></div>
						<?php
						}
					} // end class check
					?>
			</fieldset>

			<?php
			if ($can_user_edit) {
			?>
			
			<?php
			if ($media_info['mt'] == 'video' || $media_info['mt'] == 'clip') {
			?>
			<fieldset>
				<legend>Add Subtitles</legend>
				<p>Here you can upload an SRT (.srt) or WebVTT (.vtt) file to add subtitles for this entry. This overrides any current subtitles.</p>
				<form action="/do/subtitle_process.php" method="post" enctype="multipart/form-data">
					<input type="hidden" name="mid" value="<?php echo $mid; ?>" />
					<p><input type="file" name="subtitle-file" /></p>
					<div><input type="submit" value="Upload Subtitles" class="button success medium" /></div>
				</form>
				<p>Also, you have the option of removing the entry's subtitles: <a href="/do/delete_subtitles.php?mid=<?php echo $mid; ?>" class="button small alert">Remove Subtitles</a></p>
			</fieldset>
			<?php
			} // end if video check for subtitles
			?>
			
			<fieldset>
				<legend>Thumbnail Tools</legend>
				<p><a id="thumbnail-fix-btn" href="#" class="button small alert">Fix thumbnail</a><?php if ($media_info['mt'] == 'video') { ?> or <a id="thumbnail-random-btn" href="#" class="button small">New random thumbnail</a><?php } // end video check ?></p>
				<div id="thumbnail-result"></div>
				<p>Or... upload a custom thumbnail (JPG/JPEG only, less than 200kb)</p>
				<form id="upload-thumb-form" action="/do/thumbnail.php" method="post" enctype="multipart/form-data">
				<p><input type="file" name="thumb-file" id="thumb-file" /></p>
				<div><input type="button" id="thumbnail-upload-btn" value="Upload" class="button success medium" /></div>
				</form>
			</fieldset>
			<?php
			} // end if can user edit check
			?>
			<?php
			if ($media_info['mt'] == 'video') {
			?>
			<div id="clip-creation">
				<fieldset>
					<legend>Make a Clip!</legend>
					<label>Start in seconds or timecode (ex: 1:04:22)</label>
					<div class="row">
						<div class="five columns">
							<div class="row collapse">
							<div class="eight mobile-three columns">
								<input type="text" id="clip-start" />
							</div>
							<div class="four mobile-one columns">
								<a href="#" id="clip-get-start" class="postfix button secondary">Now!</a>
							</div>
							</div>
						</div>
					</div>
					<label>End in seconds or timecode (ex: 1:05:46)</label>
					<div class="row">
						<div class="five columns">
							<div class="row collapse">
							<div class="eight mobile-three columns">
								<input type="text" id="clip-end" />
							</div>
							<div class="four mobile-one columns">
								<a href="#" id="clip-get-end" class="postfix button secondary">Now!</a>
							</div>
							</div>
						</div>
					</div>
					<label>Clip Name</label>
					<input type="text" id="clip-name" value="Clip of <?php echo $media_info['ti']; ?>" />
					<input type="button" value="Make Clip!" class="button radius medium secondary" id="clip-submit" />
					<div id="clip-result"></div>
				</fieldset>
			</div>
			<?php
			} // end if video clip creation check
			?>
		</div>
		<?php
		} // end if logged in check
		?>

		<?php
		if ($media_info['mt'] != 'link') {
		?>
		<div id="advanced" class="player-panel" style="display: none;">
			<fieldset>
				<legend>Advanced Info</legend>
				<?php
				if ($media_info['mt'] == 'clip') {
				?>
				<p>Source entry: <a href="/media/<?php echo $media_info['clip']['src']; ?>">#<?php echo $media_info['clip']['src']; ?></a></p>
				<?php
				}
				?>
				<?php if ($current_user['userlevel'] == 1 && isset($media_info['pa']['in'])) { ?><p>In Path: <code><?php echo $media_info['pa']['in']; ?></code></p><?php } ?>
				<?php
				if (isset($media_info['pa']) && isset($media_info['pa']['c']) && is_array($media_info['pa']['c'])) {
					require_once('/median-webapp/includes/farm_functions.php');
				?>
				<table>
					<tr><th>Quality</th><th>Status</th><?php if ($current_user['userlevel'] == 1) { ?><th>Path</th><th>Remove?</th><?php } ?></tr>
					<?php
					// get media path info from farming!
					foreach ($media_info['pa']['c'] as $media_path) {
						if (!isset($media_path['p']) || !isset($media_path['b'])) {
							continue;
						}
						if (isset($media_path['e']) && $media_path['e'] == true) {
							$path_status = 'Ready';
						} else {
							$path_status = getFarmingStatusByOutPath($media_path['p']);
						}
						echo '<tr><td>'.$media_path['b'].'kbps ('.bitrateToFriendly($media_path['b']).')</td>';
						echo '<td>'.$path_status.'</td>';
						if ($current_user['userlevel'] == 1) {
							echo '<td><code>'.$media_path['p'].'</code></td>';
							echo '<td><a href="/do/delete_version.php?mid='.$mid.'&b='.$media_path['b'].'">remove this version</a></td>';
						}
						echo '</tr>'."\n";
					}
					?>
				</table>
				<?php
				} else if (isset($media_info['pa']) && isset($media_info['pa']['c']) && !is_array($media_info['pa']['c'])) {
				?>
				<p>Out Path: <code><?php echo $media_info['pa']['c']; ?></code></p>
				<?php
				} // end media path output check
				?>
			</fieldset>
			<?php
			if (canUseAmara($current_user['userid']) && $media_info['mt'] == 'video') {
			?>
			<fieldset>
				<legend>Make subtitles with Amara!</legend>
				<p>Median now stores captions locally instead of relying on Amara's servers. Make sure that every time you update subtitles on Amara, you <b>resync</b> them to Median below. If there's a problem with the video playback on Amara, try just breaking the link and resending.</p>
				<?php
				if (isset($media_info['sbx']) && isset($media_info['amara'])) {
					?>
					<p><a href="/amara_process.php?mid=<?php echo $mid; ?>&a=sd">Sync captions & break the Median-Amara link &raquo;</a> (Do this to close the potential security loophole; the video will still exist in Amara, but won't work anymore.)</p>
					<p><a href="/amara_process.php?mid=<?php echo $mid; ?>&a=d">Just break the Median-Amara link &raquo;</a> (Do this to close the potential security loophole; the video will still exist in Amara, but won't work anymore.)</p>
					<?php
				} else {
					?>
					<p><a href="/amara_process.php?mid=<?php echo $mid; ?>&a=n">Send this video to Amara for captioning &raquo;</a> (This will do all the work for you of sending the video URL, title, duration, and thumbnail to Amara; it should show up in the Emerson team.)</p>
					<?php
				} // end sandbox check
				?>
				<p><a href="/admin/amara_resync.php?mid=<?php echo $mid; ?>">Just resync captions from Amara &raquo;</a></p>
			</fieldset>
			<?php
			}
			?>
			<?php
			if (canUseAkamai($current_user['userid']) && $media_info['mt'] == 'video') {
			?>
			<fieldset>
				<legend>Mirror with Akamai!</legend>
				<p>This will send you to the Median-Akamai Manager and pre-fill the required info. It is recommended that you do not add the entry to Akamai until at least the SD version of the video is ready.</p>
				<?php if (isMediaInAkamai($mid)) { ?>
				<p><a href="/akamai/admin/?t=r&mid=<?php echo $mid; ?>" class="button alert small">Remove from Akamai &raquo;</a></p>
				<?php } else { ?>
				<p><a href="/akamai/admin/?t=a&mid=<?php echo $mid; ?>" class="button small">Mirror with Akamai &raquo;</a></p>
				<?php } ?>
			</fieldset>
			<?php
			} // can use akamai tools check
			?>
		</div>
		<?php
		} // end if NOT a link check for advanced tools
		?>

    </div>
</div>

<input type="hidden" id="mid" value="<?php echo $mid; ?>" />

<?php
require_once('/median-webapp/includes/footer.php');
