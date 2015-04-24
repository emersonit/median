<?php

/*

    MANAGE YOUR STUFF!
        cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

// include what's needed
require_once('/median-webapp/includes/common_functions.php');
require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/media_functions.php');

/*

	page routing

*/

// default route
$route_name = 'media';

if (isset($_GET['w']) && trim($_GET['w']) != '') {
	$route_name = strtolower(trim($_GET['w']));
}

/*

	end routing

*/

$page_uuid = 'manage-page';
$page_title = 'Manage Your Content';
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
		<?php
		if ($route_name == 'media') {
			?>
			<h2>Manage Your Entries</h2>
			<p>Here you can manage your entries. You have the choice to also see the entries any of the groups you own/control.</p>
			<?php
		} else if ($route_name == 'playlists') {
			?>
			<h2>Manage Your Playlists</h2>
			<p>Here you can manage your playlists. You can share these playlists with whoever you'd like.</p>
			<?php
		} else if ($route_name == 'groups') {
			?>
			<h2>Manage Your Groups</h2>
			<p>Here you can manage the groups you're an ownder of. Being an owner of a group also gives you control over any media entries that group controls.</p>
			<?php
		} // end route check
		?>

        <?php
		if (isset($_GET['success']) && trim($_GET['success']) != '') {
		?>
        <div class="alert-box success">Your uploader submission was successful! You should see your media below.</div>
        <?php
        } // end success check
		?>
        <?php
		if (isset($_GET['edited']) && trim($_GET['edited']) != '') {
		?>
        <div class="alert-box success">Your entry has been successfully saved!</div>
        <?php
        } // end edited check
        ?>
    </div>
</div>

<div class="row">
    <?php
	// alerts ?
	$alerts = getUserAlerts($current_user['userid']);
	$alert_count = count($alerts);
	$alert_limit = 10;
	if ($alert_count > 0) {
		// show alerts panel
	?>
	<div class="third column" id="alerts-list">
		<h4>Alerts!</h4>
		<?php
		if ($alert_count > $alert_limit) {
			echo '<p>Only '.$alert_limit.' alerts are shown here, but you have '.$alert_count.' total alerts.</p>';
		}
		$alert_counter = 0;
		foreach ($alerts as $alert) {
			if ($alert_counter > $alert_limit) {
				break;
			}
			echo '<div class="alert-box alert">';
			switch ($alert['type']) {
				case 't-error':
				echo '"'.getMediaTitle($alert['mid']).'" encountered an error while transcoding, it may be unwatchable. Median Admins have been notified.';
				break;
				case 'co-error':
				echo 'Your media entry "<a href="/edit/media/'.$alert['mid'].'/">'.getMediaTitle($alert['mid']).'</a>" is class-only but is not in any class.';
				break;
			}
			//echo '<a href="" class="close">&times;</a>';
			echo '</div>';
			$alert_counter++;
		}
		?>
	</div>
	<div class="two-thirds column" id="manage-list">
	<?php
	} else {
	?>
	<div class="full column" id="manage-list">
	<?php
	}
	?>
		<div class="sub-nav">
			Viewing:
            <a <?php if ($route_name == 'media') { echo ' class="manage-list-active"'; } ?> href="/manage/media/">Your Media</a>
			or <a <?php if ($route_name == 'playlists') { echo ' class="manage-list-active"'; } ?> href="/manage/playlists/">Your Playlists</a>
			or <a <?php if ($route_name == 'groups') { echo ' class="manage-list-active"'; } ?> href="/manage/groups/">Your Groups</a>
			or <a href="/manage/account/">Your Account Options</a>
		</div>
		<?php
		if ($route_name == 'media') {
		?>
        <div class="nav-filter">
            <p>
                <select id="filter-owner"><option value="mine">Show Just My Entries</option><option value="all">Show Mine and My Groups' Entries</option></select>
                <select id="filter-type"><option value="all" selected="selected">All Media Types</option><option value="video">Video Only</option><option value="audio">Audio Only</option><option value="image">Images Only</option><option value="doc">Documents Only</option><option value="link">Links Only</option></select>
                <select id="filter-sort">
                    <option value="latest" selected="selected">Order by Date, Newest to Oldest</option>
                    <option value="oldest">Order by Date, Oldest to Newest</option>
                    <option value="alpha_asc">Order by Title, A to Z</option>
                    <option value="alpha_desc">Order by Title, Z to A</option>
                    <option value="time_asc">Order by Duration, Shortest to Longest</option>
                    <option value="time_desc">Order by Duration, Longest to Shortest</option>
                    <option value="views">Order by View Count, Highest to Lowest</option>
                    <option value="comments">Order by Comment Count, Highest to Lowest</option>
                </select>
                <input type="submit" value="filter" id="filter-submit" />
            </p>
            <p class="right">
                <label for="manage-select-all" style="display:inline;">Select all on this page?</label>
                <input type="checkbox" id="manage-select-all" />
            </p>
        </div>
		<?php
        } // end if media route check
		?>
		<div id="main-list"><?php if ($route_name == 'media') { require('dummy_listing.php'); } ?></div>
	</div>
</div>

<input type="hidden" id="route-name" value="<?php echo $route_name; ?>" />

<?php
require_once('/median-webapp/includes/footer.php');
