<?php
/** 
 * The base configuration settings for the MindTouch Importer.
 * Fill this out and save as config.php.
 */
ini_set('memory_limit','500M');

/** Credentials for the database. */
define('DB_NAME', '');
define('DB_HOST', '');
define('DB_USERNAME', '');
define('DB_PASSWORD', '');

/** Credentials for the default MindTouch API. */
define('API_URL', '');
define('API_USERNAME', '');
define('API_PASSWORD', '');

/** Whether to force the site to use the secure URL. */
define('FORCE_SECURE', true);

/** Set the name of the project. */
define('PROJECT_NAME', 'MindTouch Importer');

/** Set an admin contact email address. */
define('ADMIN_EMAIL', '');

/** Set hidden keys. */
define('NONCE_KEY', 'BBw(E{|5W3QX#6)ofIm0%5?t}s(P+14i(rbj~2S5^>$=k-A+jqc!SA)o[~vWrxNW');

/** Set number of seconds a password token is valid. */
define('PASSWORD_TOKEN_TTL', 14400);

/** Set time limit. */
define('TIME_LIMIT', 1000);

/** Set debug. */
define('DEBUG', false);
define('DISABLE_ATTACHMENTS', false);

/** Set the URL to the directory with the index.php file. */
define('ABSURL', '');

/** The following includes the required scripts to start the application. */
if (!defined('ABSPATH')) {
	define('ABSPATH', dirname(__FILE__) . '/');
	require(ABSPATH . 'includes/version.php');
	require(ABSPATH . 'includes/functions.php');
	require(ABSPATH . 'includes/password.php');

	// Detect when the request is Ajax.
	if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
		define('AJAX', true);
	} else {
		define('AJAX', false);
	}
}
