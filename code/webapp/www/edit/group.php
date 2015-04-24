<?php

/*

	EDIT GROUP
		cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/error_functions.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
	bailout('No valid ID provided.', $current_user['userid']);
}

$gid = (int) $_GET['id'] * 1;

require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/group_functions.php');

$meta_info = getGroupInfo($gid);

if ($meta_info == false) {
	// uhh media does not exist!
	bailout('Sorry, but a group with that ID does not exist!', $current_user['userid']);
}

// permission checks
$can_user_edit = canEditGroup($current_user['userid'], $gid);

if (!$can_user_edit) {
	bailout('Sorry, but you do not have permission to edit this group.', $current_user['userid']);
}

$page_uuid = 'edit-group-page';
$page_title = 'Editing '.$meta_info['n'];
require_once('/median-webapp/includes/header.php');
?>

<?php echo '<!-- '.print_r($meta_info, true).' -->'; ?>

<div class="row">
    <div class="column full">

        <h2>Edit Group</h2>

        <form action="/submit/edit/group/" method="post" id="group-form" enctype="multipart/form-data">
        <input type="hidden" name="gid" value="<?php echo $gid; ?>" />
        <div class="row">
            <div class="column third">
                <label class="inline right">Name:</label>
            </div>
            <div class="column two-thirds">
                <input type="text" value="<?php echo $meta_info['n']; ?>" id="group-name" placeholder="The Cool Kids Group" name="n" />
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="inline right">Visibility:</label>
            </div>
            <div class="column two-thirds">
                <select class="five" name="ul">
                    <option value="0"<?php echo (($meta_info['v'] == 0) ? ' selected="selected"': ''); ?>>Members Only</option>
                    <?php if ($current_user['userlevel'] == 1) { ?><option value="1"<?php echo (($meta_info['v'] == 1) ? ' selected="selected"': ''); ?>>Admins Only</option><?php } ?>
                    <?php if ($current_user['userlevel'] < 5) { ?><option value="4"<?php echo (($meta_info['v'] == 4) ? ' selected="selected"': ''); ?>>Faculty Only</option><?php } ?>
                    <option value="5"<?php echo (($meta_info['v'] == 5) ? ' selected="selected"': ''); ?>>Just Logged-in People</option>
                    <option value="6"<?php echo (($meta_info['v'] == 6) ? ' selected="selected"': ''); ?>>Publicly Visible</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="inline right">Brief Description / Tagline:</label>
            </div>
            <div class="column two-thirds">
                <input type="text" value="<?php if (isset($meta_info['sd'])) { echo $meta_info['sd']; } ?>" name="sd" />
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="inline right">Description:</label>
            </div>
            <div class="column two-thirds">
                <textarea name="d"><?php if (isset($meta_info['d'])) { echo $meta_info['d']; } ?></textarea>
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="inline right">iTunes Artwork:</label>
            </div>
            <div class="column two-thirds">
                <input type="file" name="a" /> (Optional; JPG only; 200kb limit; leave alone to keep existing.)
            </div>
        </div>

        <h4>Edit Members</h4>
        <div class="panel"><p>Start typing an ECNet name and select the user from the auto-complete list. Please note that users must have logged into median at least once to be included in that list. Selecting a user as an owner will allow them to edit the group info, member list, and everything associated with the group.</p></div>

        <div class="row">
            <div class="column third">
                <label class="inline right">Add Member:</label>
            </div>
            <div class="column two-thirds">
                <input type="text" id="add-member" />
            </div>
        </div>

		<div class="row">
			<div class="column third">
				&nbsp;
			</div>
			<div class="column two-thirds">
				<table id="members-list">
					<thead>
						<tr><th class="two">Owner?</th><th class="eight">Username</th><th class="two"></th></tr>
					</thead>
					<tbody>
						<?php
						$members = getGroupMembers($gid);
						$owners = getGroupOwners($gid, true);
						foreach ($members as $member) {
							echo '<tr>';
							echo '<td><input type="hidden" class="member-id" name="m[]" value="'.$member['uid'].'" /><input type="checkbox" '.((in_array($member['uid'], $owners)) ? 'checked="checked"': '').' class="owner-checkbox" name="o[]" value="'.$member['uid'].'" /></td>';
							echo '<td>'.$member['ecnet'].'</td>';
							echo '<td><input type="button" value="remove?" class="button small secondary remove-member-btn" /></td>';
							echo '</tr>';
						}
						?>
					</tbody>
				</table>
			</div>
		</div>

        <input type="submit" value="Save" class="button large" />
        </form>

        <hr />
        <h4>Delete Group</h4>
        <p>This will delete the group entirely. It will not delete any media, but will remove the group's ownership of media, events, and categories.</p>
        <a href="/delete/group/<?php echo $gid ?>/" class="delete-major-btn button small alert">Delete Group!</a>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
