<?php

/*

	EDIT CATEGORY
		cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/error_functions.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
	bailout('No valid ID provided.', $current_user['userid']);
}

$cid = (int) $_GET['id'] * 1;

require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/group_functions.php');

$meta_info = getCategoryInfo($cid);

if ($meta_info == false) {
	// uhh media does not exist!
	bailout('Sorry, but a category with that ID does not exist!', $current_user['userid'], $cid);
}

// permission checks
$can_user_edit = canEditCategory($current_user['userid'], $cid);

if (!$can_user_edit) {
	bailout('Sorry, but you do not have permission to edit this category.', $current_user['userid'], $cid);
}


$page_uuid = 'edit-category-page';
$page_title = 'Editing '.$meta_info['ti'];
require_once('/median-webapp/includes/header.php');
?>

<?php echo '<!-- '.print_r($meta_info, true).' -->'; ?>

<div class="row">
    <div class="column full">

        <h2>Edit Category</h2>

        <form action="/submit/edit/category/" method="post" id="cat-form" enctype="multipart/form-data">

        <input type="hidden" name="cid" value="<?php echo $cid; ?>" />

        <div class="row">
            <div class="column third">
                <label class="inline right">Name:</label>
            </div>
            <div class="column two-thirds">
                <input type="text" placeholder="Cool Student Media" id="cat-name" value="<?php echo $meta_info['ti']; ?>" name="n" />
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="inline right">Visibility:</label>
            </div>
            <div class="column two-thirds">
                <select class="five" name="ul_v">
                    <option value="0"<?php echo (($meta_info['ul_v'] == 0) ? ' selected="selected"': ''); ?>>Owners Only</option>
                    <?php if ($current_user['userlevel'] == 1) { ?><option value="1"<?php echo (($meta_info['ul_v'] == 1) ? ' selected="selected"': ''); ?>>Admins Only</option><?php } ?>
                    <?php if ($current_user['userlevel'] < 5) { ?><option value="4"<?php echo (($meta_info['ul_v'] == 4) ? ' selected="selected"': ''); ?>>Faculty Only</option><?php } ?>
                    <option value="5"<?php echo (($meta_info['ul_v'] == 5) ? ' selected="selected"': ''); ?>>Just Logged-in People</option>
                    <option value="6"<?php echo (($meta_info['ul_v'] == 6) ? ' selected="selected"': ''); ?>>Publicly Visible</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="inline right">Who can submit:</label>
            </div>
            <div class="column two-thirds">
                <select class="five" name="ul_s">
                    <option value="0"<?php echo (($meta_info['ul_s'] == 0) ? ' selected="selected"': ''); ?>>Owners Only</option>
                    <?php if ($current_user['userlevel'] == 1) { ?><option value="1"<?php echo (($meta_info['ul_s'] == 1) ? ' selected="selected"': ''); ?>>Admins Only</option><?php } ?>
                    <?php if ($current_user['userlevel'] < 5) { ?><option value="4"<?php echo (($meta_info['ul_s'] == 4) ? ' selected="selected"': ''); ?>>Faculty Only</option><?php } ?>
                    <option value="5"<?php echo (($meta_info['ul_s'] == 5) ? ' selected="selected"': ''); ?>>Any logged in user</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="inline right">Author (for iTunes feeds):</label>
            </div>
            <div class="column two-thirds">
                <input type="text" value="<?php if (isset($meta_info['at'])) { echo $meta_info['at']; } ?>" name="at" />
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
                <textarea name="d"><?php if (isset($meta_info['de'])) { echo $meta_info['de']; } ?></textarea>
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
        <h4>Edit Owners</h4>
        <div class="panel"><p>Start typing an ECNet name and select the user from the auto-complete list. Please note that users must have logged into median at least once to be included in that list. Owners can edit this category and all subcategories.</p></div>

        <?php
        if (count($meta_info['u_o']) > 0) {
            foreach ($meta_info['u_o'] as $user_owner) {
                ?>
                <div class="row">
                    <div class="column third"><label class="right inline">User Owner(s):</label></div>
                    <div class="column two-thirds"><input name="userowner[]" class="owner-field user-owner-field" type="text" value="<?php echo getUserName($user_owner); ?>" placeholder="type a username here" /> <a href="#" class="button success radius add-another" data-context="user-owner">Add another &raquo;</a>  <a href="#" class="button alert small remove-other" data-context="user-owner">&times;</a></div>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="row">
                <div class="column third"><label class="right inline">User Owner(s):</label></div>
                <div class="column two-thirds"><input name="userowner[]" class="owner-field user-owner-field" type="text" placeholder="type a username here" /> <a href="#" class="button success small add-another" data-context="user-owner">Add another &raquo;</a>  <a href="#" class="button alert small remove-other" data-context="user-owner">&times;</a></div>
            </div>
            <?php
        }
        ?>

        <?php
        foreach ($meta_info['g_o'] as $group_owner) {
            $group_info = getGroupInfo($group_owner);
            ?>
            <div class="row">
                <div class="column third"><label class="right inline">Group Owner(s):</label></div>
                <div class="column two-thirds">
                    <input type="hidden" name="groupowner[]" class="owner-field group-owner-field" data-group-name="<?php echo $group_info['n']; ?>" value="<?php echo $group_owner; ?>" /> <?php echo $group_info['n']; ?> <a href="#" class="button alert small remove-preselected" data-context="group-owner">&times;</a>
                </div>
            </div>
            <?php
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

        <input type="submit" value="Save" class="button large" />
        </form>

        <?php
        $subcats = getSubcategories($cid);
        if (count($subcats) > 0) {
            ?>
            <hr />
            <h4>Edit Subcategories</h4>
            <a href="/edit/subcats/<?php echo $cid ?>/" class="button small">Go to Subcategory Management &raquo;</a>
            <?php
        }
        ?>

        <hr />
        <h4>Delete Category</h4>
        <p>This will delete the category and all subcategories entirely. It will not delete any media, but will add those media to Uncategorized if they had no other category.</p>
        <a href="/delete/category/<?php echo $cid ?>/" class="delete-major-btn button small alert">Delete Category!</a>

    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
