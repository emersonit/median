<?php

/*

	MEDIAN PRIME
		cyle gage, emerson college, 2014

*/

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

/*

    needs to be able to serve...

    /categories/
    /groups/
    /events/
    /classes/
    /tags/

*/

$route = 'latest-media';

// check for a route and sanitize
if (isset($_GET['w']) && trim($_GET['w']) != '') {
    switch (trim($_GET['w'])) {
        case 'groups':
        $route = 'groups';
        break;
        case 'cats':
        $route = 'cats';
        break;
        case 'events':
        $route = 'events';
        break;
        case 'classes':
        $route = 'classes';
        break;
        case 'tags':
        $route = 'tags';
        break;
        case 'media':
        $route = 'latest-media';
        break;
    }
}

require_once('/median-webapp/includes/meta_functions.php');
require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/common_functions.php');

$page_uuid = 'main-page';
$page_title = ''; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">

    <div class="column third nav-column">

        <input type="button" id="big-upload-button" class="button large success full" value="Upload" />
        <?php
        if ($current_user['loggedin'] == false) {
        ?>
        <input type="button" id="big-login-button" class="button medium emerson full" value="Login" />
        <?php
        } // end of login check
        ?>

        <ul class="nav">
            <!-- <li><a href="/">Explore Media</a></li> -->
            <?php
            if ($current_user['loggedin'] == true) {
            ?>
            <li><a href="/classes/">Your Classes</a></li>
            <li><a href="/manage/">Manage Your Content</a></li>
            <?php
            } // end of login check
            ?>
            <li><a href="/live/">Median Live</a></li>
            <li><a href="/upload/">Upload</a></li>
            <li><a href="/help/">Help</a></li>
            <?php
            if ($current_user['loggedin'] == true) {
            ?>
            <li><a href="/logout/">Logout</a></li>
            <?php
            } // end of login check
            ?>
            <?php
			if ($current_user['userlevel'] == 1) {
			?>
            <li><a href="/admin/">Admin Backend</a></li>
			<?php
            } // end if admin check
			?>
			<?php
			if ($current_user['userlevel'] == 1 || canUseAkamai($current_user['userid'])) {
			?>
			<li><a href="/akamai/">Using Akamai</a></li>
			<li><a href="/akamai/admin/">Akamai Admin Backend</a></li>
			<?php
            } // end if akamai check
			?>
        </ul>

        <?php
		// get any alerts that are for the user for one reason or another
		$user_alerts = getUserAlerts($current_user['userid'], true);
		if ($user_alerts > 0) {
		?>
		<div class="alert-box">You have <?php echo $user_alerts; ?> <a href="/manage/">pending alert<?php echo plural($user_alerts); ?></a>.</div>
		<?php
		} // end if user alerts check
		?>

		<?php
        // get the news!
		$news = getNews(2);
		if (count($news) > 0) {
			foreach ($news as $post) {
				echo '<div class="news-box">'.$post['c'].' <span class="news-date">'.date('m/d/y', $post['tsc']).'</span></div>';
			}
		}
		?>
        <div class="rss"><a href="/rss/news/"><img src="/images/icons/rss.png" title="RSS Feed for Latest Median News" /></a></div>
		<p class="news-link"><a href="http://press.emerson.edu/it/category/projects/median/" target="_blank">More news at the IT Blog &raquo;</a></p>

        <?php
        // show this screen size indicator thing only for admins
        if ($current_user['userlevel'] == 1) {
        ?>
        <p>(You are using <span class="for-small-screens">a SMALL</span> <span class="for-medium-screens">a MEDIUM</span> <span class="for-large-screens">a LARGE</span> <span class="for-xlarge-screens">an EXTRA LARGE</span> screen.)</p>
        <?php
        } // end if admin check
        ?>

    </div>

    <div class="column two-thirds main-column">

        <!-- <div class="alert-box">Some kind of alert, maybe with <a href="#">a link</a>. <span class="alert-date">7/10/14</span></div> -->

        <div class="sub-nav">
            Viewing:
            <?php if ($route == 'latest-media') { ?><b>Media List</b><?php } else { ?><a href="/">Media List</a><?php } ?> or
            <?php if ($route == 'cats') { ?><b>Categories</b><?php } else { ?><a href="/categories/">Categories</a><?php } ?> or
            <?php if ($route == 'groups') { ?><b>Groups</b><?php } else { ?><a href="/groups/">Groups</a><?php } ?> or
            <?php if ($route == 'events') { ?><b>Events</b><?php } else { ?><a href="/events/">Events</a><?php } ?>
        </div>

        <?php
        // show the media filter options only for latest media
        if ($route == 'latest-media') {
        ?>
        <div class="nav-filter">
            <div class="filter-option-box">Filter:</div>
            <div class="filter-option-box"><select id="filter-type"><option value="all" selected="selected">All Media Types</option><option value="video">Video Only</option><option value="audio">Audio Only</option><option value="image">Images Only</option><option value="doc">Documents Only</option><option value="link">Links Only</option></select></div>
            <div class="filter-option-box"><select id="filter-sort">
                <option value="latest" selected="selected">Order by Date, Newest to Oldest</option>
                <option value="oldest">Order by Date, Oldest to Newest</option>
                <option value="alpha_asc">Order by Title, A to Z</option>
                <option value="alpha_desc">Order by Title, Z to A</option>
                <option value="time_asc">Order by Duration, Shortest to Longest</option>
                <option value="time_desc">Order by Duration, Longest to Shortest</option>
                <option value="views">Order by View Count, Highest to Lowest</option>
                <option value="comments">Order by Comment Count, Highest to Lowest</option>
            </select></div>
            <div class="filter-option-box"><input type="button" value="filter!" id="filter-submit"></div>
            <div class="dummy"></div>
        </div>
        <?php
        } // end if latest-media route check
        ?>

        <div id="main-list"><?php require('dummy_listing.php'); ?></div>

        <?php
		if ($route == 'latest-media') {
		?>
		<div class="rss"><a href="/rss/latest/"><img src="/images/icons/rss.png" title="RSS Feed for Latest Public Entries" /></a></div>
		<?php
		}
		?>

    </div>
</div>

<input type="hidden" id="route" value="<?php echo $route; ?>" />

<?php
require_once('/median-webapp/includes/footer.php');