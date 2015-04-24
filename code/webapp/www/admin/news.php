<?php

/*

    EDIT NEWS
        cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (getUserLevel($current_user['userid']) != 1) {
    bailout('Sorry, you do not have permission to view this.', $current_user['userid']);
}

require_once('/median-webapp/includes/dbconn_mongo.php');

$page_uuid = 'admin-page';
$page_title = 'News Admin'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Edit News</h2>
		<div id="news">
		<?php
		$news = $mdb->news->find()->sort( array('tsc' => -1) );
		foreach ($news as $news_item) {
			echo '<div>';
			echo '<p><span class="label secondary">'.date('m-d-Y h:i A', $news_item['tsc']).'</span> '.$news_item['c'].' <a href="news_process.php?t=d&id='.$news_item['_id'].'" class="button small alert">delete</a></p>';
			echo '</div>'."\n";
		}
		?>
		</div>
		<form action="news_process.php?t=a" method="post">
		<fieldset>
		<legend>Add News</legend>
		<input required="required" type="text" name="c" placeholder="news blurb" />
		<input class="button medium" type="submit" value="add!" />
		</fieldset>
		</form>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
