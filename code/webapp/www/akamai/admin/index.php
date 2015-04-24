<?php

/*

	AKAMAI ADMIN
		cyle gage, emerson college, 2014

*/

$login_required = true;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/includes/permission_functions.php');
require_once('/median-webapp/includes/error_functions.php');

if (canUseAkamai($current_user['userid']) == false) {
	bailout('Sorry, but you do not have permission to access this page.', $current_user['userid']);
}

require_once('/median-webapp/includes/akamai_functions.php');
require_once('/median-webapp/includes/media_functions.php');

$page_uuid = 'akamai-admin-page';
$page_title = 'Akamai Admin'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
	<div class="column full">
		<h2>Akamai Admin</h2>
		<p>Here you can see what entries and files are currently in our high-availability Akamai mirror.</p>

		<?php
		if (isset($_GET['added']) && $_GET['added'] == 'yup') {
		?>
		<div class="alert-box success">Added to Akamai successfully!</div>
		<?php
		}
		?>

		<?php
		if (isset($_GET['removed']) && $_GET['removed'] == 'yup') {
		?>
		<div class="alert-box success">Removed from Akamai successfully!</div>
		<?php
		}
		?>

		<h4>Entries Currently on Akamai</h4>
		<?php
		$entries = getAkamaiEntries();
		if (count($entries) > 0) {
			echo '<table>';
			echo '<tr><th>Median ID</th><th>Title</th><th>Bitrate</th><th>Date Added</th><th></th></tr>';
			foreach ($entries as $entry) {
				echo '<tr>';
				echo '<td>'.$entry['mid'].'</td>';
				echo '<td>'.getMediaTitle($entry['mid']).'</td>';
				echo '<td>'.$entry['b'].' kbps</td>';
				echo '<td>'.date('n/j/Y g:ia', $entry['tsc']).'</td>';
				echo '<td><a href="remove.php?mid='.$entry['mid'].'">Remove</a></td>';
				echo '</tr>';
			}
			echo '</table>';
		}

		?>

		<hr />
		<h4>Files Currently on Akamai</h4>
		<?php
		$files = getAkamaiFiles();
		if ($files === false) {
			echo '<p>There was an error connecting to the Akamai FTP site.</p>';
		} else if (count($files) > 0) {
			// display file list...
			echo '<table>';
			echo '<tr><th>'.$akamai_ftp_path_prefix.'/</th><th>Size</th><th>Date Added</th></tr>';
			$total_mb = 0;
			foreach ($files as $file) {
				echo '<tr><td>'.$file['name'].'</td><td>'.number_format(($file['size']/1024/1024), 1).' MB</td><td>'.date('n/j/Y', strtotime($file['date'])).'</td></tr>';
				$total_mb += $file['size']/1024/1024;
			}
			$total_gb = $total_mb/1024;
			echo '<tr><td></td><td><b>'.number_format($total_gb, 1).' GB used</b><br />of 5.0 GB limit</td><td></td></tr>';
			echo '</table>';
			if ($total_gb > 4.5) {
				echo '<div><span class="warning">WARNING: Almost at 5GB limit!</span></div>';
			}
		}
		?>

		<hr />
		<h4>Add an entry to Akamai</h4>
		<form action="add.php" method="get">
		<input type="text" class="two" name="mid" id="add-mid" placeholder="Median ID here" <?php echo ((isset($_GET['t']) && trim($_GET['t']) == 'a') ? 'value="'.$_GET['mid'].'"' : ''); ?> />
		<input type="submit" value="add!" class="button radius medium" />
		</form>

		<hr />
		<h4>Remove an entry from Akamai</h4>
		<form action="remove.php" method="get">
		<input type="text" class="two" name="mid" id="remove-mid" placeholder="Median ID here" <?php echo ((isset($_GET['t']) && trim($_GET['t']) == 'r') ? 'value="'.$_GET['mid'].'"' : ''); ?> />
		<input type="submit" value="remove!" class="button radius medium alert" />
		</form>
	</div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
