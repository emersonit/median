<?php

/*

	EDIT CATEGORY'S SUBCATEGORIES
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

if (!canHaveSubcats($cid)) {
	bailout('Sorry, but this category cannot have subcategories attached to it.', $current_user['userid'], $cid);
}

$page_uuid = 'edit-category-page';
$page_title = 'Editing '.$meta_info['ti'].'\'s subcategories';
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">

        <h2>Edit "<?php echo $meta_info['ti']; ?>" Subcategories</h2>

        <?php
        $subcats = getSubcategories($cid);
        if (count($subcats) > 0) {
            echo '<table>';
            foreach ($subcats as $subcategory) {
                //echo '<pre>'.print_r($subcategory, true).'</pre>';
                echo '<tr>';
                echo '<td>'.$subcategory['ti'].'</td>';
                echo '<td><a href="/edit/category/'.$subcategory['id'].'" class="button small">edit</a></td>';
                echo '</tr>'."\n";
            }
            echo '</table>'."\n";
        } else {
            echo '<p>This category currently has no subcategories.</p>';
        }
        ?>

        <h2>Add Subcategory</h2>

        <form action="/submit/new/subcat/" method="post" id="cat-form" enctype="multipart/form-data">

        <input type="hidden" name="pid" value="<?php echo $cid; ?>" />

        <div class="row">
            <div class="column third">
                <label class="inline right">Name:</label>
            </div>
            <div class="column two-thirds">
                <input type="text" placeholder="Cool Student Media" id="cat-name" name="n" />
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="inline right">Visibility:</label>
            </div>
            <div class="column two-thirds">
                <select class="five" name="ul_v">
                    <option value="0">Owners Only</option>
                    <?php if ($current_user['userlevel'] == 1) { ?><option value="1">Admins Only</option><?php } ?>
                    <?php if ($current_user['userlevel'] < 5) { ?><option value="4">Faculty Only</option><?php } ?>
                    <option value="5" selected="selected">Just Logged-in People</option>
                    <option value="6">Publicly Visible</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="inline right">Who can submit:</label>
            </div>
            <div class="column two-thirds">
                <select class="five" name="ul_s">
                    <option value="0">Owners Only</option>
                    <?php if ($current_user['userlevel'] == 1) { ?><option value="1">Admins Only</option><?php } ?>
                    <?php if ($current_user['userlevel'] < 5) { ?><option value="4">Faculty Only</option><?php } ?>
                    <option value="5" selected="selected">Any logged in user</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="inline right">Brief Description / Tagline:</label>
            </div>
            <div class="column two-thirds">
                <input type="text" name="sd" />
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="inline right">Description:</label>
            </div>
            <div class="column two-thirds">
                <textarea name="d"></textarea>
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
        <h4>Add Owners to New Subcategory</h4>
        <div class="panel"><p>Start typing an ECNet name and select the user from the auto-complete list. Please note that users must have logged into median at least once to be included in that list.</p></div>

        <div class="row">
            <div class="column third"><label class="right">User Owner(s):</label></div>
            <div class="column two-thirds"><input name="userowner[]" class="owner-field user-owner-field" type="text" value="<?php echo getUserName($current_user['userid']); ?>" placeholder="type a username here" /> <a href="#" class="button green small radius add-another" data-context="user-owner">Add another &raquo;</a>  <a href="#" class="button alert radius small remove-other" data-context="user-owner">&times;</a></div>
        </div>

        <?php
        $user_groups = getUserGroups($current_user['userid']);
        if (count($user_groups) > 0) {
            //echo '<pre>'.print_r($user_groups, true).'</pre>';
        ?>
        <div class="row">
            <div class="column third"><label class="right">Group Owner(s):</label></div>
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

        <input type="submit" value="Submit" class="button large" />

        </form>

    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
