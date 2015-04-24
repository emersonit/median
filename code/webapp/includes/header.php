<?php

require_once('/median-webapp/config/config.php');

if (!isset($page_uuid) || trim($page_uuid) == '') {
    $page_uuid = 'default-page';
}

if (!isset($page_title) || trim($page_title) == '') {
    $page_title = 'Median - '.$median_institution_name;
}

?><!doctype html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo $page_title; ?></title>
<link rel="stylesheet" href="https://pages.emerson.edu/cdn/css/jquery-ui-themes/smoothness/jquery-ui.min.css" />
<link rel="stylesheet" href="https://pages.emerson.edu/cdn/css/open-sans-standard.css" />
<link rel="stylesheet" href="https://pages.emerson.edu/cdn/css/lato-median.css" />
<link rel="stylesheet" href="/css/normalize.css" />
<link rel="stylesheet" href="/css/median.css" />
</head>
<body class="<?php echo $page_uuid; ?>">

<div class="container">

<div class="row">
    <div class="column full">
        <div class="search-area">
            <form action="/search/" method="get"><input name="s" type="search" placeholder="Entry title, user name, etc" /> <input type="submit" value="Search" /></form>
        </div>
        <h1><a href="/"><span class="logo-m">m</span><span class="logo-e">e</span><span class="logo-d">d</span><span class="logo-i">i</span><span class="logo-a">a</span><span class="logo-n">n</span></a></h1>
    </div>
</div>

<?php
// get global alerts, if there are any
require_once('/median-webapp/includes/meta_functions.php');
$global_alerts = getGlobalAlerts();
if (count($global_alerts) > 0) {
  ?>
  <div class="row" id="global-alerts-box">
  <div class="column full">
  <?php
	foreach ($global_alerts as $global_alert) {
		echo '<div class="alert-box '.((isset($global_alert['a']) && $global_alert['a'] == true) ? 'alert': 'secondary').'">'.$global_alert['c'].'</div>'."\n";
	}
  ?>
  </div>
  </div>
  <?php
} // end global alerts check
?>