<?php
// Get the configuration and start the application.
require('config.php');
start_application();

// Prevent access from http.
if (PHP_SAPI === 'cgi-fcgi') {
	exit('No access.');
}

// Engage controller.
$Crontroller = new Crontroller();
$Crontroller->route('queue');
