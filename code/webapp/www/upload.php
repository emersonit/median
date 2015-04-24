<?php

/*

    ONE UPLOADER TO RULE THEM ALL.
        cyle gage, emerson college, 2014


	this uploader has a few custom uploaders built-in based on the URL path
	you probably don't want them; but use them as templates for your own!

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

// include what's needed
require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/common_functions.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/group_functions.php');
require_once('/median-webapp/includes/error_functions.php');
require_once('/median-webapp/includes/log_functions.php');

// sorry -- IE not allowed for uploading
if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
	bailout('Sorry, but the Median Uploader is not compatible with Internet Explorer. Please use Chrome, Firefox, or Safari.', $current_user['userid']);
}

// get their username for use later
$user_name = getUserName($current_user['userid']);

// these'll help figure out when to use the "special" uploaders
$is_emchan = false;
$is_canvas = false;
$is_evvys = false;

// check for special uploader condition
if (isset($_GET['special']) && trim($_GET['special']) != '') {

	if (strtolower(trim($_GET['special'])) == 'emchan') {
		$is_emchan = true;
	}

	if (strtolower(trim($_GET['special'])) == 'canvas' && isset($_GET['r']) && trim($_GET['r']) != '') {
		$is_canvas = true;
		$canvas_return_url = trim($_GET['r']);
	}

	if (strtolower(trim($_GET['special'])) == 'evvys') {
		$is_evvys = true;
		if (isset($_GET['ID']) && trim($_GET['ID']) != '') {
			$evvys_id = trim($_GET['ID']);
		} else {
			$evvys_id = 0;
		}
	}

} // end check for special uploader triggers

// get user's defaults, if any
$user_defaults = array();
$user_info = getUserInfo($current_user['userid']);

/*

	when using a custom uploader,
	you can set its own custom defaults

*/
if ($is_emchan) {
	// emerson channel has its own defaults
	$user_defaults['li'] = 'me_copyright';
	$user_defaults['ul'] = 6;
	$user_defaults['as'] = array();
	$user_defaults['as']['ca'] = array(209);
	$user_defaults['me'] = array();
	$user_defaults['me']['copyright_holder'] = 'Emerson Channel';
	$user_defaults['me']['copyright_yr'] = date('Y');
	$user_defaults['ow'] = array();
	$user_defaults['ow']['g'] = array(18);
	$user_defaults['ha'] = true;
} else if ($is_evvys) {
	// evvys has its own defaults
	$user_defaults['li'] = 'else_unknown';
	$user_defaults['ul'] = 6;
	$user_defaults['as'] = array();
	$user_defaults['as']['ca'] = array(1);
	$user_defaults['me'] = array();
	$user_defaults['ow'] = array();
	$user_defaults['ow']['g'] = array(62);
	$user_defaults['pwd'] = 'evvys33emerson';
	$user_defaults['ha'] = true;
} else {
	if (isset($user_info['d']) && is_array($user_info['d'])) {
		$user_defaults = $user_info['d'];
	} else {
        // these are the default defaults, lol
		$user_defaults['as'] = array();
		$user_defaults['me'] = array();
		$user_defaults['ow'] = array();
		$user_defaults['li'] = 'else_unknown';
		$user_defaults['ul'] = 5;
	}
}

// this text will show up at the end of each section as a disclaimer
$pre_submit_text = '<div class="panel">
	<p>By clicking <b>Submit</b> you are submitting your media to Median and agreeing to the <a href="'.$electronic_policy_page_url.'" target="_blank">Electronic Information Policy</a>.</p>
	<p>The uploading and streaming of full-length copyrighted works using Median without the express written permission of the copyright holder is an infringement of copyright law and is not permitted. Please read the <a href="'.$copyright_policy_page_url.'" target="_blank">Intellectual Property Policy</a> and learn more at the <a href="http://fairuse.emerson.edu/" target="_blank">Fair Use</a> helper page.</p>
</div>';

$page_uuid = 'upload-page';
$page_title = 'The Uploader - Median - '.$median_institution_name; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
	<div class="column full">
		<h2>The Uploader</h2>
	</div>
</div>

<div class="row">
    <!-- start of uploader sidebar -->
    <div class="column third" id="uploader-sidebar">

        <ul id="upload-nav">
			<li class="active"><a href="#upload" data-id="file-list">Upload Something</a></li>
			<li><a href="#link" data-id="link">... or a Link</a></li>
			<?php
			if (!$is_evvys) {
			?>
			<li><a href="#organization" data-id="organization">Organization</a></li>
			<li><a href="#meta" data-id="metadata">Media Info</a></li>
			<li><a href="#access" data-id="access-settings">Privacy & Ownership</a></li>
			<li><a href="#defaults" data-id="defaults-settings">Your Defaults</a></li>
			<?php
			} // end if evvys check
			?>
		</ul>

		<div id="access-results" class="panel" style="display: none;">
			<p>Here's how your media is protected. These will change as you alter the settings to the right.</p>
			<p class="robot-icon-header"><img class="robot-icon" src="/images/robot-head.png" /> Visible To:</p>
			<ul id="access-results-visible"></ul>
			<p class="robot-icon-header"><img class="robot-icon" src="/images/robot-head-eyesclosed.png" /> Hidden From:</p>
			<ul id="access-results-hidden"></ul>
		</div>

    </div>
    <!-- end of uploader sidebar -->
    <!-- start of uploader main area -->
    <div class="column two-thirds">

        <?php

		/*

				the file uploading step

		*/

		?>

		<form action="/submit/" method="post" id="the-form">

        <div id="file-list" class="upload-panel">
            <div id="upload-step">
                <input class="upload-field" type="hidden" name="user_id" value="<?php echo $current_user['userid']; ?>" />
                <div class="panel">
        			<p class="panel-info">The only thing that's required for the whole upload form is this step. Use the sections in the left-side menu to add more information about your entries.</p>
        			<?php
        			if ($is_emchan) {
        			?>
        			<p>Also, this is the uploader for <b>The Emerson Channel</b>, which means you can only upload <b>one file</b> and it will have special defaults that you don't need to change. When you complete this uploader, you'll be sent back to the Emerson Channel. If you arrived here accidentally, <a href="/upload/" target="_top">go back to the normal Uploader</a>.</p>
        			<?php
        			} else if ($is_canvas) {
        			?>
        			<p>Also, this is the uploader for <b>Canvas</b>, which means you'll only be able to upload <b>one file</b> and then it'll send you back to Canvas. If you arrived here accidentally, <a href="/upload/" target="_top">go back to the normal Uploader</a>.</p>
        			<p>If the embed which gets placed into Canvas after uploading gives you a message saying your entry is not enabled, please just wait a bit! Median is working as fast as possible to encode your entry!</p>
        			<?php
        			} else if ($is_evvys) {
        			?>
        			<p>Also, this is the uploader for <b>the EVVYs</b>, which means you can only upload <b>one file</b> and it will have special defaults that you cannot change. When you complete this uploader, you'll be sent back to the EVVYs submission system. If you arrived here accidentally, <a href="/upload/" target="_top">go back to the normal Uploader</a>.</p>
        			<?php
        			} // end special uploaders check
        			?>
        			<p>Median accepts video files (MP4, MOV, AVI, WMV, MPG), audio files (MP3), image files (JPG, GIF, PNG), and document files (DOC, DOCX, XLS, XLSX, PDF, TXT, RTF). While there is no technical upload limit, it's usually difficult to upload more than <b>4GB</b> of files at once.</p>
        		</div>
                <div id="file-entry-list">
        			<div class="file-entry">
        				<div class="remove-this-file">&times;</div>
        				<p>Select a file to upload! <input name="upload-file[]" class="upload-file" type="file" /></p>
        				<p>And a title for it: <input name="upload-title[]" class="upload-field" type="text" placeholder="Title of entry" /></p>
        			</div>
        			<div id="file-entry-template" style="display:none;">
        				<div class="remove-this-file">&times;</div>
        				<p>And another file! <input name="upload-file[]" class="upload-file" type="file" /></p>
        				<p>And a title for it: <input name="upload-title[]" class="upload-field" type="text" placeholder="Title of entry" /></p>
        			</div>
        		</div>
                <?php
        		if ($is_canvas == false && $is_emchan == false && $is_evvys == false) {
        		?>
        		<div class="panel">
        			<p>You can add <b>up to 5 files</b> in a single batch upload job. Please be aware all the metadata and other settings will apply to all files submitted at once. You can edit these settings after uploading.</p>
        		</div>
        		<p><a href="#" class="button small" id="add-another-file">Add another file to upload!</a></p>
        		<p>When you are done adding files, upload them:</p>
        		<?php
        		}
        		?>
                <p><input type="button" class="button large success" value="Upload!" id="upload-btn" /></p>
            </div>

            <div id="uploading-step" style="display:none;">
                <div class="panel">
                    <p><b>Uploading!</b> Please be patient. How long this takes depends on the size of the files you've selected and your connection speed.</p>
                </div>
                <div id="progress-bar-outside"><div id="progress-par-inside"></div></div>
                <p id="upload-debug-txt"></p>
            </div>

            <div id="uploaded-step" style="display:none;">
                <p><b>Your media is done uploading.</b> Check out the status of each upload below. Now you can either accept the default Median settings for your files and be done, or edit the info for the entries you've just uploaded. If you uploaded videos, you can check out their transcoding status on <a href="/farming/" target="_blank">the Farm page</a> (opens in a new window).</p>
        		<div id="upload-result"></div>
        		<?php echo $pre_submit_text; ?>
        		<p><a href="#" class="button large success submit-form">I'm done! Submit!</a> <?php if (!$is_evvys) { ?><a href="#" class="button large next-step" data-next-step="organization">Edit Info & Settings &raquo;</a><?php } // end if evvys check ?></p>
            </div>

            <div id="error-step" style="display:none;">
                <p>There was an error of some kind! It's possible the upload function is not available.</p>
            </div>

        </div>

        <?php

		/*

				the link step

		*/

		?>

        <div id="link" class="upload-panel" style="display:none;">

			<div class="panel">
				<p class="panel-info">Instead of uploading a file, you can submit a link instead.</p>
			</div>

			<div class="alert-box warning">Please note that adding a URL here replaces any files you may have uploaded in the "Upload Something" step.</div>

			<div class="row">
				<div class="column third">
					<label class="right">Title:</label>
				</div>
				<div class="column two-thirds">
					<input name="title" type="text" />
				</div>
			</div>

			<div class="row">
				<div class="column third">
					<label class="right">URL:</label>
				</div>
				<div class="column two-thirds">
					<input id="the-link" name="the-link" type="text" placeholder="http://www.emerson.edu/" />
				</div>
			</div>

			<hr />
			<p>You can edit some more settings, or you can be done with the uploader now!</p>
			<?php echo $pre_submit_text; ?>
			<p><a href="#" class="button large success submit-form">I'm done! Submit!</a> <?php if (!$is_evvys) { ?><a href="#" class="button large next-step" data-next-step="organization">Edit Info & Settings &raquo;</a><?php } // end if evvys check ?></p>

		</div>

        <?php

		/*

				the organization step

		*/

		?>

		<div id="organization" class="upload-panel" style="display:none;">
			<div class="panel"><p class="panel-info">Put your entries in categories or add them to classes or submit them to events!</p></div>

			<?php
			// insert default-selected classes
			if (isset($user_defaults['as']['cl']) && count($user_defaults['as']['cl']) > 0) {
				$current_semester_code = getCurrentSemesterCode();
				foreach ($user_defaults['as']['cl'] as $selected_class) {
					if ($selected_class['s'] == $current_semester_code) {
						$class_info = getClassInfo($selected_class['c']);
						?>
						<div class="row" class="class-row">
							<div class="column third"><label class="right">Class:</label></div>
							<div class="column two-thirds">
								<input type="hidden" name="class[]" value="<?php echo $selected_class['c']; ?>" />
								<?php echo $selected_class['c'].': '.$class_info['ct']; ?> <a href="#" class="button alert small remove-preselected" data-context="class">&times;</a>
							</div>
						</div>
						<?php
					}
				}
			}
			?>

			<?php
			$user_classes = getUserClasses($current_user['userid']);
			$all_classes = array();
			if (count($user_classes) == 2) {
				$all_classes = array_merge($user_classes['taking'], $user_classes['teaching']);
			?>
			<div class="row" class="class-row">
				<div class="column third"><label class="right">Class:</label></div>
				<div class="column two-thirds">
					<select name="class[]" class="five">
						<option value="0" selected="selected">None</option>
						<?php
						foreach ($all_classes as $class) {
							echo '<option value="'.$class['cc'].'">'.$class['cc'].': '.$class['name'].'</option>'."\n";
						}
						?>
					</select> <a href="#" class="button success small add-another" data-context="class">Add another &raquo;</a> <a href="#" class="button alert small remove-other" data-context="class">&times;</a>
				</div>
			</div>
			<?php
			} // end classes check
			?>

			<?php
			// insert default-selected categories
			if (isset($user_defaults['as']['ca']) && count($user_defaults['as']['ca']) > 0) {
				foreach ($user_defaults['as']['ca'] as $selected_cat) {
					$cat_info = getCategoryInfo($selected_cat);
					?>
					<div class="row">
						<div class="column third"><label class="right">Category:</label></div>
						<div class="column two-thirds">
							<input type="hidden" name="cat[]" value="<?php echo $selected_cat; ?>" />
							<?php echo $cat_info['ti']; ?> <a href="#" class="button alert small remove-preselected" data-context="cat">&times;</a>
						</div>
					</div>
					<?php
				}
			}
			?>

			<?php
			$cats = getCategories($current_user['userid'], true);
			if (count($cats) > 0) {
			?>
			<div class="row">
				<div class="column third"><label class="right">Category:</label></div>
				<div class="column two-thirds">
					<select class="five" name="cat[]">
						<option value="1" selected="selected">Uncategorized</option>
						<?php
						foreach ($cats as $cat) {
							if (isset($cat['pid']) || $cat['id'] == 1) {
								continue;
							}
							echo '<option value="'.$cat['id'].'">'.$cat['ti'].'</option>';
							foreach ($cats as $subcat) {
								if (isset($subcat['pid']) && $subcat['pid'] == $cat['id']) {
									echo '<option value="'.$subcat['id'].'"> - '.$subcat['ti'].'</option>';
									foreach ($cats as $subsubcat) {
										if (isset($subsubcat['pid']) && $subsubcat['pid'] == $subcat['id']) {
											echo '<option value="'.$subsubcat['id'].'"> - - '.$subsubcat['ti'].'</option>';
										}
									}
								}
							}
						}
						?>
					</select> <a href="#" class="button success small add-another" data-context="cat">Add another &raquo;</a>  <a href="#" class="button alert small remove-other" data-context="cat">&times;</a>
				</div>
			</div>
			<?php
			} // end cats check
			?>

			<?php
			// insert default-selected events
			if (isset($user_defaults['as']['ev']) && count($user_defaults['as']['ev']) > 0) {
				foreach ($user_defaults['as']['ev'] as $selected_event) {
					$event_info = getEventInfo($selected_event);
					?>
					<div class="row">
						<div class="column third"><label class="right">Event:</label></div>
						<div class="column two-thirds">
							<input type="hidden" name="event[]" value="<?php echo $selected_event; ?>" />
							<?php echo $event_info['ti']; ?> <a href="#" class="button alert small remove-preselected" data-context="event">&times;</a>
						</div>
					</div>
					<?php
				}
			}
			?>

			<?php
			$events = getEvents($current_user['userid'], true);
			if (count($events) > 0) {
				//echo '<pre>'.print_r($events, true).'</pre>';
			?>
			<div class="row">
				<div class="column third"><label class="right">Event:</label></div>
				<div class="column two-thirds">
					<select class="five" name="event[]">
						<option value="0" selected="selected">None</option>
						<?php
						foreach ($events as $event) {
							echo '<option value="'.$event['id'].'">'.$event['ti'].'</option>'."\n";
						}
						?>
					</select> <a href="#" class="button success small add-another" data-context="event">Add another &raquo;</a>  <a href="#" class="button alert small remove-other" data-context="event">&times;</a>
				</div>
			</div>
			<?php
			} // end events check
			?>

			<?php
			// insert default-selected playlists
			if (isset($user_defaults['as']['pl']) && count($user_defaults['as']['pl']) > 0) {
				foreach ($user_defaults['as']['pl'] as $selected_playlist) {
					$playlist_info = getPlaylistInfo($selected_playlist);
					?>
					<div class="row">
						<div class="column third"><label class="right">Playlist:</label></div>
						<div class="column two-thirds">
							<input type="hidden" name="playlist[]" value="<?php echo $selected_playlist; ?>" />
							<?php echo $playlist_info['ti']; ?> <a href="#" class="button alert small remove-preselected" data-context="playlist">&times;</a>
						</div>
					</div>
					<?php
				}
			}
			?>

			<?php
			// get playlists
			$user_playlists = getAllPlaylists($current_user['userid']);
			if (count($user_playlists) > 0) {
			?>
			<div class="row" class="playlist-row">
				<div class="column third"><label class="right">Playlist:</label></div>
				<div class="column two-thirds">
					<select name="playlist[]">
						<option value="0" selected="selected">None</option>
						<?php
						foreach ($user_playlists as $playlist) {
							$playlist_source = 'Unknown';
							if (isset($playlist['uid'])) {
								$playlist_source = 'Yours';
							} else if (isset($playlist['gid'])) {
								$group_info = getGroupInfo($playlist['gid']);
								$playlist_source = 'Group: '.$group_info['n'];
							} else if (isset($playlist['clid'])) {
								$playlist_source = 'Class: '.$playlist['clid']['c'];
							}
							echo '<option value="'.$playlist['id'].'">'.$playlist['ti'].' ('.$playlist_source.')</option>'."\n";
						}
						?>
					</select> <a href="#" class="button success small add-another" data-context="playlist">Add another &raquo;</a> <a href="#" class="button alert small remove-other" data-context="playlist">&times;</a>
				</div>
			</div>
			<?php
			}
			?>

			<div class="row">
				<div class="column third"><label class="right inline">Tags:</label></div>
				<div class="column two-thirds"><input name="tags" type="text" value="<?php echo ((isset($user_defaults['as']['tg'])) ? implode(', ', $user_defaults['as']['tg']) : ''); ?>" placeholder="tag, tag two, another tag" /></div>
			</div>

			<hr />
			<p>You can edit some more settings, or you can be done with the uploader now!</p>
			<?php echo $pre_submit_text; ?>
			<p><a href="#" class="button large success submit-form">I'm done! Submit!</a> <a href="#" class="button large next-step" data-next-step="metadata">Edit Media Info &raquo;</a></p>

		</div>

		<?php

		/*

				the metadata step

		*/

		?>

		<div id="metadata" class="upload-panel" style="display:none;">
			<div class="panel"><p class="panel-info">Fill in some metadata for the entry, or leave it blank to have none. Up to you. Keep adding as many metadata fields as you want. Median has built-in fields, but you can add custom ones!</p></div>

			<div id="metadata-list">

				<?php
				$meta_fields = getMetaDataList();
				$meta_field_codes = array();
				foreach ($meta_fields as $field) {
					$meta_field_codes[] = $field['id'];
				}
				unset($field);
				?>

				<?php
				// parse through existing fields
				$do_not_edit_these_fields = array('intime', 'outtime', 'source_media_id', 'source_type', 'source_type_id', 'copyright_yr', 'copyright_holder');
				foreach ($user_defaults['me'] as $selected_metadata_key => $selected_metadata_val) {
					if (in_array($selected_metadata_key, $do_not_edit_these_fields)) {
						continue;
					}
					if (in_array($selected_metadata_key, $meta_field_codes)) {
						?>
						<div class="row field-row">
							<div class="column third">
								<select class="meta-field-name" name="fieldname[]">
									<?php
									foreach ($meta_fields as $field) {
										echo '<option value="'.$field['id'].'"'.(($selected_metadata_key == $field['id']) ? ' selected="selected"' : '').'>'.$field['d'].'</option>'."\n";
									}
									unset($field);
									?>
								</select>
							</div>
							<div class="column two-thirds"><div class="remove-this-field">&times;</div><input name="fieldval[]" type="text" class="eleven meta-field-input" value="<?php echo $selected_metadata_val; ?>" /></div>
						</div>
						<?php
					} else {
						?>
						<div class="row custom-field-row">
							<div class="column third"><input type="text" value="<?php echo $selected_metadata_key; ?>" placeholder="field name" name="custom_fieldname[]" /></div>
							<div class="column two-thirds"><div class="remove-this-field">&times;</div><input type="text" class="eleven" name="custom_fieldval[]" value="<?php echo $selected_metadata_val; ?>" /></div>
						</div>
						<?php
					}
				}
				?>


				<div class="row field-row">
					<div class="column third">
						<select class="meta-field-name" name="fieldname[]">
							<?php
							foreach ($meta_fields as $field) {
								echo '<option value="'.$field['id'].'">'.$field['d'].'</option>'."\n";
							}
							?>
						</select>
					</div>
					<div class="column two-thirds"><div class="remove-this-field">&times;</div><input name="fieldval[]" type="text" class="eleven meta-field-input" /></div>
				</div>
				<div class="row custom-field-row">
					<div class="column third"><input type="text" placeholder="field name" name="custom_fieldname[]" /></div>
					<div class="column two-thirds"><div class="remove-this-field">&times;</div><input type="text" class="eleven" name="custom_fieldval[]" /></div>
				</div>
			</div>

			<div class="row">
				<div class="column full">
					<p><a href="#" class="button small add-field-btn" data-context="normal">Add another field +</a> <a href="#" class="button small add-field-btn" data-context="custom">Add another <i>custom</i> field +</a></p>
				</div>
			</div>

			<hr />
			<p>You can edit some more settings, or you can be done with the uploader now!</p>
			<?php echo $pre_submit_text; ?>
			<p><a href="#" class="button large success submit-form">I'm done! Submit!</a> <a href="#" class="button large next-step" data-next-step="access-settings">Edit Privacy & Ownership &raquo;</a></p>

		</div>

		<?php

		/*

				the access settings step

		*/

		?>

		<div id="access-settings" class="upload-panel" style="display:none;">

			<div class="panel"><p class="panel-info">First of all, who do you want to own these entries? Owners can always view, edit, and download the entries. You can add in as many as you want. Note: users have to have logged into median at least once to be listed.</p></div>

			<!-- ownership of the file (user and/or group) -->


			<?php
			// get default user owners
			if (isset($user_defaults['ow']['u']) && count($user_defaults['ow']['u']) > 0) {
				foreach ($user_defaults['ow']['u'] as $selected_user_owner) {
					?>
					<div class="row">
						<div class="column third"><label class="right">User Owner(s):</label></div>
						<div class="column two-thirds"><input name="userowner[]" class="five inline owner-field user-owner-field" type="text" placeholder="type a username here" value="<?php echo getUserName($selected_user_owner); ?>" /> <a href="#" class="button success small add-another" data-context="user-owner">Add another &raquo;</a> <a href="#" class="button alert small remove-other" data-context="user-owner">&times;</a></div>
					</div>
					<?php
				}
			} else {
			?>
			<div class="row">
				<div class="column third"><label class="right">User Owner(s):</label></div>
				<div class="column two-thirds">
                    <input name="userowner[]" class="five inline owner-field user-owner-field" value="<?php echo $user_name; ?>" type="text" placeholder="type a username here" />
                    <a href="#" class="button success small add-another" data-context="user-owner">Add another &raquo;</a>
                    <a href="#" class="button alert small remove-other" data-context="user-owner">&times;</a>
                </div>
			</div>
			<?php
			}
			?>

			<?php
			// get default group owners
			if (isset($user_defaults['ow']['g']) && count($user_defaults['ow']['g']) > 0) {
				foreach ($user_defaults['ow']['g'] as $selected_group_owner) {
					$group_info = getGroupInfo($selected_group_owner);
					?>
					<div class="row">
						<div class="column third"><label class="right inline">Group Owner(s):</label></div>
						<div class="column two-thirds">
							<input type="hidden" name="groupowner[]" class="owner-field group-owner-field" data-group-name="<?php echo $group_info['n']; ?>" value="<?php echo $selected_group_owner; ?>" /> <?php echo $group_info['n']; ?> <a href="#" class="button alert  small remove-preselected" data-context="group-owner">&times;</a>
						</div>
					</div>
					<?php
				}
			}
			?>

			<?php
			$user_groups = getUserGroups($current_user['userid']);
			if (count($user_groups) > 0) {
				//echo '<pre>'.print_r($user_groups, true).'</pre>';
			?>
			<div class="row">
				<div class="column third"><label class="right inline">Group Owner(s):</label></div>
				<div class="column two-thirds">
					<select class="five owner-field group-owner-field" name="groupowner[]">
						<option value="0" selected="selected">None</option>
						<?php
						foreach ($user_groups as $group) {
							echo '<option value="'.$group['gid'].'">'.$group['n'].'</option>'."\n";
						}
						?>
					</select> <a href="#" class="button success small  add-another" data-context="group-owner">Add another &raquo;</a> <a href="#" class="button alert small remove-other" data-context="group-owner">&times;</a>
				</div>
			</div>
			<?php
			}
			?>

			<hr />

			<!-- who is shown as "owner" -->

			<div class="panel"><p class="panel-info">Who do you want to be <i>shown</i> as the owner?</p></div>

			<div class="row">
				<div class="column third"><label class="right">Show Owner As:</label></div>
				<div class="column two-thirds">
					<label for="show-default"><input name="show-owner" type="radio" id="show-default" value="0" checked="checked"> Default, show all owners.</label>
					<div id="show-owner-options">
						<label for="show-u-<?php echo $current_user['userid']; ?>"><input name="show-owner" type="radio" value="<?php echo $user_name; ?>" id="show-u-<?php echo $current_user['userid']; ?>" /> <?php echo $user_name; ?></label>
						<?php
						if (isset($user_defaults['ow']['u']) && count($user_defaults['ow']['u']) > 0) {
							foreach ($user_defaults['ow']['u'] as $selected_user_owner) {
								$username = getUserName($selected_user_owner);
								?>
								<label for="show-u-<?php echo $selected_user_owner; ?>"><input name="show-owner" type="radio" value="<?php echo $username; ?>" id="show-u-<?php echo $selected_user_owner; ?>" <?php echo ((isset($user_defaults['ow']['s']) && $user_defaults['ow']['s']['t'] == 'u' && $user_defaults['ow']['s']['id'] == $selected_user_owner) ? ' checked="checked"' : ''); ?> /> <?php echo $username; ?></label>
								<?php
							}
						}

						if (isset($user_defaults['ow']['g']) && count($user_defaults['ow']['g']) > 0) {
							foreach ($user_defaults['ow']['g'] as $selected_group_owner) {
								$group_info = getGroupInfo($selected_group_owner);
								?>
								<label for="show-g-<?php echo $selected_group_owner; ?>"><input name="show-owner" type="radio" value="<?php echo $selected_group_owner; ?>" id="show-g-<?php echo $selected_group_owner; ?>" <?php echo ((isset($user_defaults['ow']['s']) && $user_defaults['ow']['s']['t'] == 'g' && $user_defaults['ow']['s']['id'] == $selected_group_owner) ? ' checked="checked"' : ''); ?> /> <?php echo $group_info['n']; ?></label>
								<?php
							}
						}
						?>
					</div>
				</div>
			</div>

			<hr />

			<div class="panel"><p class="panel-info">How would you like to license these entries, or how are they already licensed? Please note that this setting effects who may view the entry. For more information on Copyright and Fair Use, check out our <a href="http://fairuse.emerson.edu/" target="_blank">Fair Use page</a></p></div>

			<?php
			$license_pieces = explode('_', $user_defaults['li']);
			$creative_commons = false;
			if (substr($license_pieces[1], 0, 2) == 'cc') {
				$creative_commons = true;
			}
			?>

			<div class="row padbot">
				<div class="column third"><label class="right">Original Media Owner:</label></div>
				<div class="column two-thirds">
					<div><label for="owner-else"><input class="access-result-trigger" name="media-owner" value="else" type="radio" id="owner-else" <?php echo (($license_pieces[0] == 'else') ? ' checked="checked"': ''); ?>> Someone else owns the rights to this media <span class="has-tip tip-bottom" data-width="200" title="Select this if you are uploading something you bought on DVD, for example.">(?)</span></label></div>
					<div><label for="owner-me"><input class="access-result-trigger" name="media-owner" value="me" type="radio" id="owner-me" <?php echo (($license_pieces[0] == 'me') ? ' checked="checked"': ''); ?>> I own the rights to this media or have a license for it <span class="has-tip tip-bottom" data-width="200" title="Did you make what you're uploading? Are you the original author or creator, or have permission from them?">(?)</span></label></div>
				</div>
			</div>

			<div class="row padbot">
				<div class="column third"><label class="right inline">License Type:</label></div>
				<div class="column two-thirds">
					<div><label for="license-unknown"><input class="access-result-trigger" name="license-type" type="radio" value="unknown" id="license-unknown" <?php echo (($license_pieces[1] == 'unknown') ? ' checked="checked"': ''); ?>> Unknown / I don't know <span class="has-tip tip-bottom" data-width="200" title="Functionally, this will treat your entry as a copyrighted work, just to be safe.">(?)</span></label></div>
					<div><label for="license-copyright"><input class="access-result-trigger" name="license-type" type="radio" value="copyright" id="license-copyright"<?php echo (($license_pieces[1] == 'copyright') ? ' checked="checked"': ''); ?>> Copyright <span class="has-tip tip-bottom" data-width="200" title="Please check out our Fair Use page (see above) for more info about using copyrighted works.">(?)</span></label></div>
					<div><label for="license-cc"><input class="access-result-trigger" name="license-type" type="radio" value="cc" id="license-cc"<?php echo (($creative_commons) ? ' checked="checked"': ''); ?>> Creative Commons</label></div>
					<div><label for="license-public"><input class="access-result-trigger" name="license-type" type="radio" value="public" id="license-public"<?php echo (($license_pieces[1] == 'public') ? ' checked="checked"': ''); ?>> Public Domain</label></div>
				</div>
			</div>

			<div class="row padbot" id="license-cc-options"<?php if (!$creative_commons) { ?> style="display:none;"<?php } ?>>
				<div class="column third"><label class="right inline">Creative Commons: <a href="http://creativecommons.org/licenses/" target="_blank">(?)</a></label></div>
				<div class="column two-thirds">
					<label for="license-cc-by"><input name="license-type-cc" type="radio" id="license-cc-by" value="cc-by"<?php echo (($license_pieces[1] == 'cc-by') ? ' checked="checked"': ''); ?>> Attribution</label>
					<label for="license-cc-nd"><input name="license-type-cc" type="radio" id="license-cc-nd" value="cc-nd"<?php echo (($license_pieces[1] == 'cc-nd') ? ' checked="checked"': ''); ?>> Attribution No-derivatives</label>
					<label for="license-cc-sa"><input name="license-type-cc" type="radio" id="license-cc-sa" value="cc-sa"<?php echo (($license_pieces[1] == 'cc-sa') ? ' checked="checked"': ''); ?>> Attribution Share-alike</label>
					<label for="license-cc-by-nc"><input name="license-type-cc" type="radio" id="license-cc-by-nc" value="cc-by-nc"<?php echo (($license_pieces[1] == 'cc-by-nc') ? ' checked="checked"': ''); ?>> Attribution Non-commercial</label>
					<label for="license-cc-by-nc-nd"><input name="license-type-cc" type="radio" id="license-cc-by-nc-nd" value="cc-by-nc-nd"<?php echo (($license_pieces[1] == 'cc-by-nc-nd') ? ' checked="checked"': ''); ?>> Attribution Non-commercial No-derivatives</label>
					<label for="license-cc-by-nc-sa"><input name="license-type-cc" type="radio" id="license-cc-by-nc-sa" value="cc-by-nc-sa"<?php echo (($license_pieces[1] == 'cc-by-nc-sa') ? ' checked="checked"': ''); ?>> Attribution Non-commercial Share-alike</label>
				</div>
			</div>

			<div class="row" id="license-holder-row" <?php if (!$creative_commons && $license_pieces[1] != 'copyright') { ?>style="display:none;"<?php } ?>>
				<div class="column third"><label class="right inline">License Holder:</label></div>
				<div class="column two-thirds"><input name="license-holder" class="five" type="text" value="<?php echo ((isset($user_defaults['me']['copyright_holder'])) ? $user_defaults['me']['copyright_holder'] : ''); ?>" /></div>
			</div>

			<div class="row" id="license-year-row" <?php if (!$creative_commons && $license_pieces[1] != 'copyright') { ?>style="display:none;"<?php } ?>>
				<div class="column third"><label class="right inline">License Year:</label></div>
				<div class="column two-thirds"><input name="license-year" class="five" type="text" value="<?php echo ((isset($user_defaults['me']['copyright_yr'])) ? $user_defaults['me']['copyright_yr'] : ''); ?>" /></div>
			</div>

			<hr />

			<div class="panel"><p class="panel-info">Do you want stricter protection on who can see these entries? Please note: These options <b>cascade</b>, meaning anyone who tries to view your entry must meet <b>every piece of criteria</b> before being able to see it. (This does not effect owners.)</p></div>

			<!-- access level -->

			<div class="row">
				<div class="column third"><label class="right inline">Restricted to: <span class="has-tip tip-bottom" data-width="200" title="The options here are affected by the license you chose.">(?)</span></label></div>
				<?php
				// if user default is not public-compatible, then don't show the public option, let javascript create it
				?>
				<div class="column two-thirds">
					<select class="five access-result-trigger" id="access-options" name="access-level">
						<option value="0"<?php echo (($user_defaults['ul'] == 0) ? ' selected="selected"': ''); ?>>Only those who own the entry</option>
						<?php if ($current_user['userlevel'] == 1) { ?><option value="1"<?php echo (($user_defaults['ul'] == 1) ? ' selected="selected"': ''); ?>>Admins Only</option><?php } ?>
						<?php if ($current_user['userlevel'] <= 5) { ?><option value="4"<?php echo (($user_defaults['ul'] == 4) ? ' selected="selected"': ''); ?>>Faculty Only</option><?php } ?>
						<option value="5"<?php echo (($user_defaults['ul'] == 5) ? ' selected="selected"': ''); ?>>Just Logged-in People</option>
						<?php if ($user_defaults['li'] != 'else_unknown') { ?><option value="6" id="public-access-option"<?php echo (($user_defaults['ul'] == 6) ? ' selected="selected"': ''); ?>>Publicly Accessible</option><?php } ?>
					</select>
				</div>
			</div>

			<!-- group restriction, password protection, hidden -->

			<?php
			if (isset($user_defaults['me']['gr']) && count($user_defaults['me']['gr']) > 0) {
				foreach ($user_defaults['me']['gr'] as $selected_group_restrict) {
					$group_info = getGroupInfo($selected_group_restrict);
					?>
					<div class="row">
						<div class="column third"><label class="right inline">Restrict to group: <span class="has-tip tip-bottom" data-width="200" title="ONLY people in this group will be able to view it.">(?)</span></label></div>
						<div class="column two-thirds">
							<input class="group-restrict-box" type="hidden" name="grouprestrict[]" value="<?php echo $selected_group_restrict; ?>" /> <?php echo $group_info['n']; ?> <a href="#" class="button alert  small remove-preselected" data-context="group-restrict">&times;</a>
						</div>
					</div>
					<?php
				}
			}
			?>

			<?php
			if (count($user_groups) > 0) {
			?>
			<div class="row">
				<div class="column third"><label class="right inline">Restrict to group: <span class="has-tip tip-bottom" data-width="200" title="ONLY people in this group will be able to view it.">(?)</span></label></div>
				<div class="column two-thirds">
					<select class="five access-result-trigger group-restrict-box" name="grouprestrict[]">
						<option value="0" selected="selected">None</option>
						<?php
						foreach ($user_groups as $group) {
							echo '<option value="'.$group['gid'].'">'.$group['n'].'</option>'."\n";
						}
						?>
					</select> <a href="#" class="button success small add-another" data-context="group-restrict">Add another &raquo;</a>  <a href="#" class="button alert small remove-other" data-context="group-restrict">&times;</a>
				</div>
			</div>
			<?php
			}
			?>

			<div class="row">
				<div class="column third"><label class="right inline">Password protect: <span class="has-tip tip-bottom" data-width="200" title="Adding a password requires everyone (except owners) input the password before viewing.">(?)</span></label></div>
				<div class="column two-thirds"><input id="entry-password" class="access-result-trigger" name="pword" class="five" type="password" <?php echo ((isset($user_defaults['pwd'])) ? $user_defaults['pwd'] : ''); ?> /></div>
			</div>

			<div class="row">
				<div class="column third"><label class="right inline">Hidden: <span class="has-tip tip-bottom" data-width="200" title="This option hides the entry from every listing, making it only accessible via direct link.">(?)</span></label></div>
				<div class="column two-thirds"><input id="entry-hidden" class="access-result-trigger" name="hide-entry" value="1" type="checkbox" <?php echo ((isset($user_defaults['ha']) && $user_defaults['ha'] == true) ? ' checked="checked"': ''); ?> /></div>
			</div>

			<?php
			if (count($all_classes) > 0) {
			?>
			<div class="row">
				<div class="column third"><label class="right inline">Class Only: <span class="has-tip tip-bottom" data-width="200" title="This option makes the entry only accessible to people in the classes you've selected in the Organization section.">(?)</span></label></div>
				<div class="column two-thirds"><input id="entry-classonly" class="access-result-trigger" name="class-only" value="1" type="checkbox" <?php echo ((isset($user_defaults['co']) && $user_defaults['co'] == true) ? ' checked="checked"': ''); ?> /></div>
			</div>
			<?php
			}
			?>

			<hr />
			<p>You can edit some more, or you can be done with the uploader now!</p>
			<?php echo $pre_submit_text; ?>
			<p><a href="#" class="button  large success submit-form">I'm done! Submit!</a> <a href="#" class="button  large next-step" data-next-step="defaults-settings">Edit Defaults &raquo;</a></p>

		</div>

		<?php

		/*

				the defaults step

		*/

		?>

		<div id="defaults-settings" class="upload-panel" style="display:none;">

			<!-- defaults -->

			<div class="panel"><p class="panel-info">Here, if you would like, you may set the current settings of this form as your defaults. Please be aware that <b>every time</b> you use the Median Uploader it will populate the form with these settings. These settings include everything under the <b>Organization</b> and <b>Privacy & Ownership</b> sections.</p></div>

			<div class="row">
				<div class="column full">
					<div><label for="defaults-save"><input name="defaults-option" value="save" type="radio" id="defaults-save" /> Save these settings as my defaults.</label></div>
					<div><label for="defaults-clear"><input name="defaults-option" value="clear" type="radio" id="defaults-clear" /> Clear my defaults entirely.</label></div>
					<div><label for="defaults-nothing"><input name="defaults-option" value="0" type="radio" id="defaults-nothing" checked="checked" /> Do nothing with my defaults, keep them as they are.</label></div>
				</div>
			</div>

			<hr />
			<p>You can edit some more, or you can be done with the uploader now!</p>
			<?php echo $pre_submit_text; ?>
			<p><a href="#" class="button large success submit-form">I'm done! Submit!</a> <a href="#" class="button  large next-step" data-next-step="organization">Edit Organization &raquo;</a></p>

		</div>

        <?php
		// special uploader?
		if ($is_emchan) {
			echo '<input type="hidden" name="is_emchan" value="1" />'."\n";
		}
		if ($is_evvys) {
			echo '<input type="hidden" name="is_evvys" value="1" />'."\n";
			echo '<input type="hidden" name="evvys_id" value="'.$evvys_id.'" />'."\n";
		}
		if ($is_canvas) {
			echo '<input type="hidden" name="is_canvas" value="1" />'."\n";
			echo '<input type="hidden" name="return_url" value="'.$canvas_return_url.'" />'."\n";
		}
		?>

        </form>


    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
