<?php

/*

    START A NEW EVENT
        cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

// include what's needed
require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/group_functions.php');

$page_uuid = 'new-event-page';
$page_title = 'Start a New Event'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Start a New Event</h2>
        <form action="/submit/new/event/" method="post" id="event-form" enctype="multipart/form-data">
        <div class="panel">
            <p>Events are a great way to collect submissions for a limited period of time. Events can be restricted to certain access level for visibility and submission separately.</p>
        </div>
        <div class="row">
            <div class="column third">
                <label class="right required">Name:</label>
            </div>
            <div class="column two-thirds">
                <input id="event-name" type="text" placeholder="The EVVYs" name="n" />
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="right">Start:</label>
            </div>
            <div class="column two-thirds">
                <input type="text" name="st" placeholder="<?php echo date('n/j/y', strtotime('+2 days')) . ' 1:00pm'; ?>" /> (Leave blank for <i>now</i>.)
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="right">Deadline:</label>
            </div>
            <div class="column two-thirds">
                <input type="text" name="de" placeholder="<?php echo date('n/j/y', strtotime('+7 days')) . ' 8:00pm'; ?>" /> (Leave blank for <i>never</i>.)
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="right">Visibility:</label>
            </div>
            <div class="column two-thirds">
                <select class="five" name="ul_v">
                    <option value="0">Owners Only</option>
                    <?php if ($current_user['userlevel'] == 1) { ?><option value="1">Admins Only</option><?php } ?>
                    <?php if ($current_user['userlevel'] < 5) { ?><option value="4">Faculty Only</option><?php } ?>
                    <option value="5">Just Logged-in People</option>
                    <option value="6" selected="selected">Publicly Visible</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="column third">
                <label class="right">Who can submit:</label>
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
                <label class="right">Brief Description / Tagline:</label>
            </div>
            <div class="column two-thirds">
                <input type="text" name="sde" />
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
                <label class="right">Associated URL:</label>
            </div>
            <div class="column two-thirds">
                <input type="url" name="url" />
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
        <h4>Add Owners</h4>
        <div class="panel"><p>Start typing an ECNet name and select the user from the auto-complete list. Please note that users must have logged into median at least once to be included in that list.</p></div>

        <div class="row">
            <div class="column third"><label class="right">User Owner(s):</label></div>
            <div class="column two-thirds"><input name="userowner[]" class="owner-field user-owner-field" value="<?php echo getUserName($current_user['userid']); ?>" type="text" placeholder="type a username here" /> <a href="#" class="button success small add-another" data-context="user-owner">Add another &raquo;</a>  <a href="#" class="button alert small remove-other" data-context="user-owner">&times;</a></div>
        </div>

        <?php
        $user_groups = getUserGroups($current_user['userid']);
        if (count($user_groups) > 0) {
            //echo '<pre>'.print_r($user_groups, true).'</pre>';
        ?>
        <div class="row">
            <div class="column third"><label class="right">Group Owner(s):</label></div>
            <div class="column two-thirds">
                <select class="five owner-field group-owner-field" name="groupowner[]">
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
