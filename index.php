<?php
// Get the configuration and start the application.
require('config.php');
start_application();

// Engage controller.
require(ABSPATH . 'includes/routes.php');
$Controller = new Controller($routes, $whitelist);
$Controller->route();

// Make sure there's a title and send HTML to browser.
if (!empty($Controller->title)) {
	$title = $Controller->title;
} else {
	$title = PROJECT_NAME;
}

if (AJAX) {
	echo $Controller->content;
} else {
	echo build_html($Controller->content, $title, $Controller->Menu->getMenu());
}
