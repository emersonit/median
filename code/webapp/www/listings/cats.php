<?php

// category list!

$login_required = false;
require_once('/median-webapp/includes/login_check.php');
require_once('/median-webapp/includes/meta_functions.php');

$cats = getCategories($current_user['userid']);

//echo '<pre>'.print_r($cats, true).'</pre>';

if (count($cats) == 0) {
	echo '<div class="alert-box alert">Sorry, there are no categories to display.</div>';
} else {
	echo '<ul class="cats-list">';
	foreach ($cats as $cat) {

		if (isset($cat['pid'])) {
			continue;
		}

		echo '<li><a href="/category/'.$cat['id'].'/">'.$cat['ti'].'</a></li>';

		echo '<ul>';
		foreach ($cats as $subcat) {
			if (isset($subcat['pid']) && $subcat['pid'] == $cat['id']) {
				echo '<li><a href="/category/'.$subcat['id'].'/">'.$subcat['ti'].'</a></li>';
				echo '<ul>';
				foreach ($cats as $subsubcat) {
					if (isset($subsubcat['pid']) && $subsubcat['pid'] == $subcat['id']) {
						echo '<li><a href="/category/'.$subsubcat['id'].'/">'.$subsubcat['ti'].'</a></li>';
					}
				}
				echo '</ul>';
			}
		}
		echo '</ul>';

		/*
		echo '<div class="category row entry clickable" data-type="category" data-id="'.$cat['id'].'">';
		echo '<p class="category-name">'.$cat['ti'].'</p>';
		if (isset($cat['de'])) {
			echo '<p class="category-description">'.$cat['de'].'</p>';
		}
		echo '</div>'."\n";
		*/
	}
	echo '</ul>';
}

if ($current_user['loggedin']) {
	echo '<div class="panel">';
	echo '<div class="rss"><a href="/rss/categories/"><img src="/images/icons/rss.png" title="RSS Feed for Latest Public Categories" /></a></div>';
	echo '<a href="/new/category/" class="button small">Request a new category &raquo;</a>';
	echo '</div>';
}

?>