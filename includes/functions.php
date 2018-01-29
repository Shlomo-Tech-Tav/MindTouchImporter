<?php
/**
 * Sort an array of arrays by a key of the nested array.
 * @param array $array Array to sort.
 * @param string $on Key to sort on
 * @param constant $order SORT_ASC or SORT_DESC to control the sorting direction.
 * @return array $array The sorted array.
 */
function array_sort($array, $on, $order=SORT_ASC) {
	$new_array = array();
	$sortable_array = array();

	if (count($array) > 0) {
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $k2 => $v2) {
					if ($k2 == $on) {
						$sortable_array[$k] = $v2;
					}
				}
			} else {
				$sortable_array[$k] = $v;
			}
		}

		switch ($order) {
			case SORT_ASC:
				asort($sortable_array);
			break;
			case SORT_DESC:
				arsort($sortable_array);
			break;
		}

		foreach ($sortable_array as $k => $v) {
			$new_array[$k] = $array[$k];
		}
	}

	return $new_array;
}

/**
 * Builds the HTML for the page.
 * @param string $content Content of the page.
 * @param string $title Title of the document.
 * @param string $menu Additional menu items.
 * @param string $head_extra Anything extra to include in the head.
 * @return string The page's HTML.
 */
function build_html($content, $title = '', $menu = '', $head_extra = '') {
	ob_start();
	include(ABSPATH . 'templates/html-start.php');
	include(ABSPATH . 'templates/head.php');
	include(ABSPATH . 'templates/body-start.php');
	echo $content;
	include(ABSPATH . 'templates/body-end.php');
	include(ABSPATH . 'templates/html-end.php');
	return ob_get_clean();
}

/**
 * Builds a version-style string with leading zeros out of passed in integers.
 * Example build_version_string(3, 1, 2) would return 03.01.02.
 * @param integer $number Accepts any number of integers and combines them together.
 * @return string $version Version style string from numbers.
 */
function build_version_string() {
	$numbers = func_get_args();
	if (count($numbers) === 1 && is_array($numbers[0])) {
		$numbers = $numbers[0];
	}
	foreach ($numbers as $key => $number) {
		if (is_numeric($number) && $number < 10 && (int) $number[0] != 0) {
			$numbers[$key] = '0' . $number;
		}
	}
	return implode('.', $numbers);
}

function convert_to($source, $target_encoding, $encoding = '') {
	if (empty($encoding)) {
		// detect the character encoding of the incoming file
		$encoding = mb_detect_encoding( $source, "auto" );
	}
	echo "encoding: $encoding<br>\n";
	  
	// escape all of the question marks so we can remove artifacts from
	// the unicode conversion process
	$target = str_replace( "?", "[question_mark]", $source );
	  
	// convert the string to the target encoding
	$target = mb_convert_encoding( $target, $target_encoding, $encoding);
	  
	// remove any question marks that have been introduced because of illegal characters
	$target = str_replace( "?", "", $target );
	  
	// replace the token string "[question_mark]" with the symbol "?"
	$target = str_replace( "[question_mark]", "?", $target );

	return $target;
}

/**
 * Converts a string from the Windows CP1252 format to UTF8.
 * @param string $input A string encoded in the CP1252 format.
 * @param string $default
 * @param array  $replace
 * @return string $input The UTF8 version of the input.
 */
function convert_cp1252_to_utf8($input, $default = '', $replace = array()) {
	if ($input === null || $input == '') {
		return $default;
	}

	// https://en.wikipedia.org/wiki/UTF-8
	// https://en.wikipedia.org/wiki/ISO/IEC_8859-1
	// https://en.wikipedia.org/wiki/Windows-1252
	// http://www.unicode.org/Public/MAPPINGS/VENDORS/MICSFT/WINDOWS/CP1252.TXT
	$encoding = mb_detect_encoding($input, array('Windows-1252', 'ISO-8859-1'), true);
	if ($encoding == 'ISO-8859-1' || $encoding == 'Windows-1252') {
		/*
		 * Use the search/replace arrays if a character needs to be replaced with
		 * something other than its Unicode equivalent.
		 */ 

		/*$replace = array(
			128 => "&#x20AC;",      // http://www.fileformat.info/info/unicode/char/20AC/index.htm EURO SIGN
			129 => "",              // UNDEFINED
			130 => "&#x201A;",      // http://www.fileformat.info/info/unicode/char/201A/index.htm SINGLE LOW-9 QUOTATION MARK
			131 => "&#x0192;",      // http://www.fileformat.info/info/unicode/char/0192/index.htm LATIN SMALL LETTER F WITH HOOK
			132 => "&#x201E;",      // http://www.fileformat.info/info/unicode/char/201e/index.htm DOUBLE LOW-9 QUOTATION MARK
			133 => "&#x2026;",      // http://www.fileformat.info/info/unicode/char/2026/index.htm HORIZONTAL ELLIPSIS
			134 => "&#x2020;",      // http://www.fileformat.info/info/unicode/char/2020/index.htm DAGGER
			135 => "&#x2021;",      // http://www.fileformat.info/info/unicode/char/2021/index.htm DOUBLE DAGGER
			136 => "&#x02C6;",      // http://www.fileformat.info/info/unicode/char/02c6/index.htm MODIFIER LETTER CIRCUMFLEX ACCENT
			137 => "&#x2030;",      // http://www.fileformat.info/info/unicode/char/2030/index.htm PER MILLE SIGN
			138 => "&#x0160;",      // http://www.fileformat.info/info/unicode/char/0160/index.htm LATIN CAPITAL LETTER S WITH CARON
			139 => "&#x2039;",      // http://www.fileformat.info/info/unicode/char/2039/index.htm SINGLE LEFT-POINTING ANGLE QUOTATION MARK
			140 => "&#x0152;",      // http://www.fileformat.info/info/unicode/char/0152/index.htm LATIN CAPITAL LIGATURE OE
			141 => "",              // UNDEFINED
			142 => "&#x017D;",      // http://www.fileformat.info/info/unicode/char/017d/index.htm LATIN CAPITAL LETTER Z WITH CARON 
			143 => "",              // UNDEFINED
			144 => "",              // UNDEFINED
			145 => "&#x2018;",      // http://www.fileformat.info/info/unicode/char/2018/index.htm LEFT SINGLE QUOTATION MARK 
			146 => "&#x2019;",      // http://www.fileformat.info/info/unicode/char/2019/index.htm RIGHT SINGLE QUOTATION MARK
			147 => "&#x201C;",      // http://www.fileformat.info/info/unicode/char/201c/index.htm LEFT DOUBLE QUOTATION MARK
			148 => "&#x201D;",      // http://www.fileformat.info/info/unicode/char/201d/index.htm RIGHT DOUBLE QUOTATION MARK
			149 => "&#x2022;",      // http://www.fileformat.info/info/unicode/char/2022/index.htm BULLET
			150 => "&#x2013;",      // http://www.fileformat.info/info/unicode/char/2013/index.htm EN DASH
			151 => "&#x2014;",      // http://www.fileformat.info/info/unicode/char/2014/index.htm EM DASH
			152 => "&#x02DC;",      // http://www.fileformat.info/info/unicode/char/02DC/index.htm SMALL TILDE
			153 => "&#x2122;",      // http://www.fileformat.info/info/unicode/char/2122/index.htm TRADE MARK SIGN
			154 => "&#x0161;",      // http://www.fileformat.info/info/unicode/char/0161/index.htm LATIN SMALL LETTER S WITH CARON
			155 => "&#x203A;",      // http://www.fileformat.info/info/unicode/char/203A/index.htm SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
			156 => "&#x0153;",      // http://www.fileformat.info/info/unicode/char/0153/index.htm LATIN SMALL LIGATURE OE
			157 => "",              // UNDEFINED
			158 => "&#x017e;",      // http://www.fileformat.info/info/unicode/char/017E/index.htm LATIN SMALL LETTER Z WITH CARON
			159 => "&#x0178;",      // http://www.fileformat.info/info/unicode/char/0178/index.htm LATIN CAPITAL LETTER Y WITH DIAERESIS
		);*/

		if (count($replace) != 0) {
			$find = array();
			foreach (array_keys($replace) as $key) {
				$find[] = chr($key);
			}
			$input = str_replace($find, array_values($replace), $input);
		}
		/*
		 * Because ISO-8859-1 and CP1252 are identical except for 0x80 through 0x9F
		 * and control characters, always convert from Windows-1252 to UTF-8.
		 */
		$input = iconv('Windows-1252', 'UTF-8//IGNORE', $input);
		if (count($replace) != 0) {
			$input = html_entity_decode($input);
		}
	}
	return $input;
}

/**
 * Converts contents of CSV file to an array.
 * @param string $file_name Name with path of CSV file to convert.
 * @param boolean $first_line_titles True when the first row are titles.
 * @return array $csv Contents of CSV file in array form.
 */
function convert_csv_to_array($file_name, $first_line_titles = true) {
	// Convert the file to an array.
	$csv = array();
	$titles = array();
	$row = 1;
	if (($handle = fopen($file_name, "r")) !== FALSE) {
		while (($data = fgetcsv($handle)) !== FALSE) {
			if ($first_line_titles) {
				if ($row === 1) {
					// Record the titles.
					$titles = $data;
				} else {
					// Record the data.
					$csv_row = array();
					$columns = count($data);
					for ($c = 0; $c < $columns; $c ++) {
						$csv_row[$titles[$c]] = $data[$c];
					}
					$csv[] = $csv_row;
				}
			} else {
				// Record the data.
				$csv[] = $data;
			}
			$row ++;
		}
		fclose($handle);
	}
	return $csv;
}

/**
 * Returns the current URL.
 * @return string Current URL.
 */
function current_url() {
	return 'http' . (empty($_SERVER['HTTPS']) ? '' : 's') . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
}

function delete_content_ids($credentials) {
	// Delete all content IDs.
	$Api = new \MindTouchApi\MindTouchApi($credentials);
	$contexts = $Api->contextMapsGet();
	foreach ($contexts->contextmap as $context) {
		$Api->contextsDelete((string) $context->id);
	}
}

// $command = 'php ../app/console userauth:bulkuserimport PRISMS_Users_List.csv';
// execute_in_background($command);
function execute_in_background($cmd) {
	if (substr(php_uname(), 0, 7) == "Windows") {
		pclose(popen("start /B ". $cmd, "r"));
	} else {
		exec($cmd . " > /dev/null &");
	}
}

function file_search($filename, $path) {
	return current(preg_grep("/$filename$/i", glob("$path/*")));
}

function file_exists_case_insensitive($filename) {
	static $dir_list = [];
    if (file_exists($filename)) {
        return true;
    }

	$directory_name = dirname($filename);
	if (!isset($dir_list[$directory_name])) {
		$file_array = glob($directory_name . '/*', GLOB_NOSORT);
		$dir_list_entry = [];
		foreach ($file_array as $file) {
			$dir_list_entry[strtolower($file)] = true;
		}
		$dir_list[$directory_name] = $dir_list_entry;
	}

	return isset($dir_list[$directory_name][strtolower($filename)]);
}

/**
 * Generates a GUID.
 * 
 * @return string GUID.
 */
function generate_guid() {
	if (function_exists('com_create_guid') === true) {
		return trim(com_create_guid(), '{}');
	}

	return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

/**
 * Returns microtime with microseconds added.
 * @return integer $microtime
 */
function get_microtime() {
	$microtime = microtime();
	$microtime = explode(' ', $microtime);
	$microtime = $microtime[1] + $microtime[0];
	return $microtime;
}

/**
 * Returns the user ID for the logged in user. False when not logged in.
 * @return integer $user_id
 */
function get_user_id() {
	if (!empty($_SESSION['user_id'])) {
		return (int) $_SESSION['user_id'];
	} else {
		return false;
	}
}

/**
 * Includes the classes required to run the application.
 */
function include_classes() {
	// Open the includes directory.
	$classes = scandir(ABSPATH . '/includes');

	// Loop through each subdirectory.
	foreach ($classes as $class) {
		// Skip directories.
		if (is_dir(ABSPATH . '/includes/' . $class)) {
			continue;
		}

		// Check for files matching class.*.php.
		$file_bits = explode('.', $class);
		if (count($file_bits) !== 3 || $file_bits[0] !== 'class' || $file_bits[2] !== 'php') {
			continue;
		}
		require_once(ABSPATH . '/includes/' . $class);
	}
}

/**
 * Includes the controllers for the application.
 */
function include_controllers() {
	// Open the controllers directory.
	$controllers = scandir(ABSPATH . '/includes/controllers');

	// Loop through each subdirectory.
	foreach ($controllers as $controller) {
		// Skip directories.
		if (is_dir(ABSPATH . '/includes/controllers/' . $controller)) {
			continue;
		}

		// Check for files matching class.*.php.
		$file_bits = explode('.', $controller);
		if ($file_bits[0] !== 'class' && end($file_bits) !== 'php') {
			continue;
		}
		require_once(ABSPATH . '/includes/controllers/' . $controller);
	}
}

/**
 * Includes the specified import class.
 * @param string $import_class Name of import class file.
 */
function include_import_class($import_class) {
	if (file_exists(ABSPATH . '/includes/import-classes/' . $import_class)) {
		require_once(ABSPATH . '/includes/import-classes/' . $import_class);
	}
}

/**
 * Parses the query string parameters and adds back to the _GET array.
 */
function include_query_string() {
	// Check the URI for a question mark.
	$position = strpos($_SERVER['REQUEST_URI'], '?');

	// When the mark is found, get the query string and parse it into the _GET array.
	if ($position !== false) {
		$query_string = substr($_SERVER['REQUEST_URI'], $position + 1);
		parse_str($query_string, $_GET);
	}
}

/**
 * Determines if the user is an admin.
 * @return boolean
 */
function is_admin() {
	if (defined('ADMIN') && ADMIN === true) {
		return true;
	} else {
		return false;
	}
}

/**
 * Determines if the user is logged in.
 * @return boolean
 */
function is_logged_in() {
	if (!empty($_SESSION['loggedin']) && !empty($_SESSION['user_id'])) {
		return true;
	} else {
		return false;
	}
}

/**
 * Checks to see if OpenOffice is running.
 * @return boolean True when running.
 */
function is_openoffice_running() {
	$pid = (int) shell_exec('pidof /opt/openoffice4/program/soffice.bin');
	if (!empty($pid)) {
		return true;
	} else {
		return false;
	}
}

/**
 * Builds a nested, multi-dimensional array of pages that reflects
 * their hierarchy. Uses the parsed pages_data array.
 * @param array $pages Pages array generated by the parse function.
 * @return array Nested array.
 */
function build_pages_tree($pages) {
	// Create an array with the path as the key and the title as the value.
	$paths = array();
	foreach ($pages as $page) {
		// Escape any slashes in the paths or title.
		foreach ($page['path'] as $key => $path) {
			$page['path'][$key] = str_replace('/', '%2F', $path);
		}
		$page['title'] = str_replace('/', '%2F', $page['title']);
		$path = implode('/', $page['path']);
		if (!empty($path)) {
			$path .= '/';
		}
		$paths[$path . $page['title']] = $page['title'];
	}

	// Create nested tree array for the pages.
	$pages_tree = array();
	foreach ($paths as $path => $title) {
		$list = explode('/', trim($path, '/'));
		$last_dir = &$pages_tree;
		foreach ($list as $dir) {
			// Unescape slashes in paths.
			$dir = str_replace('%2F', '/', $dir);
			$last_dir =& $last_dir[$dir];
		}
		// Unescape slashes in titles.
		$last_dir['title'] = str_replace('%2F', '/', $title);
	}

	return $pages_tree;
}

/**
 * Detects if string is mb8.
 * @param string $string String to test
 * @return boolean True when mb8.
 */
function mb_is_utf8($string) {     
	return mb_detect_encoding($string, 'UTF-8') === 'UTF-8';
}

/**
 * Generates and stores a nonce in the session for forms.
 */
function nonce_generate() {
	$_SESSION['nonce'] = md5(uniqid(mt_rand(), true) + NONCE_KEY);
}

/**
 * Validates the supplied nonce.
 * @param enum('get', 'post') $method Chooses which array the supplied nonce is in.
 * @return boolean
 */
function nonce_validate($method = 'get') {
	switch($method) {
		case 'post':
			$nonce = (!empty($_POST['nonce'])) ? $_POST['nonce'] : '';
		break;

		default:
			$nonce = (!empty($_GET['nonce'])) ? $_GET['nonce'] : '';
		break;
	}
	return $_SESSION['nonce'] == $nonce && $_SESSION['nonce'];
}

/**
 * Outputs HTML for hidden form input with nonce.
 */
function nonce_input() {
	echo '<input type="hidden" name="nonce" id="form_key" value="' . $_SESSION['nonce'] . '">';
}

/**
 * Redirects script to new url and exits.
 * @param string $url URL of new location.
 * @param integer $statusCode HTTP status code. Defaults to 303.
 */
function redirect($url, $statusCode = 303) {
	if (headers_sent()) {
		ob_start();
		include(ABSPATH . 'templates/redirect.php');
		echo ob_get_clean();
	} else {
		header('Location: ' . $url, true, $statusCode);
	}
	die();
}

/**
 * Redirects page to secure site when insecure.
 */
function redirect_to_https() {
	if ($_SERVER['HTTPS'] !== "on") {
		$url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		redirect($url);
	}
}

/**
 * Removes a directory and all of its contents.
 * @param string $directory Path of directory to remove.
 * @return boolean True on success.
 */
function remove_directory($directory) {
	$it = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
	$files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($files as $file) {
		if ($file->isDir()) {
			rmdir($file->getRealPath());
		} else {
			unlink($file->getRealPath());
		}
	}
	return rmdir($directory);
}

/**
 * Removes unsafe characters from the file name.
 * @param string $filename Raw file name.
 * @return string Sanitized file name.
 */
function sanitize_filename($filename) {
	// Remove any trailing dots, as those aren't ever valid file names.
	$filename = rtrim($filename, '.');

	$regex = array('#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#');
	$filename = trim(preg_replace($regex, '', $filename));
	$filename = str_replace(' ', '-', $filename);

	return $filename;
}

/**
 * Send an email message.
 * @param string $subject Subject of email message.
 * @param string $message Body of email message.
 * @param string $email Email address to send message to.
 * @param string $name Nam of recipient.
 * @return boolean True on success. Error message on failure.
 */
function send_email($subject, $message, $email, $name = '') {
	$mail = new PHPMailer();
	$mail->From = ADMIN_EMAIL;
	$mail->FromName = PROJECT_NAME;
	$mail->addAddress($email, $name);
	$mail->Subject = $subject;
	$mail->Body = $message;
	if (!$mail->send()) {
		return $mail->ErrorInfo;
	} else {
		return true;
	}
}

/**
 * Gets or sets the key from the session.
 * @param string $get Key for the session array.
 * @param mixed $set When set, records variable in session array.
 * @return mixed $value Value of $get in session array.
 */
function session_array($get, $set = '') {
	if (!empty($set)) {
		$_SESSION[$get] = $set;
	}

	if (!empty($_SESSION[$get])) {
		return $_SESSION[$get];
	} else {
		return array();
	}
}

/**
 * Resets session to all but the nonce.
 * @param string $nonce The nonce token.
 */
function session_reset_custom() {
	$nonce = !empty($_SESSION['nonce']) ? $_SESSION['nonce'] : '';
	$loggedin = !empty($_SESSION['loggedin']) ? $_SESSION['loggedin'] : '';
	$user_id = $loggedin = !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
	$_SESSION = array(
		'loggedin' => $loggedin,
		'nonce' => $nonce,
		'user_id' => $user_id
	);
}

/**
 * Simple slugify function.
 * 
 * @param string $text String to slugify.
 * @return string Slug.
 */
function slugify($text) {
	// replace non letter or digits by -
	$text = preg_replace('~[^\\pL\d]+~u', '-', $text);

	// trim
	$text = trim($text, '-');

	// transliterate
	$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

	// lowercase
	$text = strtolower($text);

	// remove unwanted characters
	$text = preg_replace('~[^-\w]+~', '', $text);

	if (empty($text)) {
		return 'n-a';
	}

	return $text;
}

/**
 * Starts the Importer application.
 */
function start_application() {
	// The SAPI type is cgi-fcgi when accessed from the browser.
	if (PHP_SAPI === 'cgi-fcgi') {
		// Force secure site.
		if (FORCE_SECURE) {
			redirect_to_https();
		}
	}

	require ABSPATH . 'vendor/autoload.php';

	// Make sure OpenOffice is running.
	// start_openoffice();

	// Include base classes.
	include_classes();

	// Include controllers.
	include_controllers();

	// Activate query string parameters.
	include_query_string();
}

/**
 * Starts OpenOffice in a headless state for Word conversions.
 * @return boolean True on success.
 */
function start_openoffice() {
	if (is_openoffice_running()) {
		return true;
	}

	$command = '/opt/openoffice4/program/soffice -headless -accept="socket,host=127.0.0.1,port=8100;urp;" -nofirststartwizard';

	$output_file = ABSPATH . 'includes/phpdocx/temp/openoffice.log';
	$pid_file = ABSPATH . 'includes/phpdocx/temp/openoffice.pid';

	exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $command, $output_file, $pid_file));
	return true;
}

/**
 * Checks client's production credentials and returns true when defined.
 * @param array $credentials Array of client's production credentials.
 * @param string $api_url URL to the MindTouch API.
 * @param string $api_username MindTouch API username.
 * @param string $api_password MindTouch API password.
 * @return boolean
 */
function valid_production_credentials($api_url, $api_username, $api_password) {
	if (!empty($api_url) 
			&& !empty($api_username) 
			&& !empty($api_password)) {
		return true;
	} else {
		return false;
	}
}

/**
 * Prints the variable inside an xmp element.
 * @param mixed $array Variable to print.
 */
function xmp_print($array, $title = '') {
	if (!empty($title)) {
		echo "$title:<br>\n";
	}
	echo "<xmp>";
	print_r($array);
	echo "</xmp>";
}
