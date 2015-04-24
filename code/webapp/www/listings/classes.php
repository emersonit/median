<?php

// get class list

$login_required = false;
require_once('/median-webapp/includes/login_check.php');
require_once('/median-webapp/includes/user_functions.php');
require_once('/median-webapp/includes/meta_functions.php');

$this_semester = getCurrentSemester();

//echo '<pre>'.print_r($this_semester, true).'</pre>';

echo '<h4>'.$this_semester['academic_period_desc'].'</h4>';

$classes = getUserClasses($current_user['userid']);

if (count($classes) == 0) {
	echo '<div class="alert-box alert">Sorry, you have no classes to display.</div>';
	die();
}

$all_classes = array_merge($classes['taking'], $classes['teaching']);

//echo '<pre>'.print_r($all_classes, true).'</pre>';

if (count($all_classes) == 0) {
	echo '<div class="alert-box alert">Sorry, you have no classes to display.</div>';
} else {

	foreach ($all_classes as $class) {

		echo '<div class="class row entry clickable" data-type="class" data-id="'.$class['cc'].'">';
		echo '<p class="class-name"><span class="label radius class">'.$class['cc'].'</span> '.$class['name'].'</p>';
		echo '</div>'."\n";

	}

}

?>