<?php

/*

	EDIT MEDIA
		cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/error_functions.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
	bailout('No valid ID provided.', $current_user['userid']);
}

$mid = (int) $_GET['id'] * 1;

require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/group_functions.php');

$user_name = getUserName($current_user['userid']);

$media_info = getMediaInfo($mid);

if ($media_info == false) {
	// uhh media does not exist!
	bailout('Sorry, but the media entry with that ID does not exist!', $current_user['userid'], $mid);
}

// permission checks
$can_user_edit = canEditMedia($current_user['userid'], $mid);

if (!$can_user_edit) {
	bailout('Sorry, but you do not have permission to edit this media entry.', $current_user['userid'], $mid);
}

$page_uuid = 'edit-page';
$page_title = 'Editing '.$media_info['ti'];
require_once('/median-webapp/includes/header.php');
?>

<?php echo '<!-- '.print_r($media_info, true).' -->'; ?>

<div class="row">
    <div class="column full">

        <h2>Edit Media Entry</h2>

        <form action="/submit/edit/media/" method="post" id="the-form">

        <input type="hidden" name="mid" value="<?php echo $mid; ?>" />

        <div class="edit-panel" id="media-title">
            <div class="row">
                <div class="column third"><label class="right">Title:</label></div>
                <div class="column two-thirds">
                    <input type="text" name="title" value="<?php echo $media_info['ti']; ?>" />
                </div>
            </div>
        </div>

        <?php
        if ($media_info['mt'] == 'link') {
        ?>
        <div class="edit-panel" id="media-link">
            <div class="row">
                <div class="column third"><label class="right">URL:</label></div>
                <div class="column two-thirds">
                    <input type="text" name="url" value="<?php echo $media_info['url']; ?>" />
                </div>
            </div>
        </div>
        <?php
        }
        ?>

        <?php

        /*

                the organization step

        */

        ?>

        <div id="organization" class="edit-panel">
            <!-- category, class, event, etc -->

            <h3>Organization</h3>

            <?php
            // insert already-selected classes
            if (isset($media_info['as']['cl']) && count($media_info['as']['cl']) > 0) {
                $current_semester_code = getCurrentSemesterCode();
                foreach ($media_info['as']['cl'] as $selected_class) {
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
                    <select name="class[]">
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
            // insert already-selected categories
            if (isset($media_info['as']['ca']) && count($media_info['as']['ca']) > 0) {
                foreach ($media_info['as']['ca'] as $selected_cat) {
                    $cat_info = getCategoryInfo($selected_cat);
                    ?>
                    <div class="row">
                        <div class="column third"><label class="right inline">Category:</label></div>
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
                    <select name="cat[]">
                        <?php
                        if (count($media_info['as']['ca']) > 0) {
                        ?>
                        <option value="0" selected="selected">None</option>
                        <?php
                        }
                        ?>
                        <option value="1">Uncategorized</option>
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
            // insert already-selected events
            if (isset($media_info['as']['ev']) && count($media_info['as']['ev']) > 0) {
                foreach ($media_info['as']['ev'] as $selected_event) {
                    $event_info = getEventInfo($selected_event);
                    ?>
                    <div class="row">
                        <div class="column third"><label class="right inline">Event:</label></div>
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
                    <select name="event[]">
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
            // insert already-selected playlists
            if (isset($media_info['as']['pl']) && count($media_info['as']['pl']) > 0) {
                foreach ($media_info['as']['pl'] as $selected_playlist) {
                    $playlist_info = getPlaylistInfo($selected_playlist);
                    ?>
                    <div class="row">
                        <div class="column third"><label class="right inline">Playlist:</label></div>
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
                <div class="column two-thirds"><input name="tags" type="text" value="<?php echo ((isset($media_info['as']['tg'])) ? implode(', ', $media_info['as']['tg']) : ''); ?>" placeholder="tag, tag two, another tag" /></div>
            </div>

        </div>

        <?php

        /*

                the metadata step

        */

        ?>

        <div id="metadata" class="edit-panel">

            <h3>Media Info</h3>

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
                foreach ($media_info['me'] as $selected_metadata_key => $selected_metadata_val) {
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

            <p><a href="#" class="button small add-field-btn" data-context="normal">Add another field +</a> <a href="#" class="button small add-field-btn" data-context="custom">Add another <i>custom</i> field +</a></p>

        </div>

        <?php

        /*

                the access settings step

        */

        ?>

        <div class="row">
            <div class="column third" id="access-results-sidebar">
                <div id="access-results" class="panel">
                    <p>Here's how your media is protected. These will change as you alter the Privacy & Ownership settings.</p>
                    <p class="robot-icon-header"><img class="robot-icon" src="/images/robot-head.png" /> Visible To:</p>
                    <ul id="access-results-visible"></ul>
                    <p class="robot-icon-header"><img class="robot-icon" src="/images/robot-head-eyesclosed.png" /> Hidden From:</p>
                    <ul id="access-results-hidden"></ul>
                </div>
            </div>

            <div class="column two-thirds">

                <div id="access-settings" class="edit-panel">

                    <h3>Privacy & Ownership</h3>

                    <!-- ownership of the file (user and/or group) -->

                    <?php
                    if (isset($media_info['ow']['u']) && count($media_info['ow']['u']) > 0) {
                        foreach ($media_info['ow']['u'] as $selected_user_owner) {
                            ?>
                            <div class="row">
                                <div class="column third"><label class="right inline">User Owner(s):</label></div>
                                <div class="column two-thirds"><input name="userowner[]" class="owner-field user-owner-field" type="text" placeholder="type a username here" value="<?php echo getUserName($selected_user_owner); ?>" /> <a href="#" class="button success small add-another" data-context="user-owner">Add another &raquo;</a> <a href="#" class="button alert small remove-other" data-context="user-owner">&times;</a></div>
                            </div>
                            <?php
                        }
                    }
                    ?>

                    <div class="row">
                        <div class="column third"><label class="right inline">User Owner(s):</label></div>
                        <div class="column two-thirds"><input name="userowner[]" class="owner-field user-owner-field" type="text" placeholder="type a username here" /> <a href="#" class="button success small add-another" data-context="user-owner">Add another &raquo;</a>  <a href="#" class="button alert small remove-other" data-context="user-owner">&times;</a></div>
                    </div>

                    <?php
                    if (isset($media_info['ow']['g']) && count($media_info['ow']['g']) > 0) {
                        foreach ($media_info['ow']['g'] as $selected_group_owner) {
                            $group_info = getGroupInfo($selected_group_owner);
                            ?>
                            <div class="row">
                                <div class="column third"><label class="right inline">Group Owner(s):</label></div>
                                <div class="column two-thirds">
                                    <input type="hidden" name="groupowner[]" class="owner-field group-owner-field" data-group-name="<?php echo $group_info['n']; ?>" value="<?php echo $selected_group_owner; ?>" /> <?php echo $group_info['n']; ?> <a href="#" class="button alert small remove-preselected" data-context="group-owner">&times;</a>
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
                            <select class="owner-field group-owner-field" name="groupowner[]">
                                <option value="0" selected="selected">None</option>
                                <?php
                                foreach ($user_groups as $group) {
                                    echo '<option value="'.$group['gid'].'">'.$group['n'].'</option>'."\n";
                                }
                                ?>
                            </select> <a href="#" class="button success small add-another" data-context="group-owner">Add another &raquo;</a>  <a href="#" class="button alert small remove-other" data-context="group-owner">&times;</a>
                        </div>
                    </div>
                    <?php
                    }
                    ?>

                    <hr />

                    <!-- who is shown as "owner" -->

                    <div class="row">
                        <div class="column third"><label class="right">Show Owner As:</label></div>
                        <div class="column two-thirds">
                            <label for="show-default"><input name="show-owner" type="radio" id="show-default" value="0" checked="checked"> Default, show all owners.</label>
                            <div id="show-owner-options">
                                <?php
                                if (isset($media_info['ow']['u']) && count($media_info['ow']['u']) > 0) {
                                    foreach ($media_info['ow']['u'] as $selected_user_owner) {
                                        $username = getUserName($selected_user_owner);
                                        ?>
                                        <label for="show-u-<?php echo $selected_user_owner; ?>">
                                        <input name="show-owner" type="radio" value="<?php echo $username; ?>" id="show-u-<?php echo $selected_user_owner; ?>" <?php echo ((isset($media_info['ow']['s']) && $media_info['ow']['s']['t'] == 'u' && $media_info['ow']['s']['id'] == $selected_user_owner) ? ' checked="checked"' : ''); ?> />
                                        <?php echo $username; ?>
                                        </label>
                                        <?php
                                    }
                                }

                                if (isset($media_info['ow']['g']) && count($media_info['ow']['g']) > 0) {
                                    foreach ($media_info['ow']['g'] as $selected_group_owner) {
                                        $group_info = getGroupInfo($selected_group_owner);
                                        ?>
                                        <label for="show-g-<?php echo $selected_group_owner; ?>">
                                        <input name="show-owner" type="radio" value="<?php echo $selected_group_owner; ?>" id="show-g-<?php echo $selected_group_owner; ?>" <?php echo ((isset($media_info['ow']['s']) && $media_info['ow']['s']['t'] == 'g' && $media_info['ow']['s']['id'] == $selected_group_owner) ? ' checked="checked"' : ''); ?> />
                                        <?php echo $group_info['n']; ?>
                                        </label>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <hr />

                    <!-- licensing -->

                    <?php
                    $license_pieces = explode('_', $media_info['li']);
                    $creative_commons = false;
                    if (substr($license_pieces[1], 0, 2) == 'cc') {
                        $creative_commons = true;
                    }
                    ?>

                    <div class="panel"><p class="panel-info">Please note that the licensing settings affect who may view the entry. For more information on Copyright and Fair Use, check out our <a href="http://fairuse.emerson.edu/" target="_blank">Fair Use page</a></p></div>

                    <div class="row padbot">
                        <div class="column third"><label class="right">Original Media Owner:</label></div>
                        <div class="column two-thirds">
                            <label for="owner-else"><input class="access-result-trigger" name="media-owner" value="else" type="radio" id="owner-else" <?php echo (($license_pieces[0] == 'else') ? ' checked="checked"': ''); ?>> Someone else owns the rights to this media <span class="has-tip tip-bottom" data-width="200" title="Select this if you are uploading something you bought on DVD, for example.">(?)</span></label>
                            <label for="owner-me"><input class="access-result-trigger" name="media-owner" value="me" type="radio" id="owner-me" <?php echo (($license_pieces[0] == 'me' || $license_pieces[0] == 'my') ? ' checked="checked"': ''); ?>> I own the rights to this media or have a license for it <span class="has-tip tip-bottom" data-width="200" title="Did you make what you're uploading? Are you the original author or creator, or have permission from them?">(?)</span></label>
                        </div>
                    </div>

                    <div class="row padbot">
                        <div class="column third"><label class="right">License Type:</label></div>
                        <div class="column two-thirds">
                            <label for="license-unknown"><input class="access-result-trigger" name="license-type" type="radio" value="unknown" id="license-unknown" <?php echo (($license_pieces[1] == 'unknown') ? ' checked="checked"': ''); ?>> Unknown / I don't know <span class="has-tip tip-bottom" data-width="200" title="Functionally, this will treat your entry as a copyrighted work, just to be safe.">(?)</span></label>
                            <label for="license-copyright"><input class="access-result-trigger" name="license-type" type="radio" value="copyright" id="license-copyright"<?php echo (($license_pieces[1] == 'copyright') ? ' checked="checked"': ''); ?>> Copyright  <span class="has-tip tip-bottom" data-width="200" title="Please check out our Fair Use page (see above) for more info about using copyrighted works.">(?)</span></label>
                            <label for="license-cc"><input class="access-result-trigger" name="license-type" type="radio" value="cc" id="license-cc"<?php echo (($creative_commons) ? ' checked="checked"': ''); ?>> Creative Commons</label>
                            <label for="license-public"><input class="access-result-trigger" name="license-type" type="radio" value="public" id="license-public"<?php echo (($license_pieces[1] == 'public') ? ' checked="checked"': ''); ?>> Public Domain</label>
                        </div>
                    </div>

                    <div class="row padbot" id="license-cc-options" <?php if (!$creative_commons) { ?> style="display:none;"<?php } ?>>
                        <div class="column third"><label class="right">Creative Commons: <a href="http://creativecommons.org/licenses/" target="_blank">(?)</a></label></div>
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
                        <div class="column two-thirds"><input name="license-holder" class="five" type="text" value="<?php echo ((isset($media_info['me']['copyright_holder'])) ? $media_info['me']['copyright_holder'] : ''); ?>" /></div>
                    </div>

                    <div class="row" id="license-year-row" <?php if (!$creative_commons && $license_pieces[1] != 'copyright') { ?>style="display:none;"<?php } ?>>
                        <div class="column third"><label class="right inline">License Year:</label></div>
                        <div class="column two-thirds"><input name="license-year" class="five" type="text" value="<?php echo ((isset($media_info['me']['copyright_yr'])) ? $media_info['me']['copyright_yr'] : ''); ?>" /></div>
                    </div>

                    <hr />

                    <!-- access level -->

                    <div class="row">
                        <div class="column third"><label class="right inline">Restricted to: <span class="has-tip tip-bottom" data-width="200" title="The options here are affected by the license type you chose.">(?)</span></label></div>
                        <?php
                        // if user default is not public-compatible, then don't show the public option, let javascript create it
                        ?>
                        <div class="column two-thirds">
                            <select class="access-result-trigger" id="access-options" name="access-level">
                                <option value="0"<?php echo (($media_info['ul'] == 0) ? ' selected="selected"': ''); ?>>Only those who own the entry</option>
                                <?php if ($current_user['userlevel'] == 1) { ?><option value="1"<?php echo (($media_info['ul'] == 1) ? ' selected="selected"': ''); ?>>Admins Only</option><?php } ?>
                                <?php if ($current_user['userlevel'] <= 5) { ?><option value="4"<?php echo (($media_info['ul'] == 4) ? ' selected="selected"': ''); ?>>Faculty Only</option><?php } ?>
                                <option value="5"<?php echo (($media_info['ul'] == 5) ? ' selected="selected"': ''); ?>>Just Logged-in People</option>
                                <?php if ($license_pieces[1] != 'unknown' || ($license_pieces[1] != 'copyright' && $license_pieces[0] != 'else')) { ?><option value="6" id="public-access-option"<?php echo (($media_info['ul'] == 6) ? ' selected="selected"': ''); ?>>Publicly Accessible</option><?php } ?>
                            </select>
                        </div>
                    </div>

                    <!-- group restriction, password protection, hidden -->

                    <?php
                    if (isset($media_info['as']['gr']) && count($media_info['as']['gr']) > 0) {
                        foreach ($media_info['as']['gr'] as $selected_group_restrict) {
                            $group_info = getGroupInfo($selected_group_restrict);
                            ?>
                            <div class="row">
                                <div class="column third"><label class="right inline">Restrict to group: <span class="has-tip tip-bottom" data-width="200" title="ONLY people in this group will be able to view it.">(?)</span></label></div>
                                <div class="column two-thirds">
                                    <input type="hidden" name="grouprestrict[]" value="<?php echo $selected_group_restrict; ?>" /> <?php echo $group_info['n']; ?> <a href="#" class="button alert small remove-preselected" data-context="group-restrict">&times;</a>
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
                            <select class="five access-result-trigger" name="grouprestrict[]">
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
                        <div class="column third"><label class="right inline">Password protect: <span class="has-tip tip-bottom" data-width="200" title="Adding a password requires everyone except owners input the password before viewing.">(?)</span></label></div>
                        <div class="column two-thirds"><input id="entry-password" name="pword" class="five access-result-trigger" type="password" <?php echo ((isset($media_info['pwd'])) ? ' value="1"': ''); ?> /> (Leave the password field alone to keep the current password, or delete it to have no password.)</div>
                    </div>

                    <div class="row">
                        <div class="column third"><label class="right inline">Hidden: <span class="has-tip tip-bottom" data-width="200" title="This option hides the entry from every listing on Median, making it only accessible via direct link.">(?)</span></label></div>
                        <div class="column two-thirds"><input id="entry-hidden" class="access-result-trigger" name="hide-entry" value="1" type="checkbox" <?php echo ((isset($media_info['ha']) && $media_info['ha'] == true) ? ' checked="checked"': ''); ?> /></div>
                    </div>

                    <?php
                    if (count($all_classes) > 0) {
                    ?>
                    <div class="row">
                        <div class="column third"><label class="right inline">Class Only: <span class="has-tip tip-bottom" data-width="200" title="This option makes the entry only accessible to people in the classes you've selected in the Organization section.">(?)</span></label></div>
                        <div class="column two-thirds"><input id="entry-classonly" class="access-result-trigger" name="class-only" value="1" type="checkbox" <?php echo ((isset($media_info['co']) && $media_info['co'] == true) ? ' checked="checked"': ''); ?> /></div>
                    </div>
                    <?php
                    }
                    ?>

                </div>

            </div>
        </div>

        <div class="edit-panel">
        <input type="submit" value="Save Changes" class="button large success" />
        </div>

        </form>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
