<?php

/*

    SEARCH
        cyle gage, emerson college, 2014

*/

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

$search_string = null;

if (isset($_POST['s']) && trim($_POST['s']) != '') {
	$search_string = trim($_POST['s']);
}

if (isset($_GET['s']) && trim($_GET['s']) != '') {
	$search_string = trim($_GET['s']);
}

// include what's needed
require_once('/median-webapp/includes/common_functions.php');

$page_uuid = 'search-page';
$page_title = 'Search'; // leave blank for the main page
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
      <h2>Searching for <code id="search-header"><?php echo $search_string; ?></code></h2>
      <p>Search: <input type="search" id="search-box" value="<?php echo $search_string; ?>" /> <input id="search-btn" class="button radius medium secondary" type="submit" value="search!" /></p>
      <div class="row" id="user-search">
  			<div class="column full">
  				<div id="user-list"></div>
  			</div>
  		</div>
      <div class="row" id="media-search">
  			<div class="column full">
  				<h4>Media:</h4>
  				<div class="nav-filter">
  					<div><select id="search-type"><option value="all" selected="selected">Entries and Users</option><option value="entries">Just Entries</option><option value="users">Just Users</option></select></div>
  					<div><select id="filter-type"><option value="all" selected="selected">All Media Types</option><option value="video">Video Only</option><option value="audio">Audio Only</option><option value="image">Images Only</option><option value="doc">Documents Only</option><option value="link">Links Only</option></select></div>
  					<div><select id="filter-sort">
  						<option value="latest" selected="selected">Order by Date, Newest to Oldest</option>
  						<option value="oldest">Order by Date, Oldest to Newest</option>
  						<option value="alpha_asc">Order by Title, A to Z</option>
  						<option value="alpha_desc">Order by Title, Z to A</option>
  						<option value="time_asc">Order by Duration, Shortest to Longest</option>
  						<option value="time_desc">Order by Duration, Longest to Shortest</option>
  						<option value="views">Order by View Count, Highest to Lowest</option>
  						<option value="comments">Order by Comment Count, Highest to Lowest</option>
  					</select></div>
  					<div><input type="submit" value="filter" id="filter-submit" /></div>
  					<div class="dummy"></div>
  				</div>
  				<div id="main-list"></div>
  			</div>
  		</div>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
