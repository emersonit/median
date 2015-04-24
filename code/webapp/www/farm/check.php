<?php

// check to make sure the median PXE nodes are running

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/dbconn_mongo.php');

// get the farmers
$farmers = $farmdb->farmers->find( array('e' => true) );

include('Mail.php');
$mail_subject = 'There\'s a problem with the Median PXE Nodes!';
$mailer = Mail::factory('smtp', array('host' => $mail_smtp_server) );
$headers = array('From' => $mail_from, 'Subject' => $mail_subject);

if (count($farmers) > 0) {
	$farmer_problems = array();
	foreach ($farmers as $farmer) {
		$seconds_ago = time() - $farmer['tsh'];
		if ($seconds_ago > 600) {
			$farmer_problems[] = $farmer['ip'];
		}
	}
	if (count($farmer_problems) > 0) {
		$mail_message = 'Oh dear, '.count($farmer_problems).' of the Median PXE nodes are having an issue. Their IPs are: '.implode(', ', $farmer_problems)."\n\n".' - Median';
		$mailer->send($mail_to, $headers, $mail_message);
	} else {
		die();
	}
} else {
	$mail_message = 'Oh dear, there are currently no Median PXE nodes enabled! Transcoding is not happening! Please either re-enable one or fix whatever is broken!'."\n\n".'- Median';
	$mailer->send($mail_to, $headers, $mail_message);
}
