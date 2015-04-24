<?php

// basic LTI support for Canvas!

require_once('/median-webapp/config/config.php');

// Load up the LTI Support code
require_once('/median-webapp/lib/ims-blti/blti.php');

header('Content-Type: text/html; charset=utf-8');

// Initialize, all secrets are 'secret', do not set session, and do not redirect
$context = new BLTI($blti_secret, false, false);

if ($context->valid) {
	if (isset($_POST['selection_directive']) && $_POST['selection_directive'] == 'embed_content') {
		header('Location: '.$median_base_url.'upload/canvas/?r='.$_POST['launch_presentation_return_url']);
		die();
	} else {
		die('Not sure what to do, sorry.');
	}
} else {
	die('There was a problem; the LTI context appears to be invalid. This is probably a configuration problem.');
}
