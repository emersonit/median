<?php

/*

    START A NEW GROUP
        cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');

$page_uuid = 'new-group-page';
$page_title = 'Start a New Group'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Start a New Group</h2>
        <form action="/submit/new/group/" method="post" id="group-form" enctype="multipart/form-data">
        <div class="panel">
            <p>Groups can be used to organize media and can hold ownership of categories, events, and media entries.</p>
        </div>
        <div class="row">
            <div class="column third">
                <label class="right required">Name:</label>
            </div>
            <div class="column two-thirds">
                <input type="text" placeholder="The Cool Kids Group" name="n" id="group-name" />
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="right">Visibility:</label>
            </div>
            <div class="column two-thirds">
                <select class="five" name="ul">
                    <option value="0">Members Only</option>
                    <?php if ($current_user['userlevel'] == 1) { ?><option value="1">Admins Only</option><?php } ?>
                    <?php if ($current_user['userlevel'] < 5) { ?><option value="4">Faculty Only</option><?php } ?>
                    <option value="5">Just Logged-in People</option>
                    <option value="6" selected="selected">Publicly Visible</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="right">Brief Description / Tagline:</label>
            </div>
            <div class="column two-thirds">
                <input type="text" name="sd" />
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="right">Description:</label>
            </div>
            <div class="column two-thirds">
                <textarea name="d"></textarea>
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="right">iTunes Artwork:</label>
            </div>
            <div class="column two-thirds">
                <input type="file" name="a" /> (Optional; JPG only; 200kb limit)
            </div>
        </div>
        <h4>Add Members</h4>
        <div class="panel"><p>Start typing an ECNet name and select the user from the auto-complete list. Please note that users must have logged into median at least once to be included in that list. Selecting a user as an owner will allow them to edit the group info, member list, and everything associated with the group.</p></div>
        <div class="row">
            <div class="column third">
                <label class="right">Add Member:</label>
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
                        <tr><td><input class="member-id" type="hidden" name="m[]" value="<?php echo $current_user['userid']; ?>" /><input class="owner-checkbox" type="checkbox" checked="checked" name="o[]" value="<?php echo $current_user['userid']; ?>" /></td><td><?php echo getUserName($current_user['userid']); ?></td><td><input type="button" value="remove?" class="button small secondary remove-member-btn" /></td></tr>
                    </tbody>
                </table>

            </div>
        </div>

        <input type="submit" value="Submit" class="button large" />

        </form>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
