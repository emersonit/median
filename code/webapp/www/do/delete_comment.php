<?php

// delete a comment from a media entry

require_once('/median-webapp/includes/login_check.php');

if (!isset($current_user['loggedin']) || $current_user['loggedin'] == false) {
	die('You need to be logged in to delete a comment.');
}

if (!isset($_GET['id']) || trim($_GET['id']) == '') {
	die('You need to provide a comment to delete.');
}

$cid = trim($_GET['id']);

require_once('/median-webapp/includes/dbconn_mongo.php');

// check to make sure this comment belongs to the current user

$the_comment = $mdb->comments->findOne( array( '_id' => new MongoId($cid) ) );

if (!isset($the_comment)) {
	die('That comment does not exist!');
} else {
	if ($the_comment['uid'] != $current_user['userid']) {
		die('You are not the owner of that comment, so you cannot delete it.');
	}
	try {
		$mdb->comments->remove( array( '_id' => new MongoId($cid) ), array('w' => 1) );
	} catch(MongoCursorException $e) {
		die('There was an error deleting your comment, sorry. Please try again.');
	}
	header('Location: /media/'.$the_comment['mid'].'/');
}