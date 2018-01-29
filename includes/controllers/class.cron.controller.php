<?php
class Crontroller extends BaseController {
	private $next = array();
	/**
	 * Instance of queue class.
	 * @var object
	 */
	private $Queue;
	protected $Usage;
	/**
	 * Instance of users class.
	 * @var object
	 */
	protected $Users;

	/**
	 * Instantiates objects.
	 */
	public function __construct() {
		start_openoffice();

		// Connect to database.
		$this->Database = new medoo(array(
			'database_type' => 'mysql',
			'database_name' => DB_NAME,
			'server' => DB_HOST,
			'username' => DB_USERNAME,
			'password' => DB_PASSWORD,
			'charset' => 'utf8'
		));

		$this->View = new View();
		$this->Clients = new Clients($this->Database);
		$this->Queue = new ImportQueue($this->Database);
		$this->Usage = new UserUsage($this->Database);
		$this->Users = new Users($this->Database);
	}

	/**
	 * Processes the next Word document in the import queue.
	 */
	private function processQueue() {
		// Check the import queue for the oldest item in the queue.
		$next = $this->Queue->getNext();
		if (count($next) < 1) {
			return true;
		}

		// Set item to processing to prevent other jobs from accessing.
		$this->Queue->updateProcessing($next['import_queue_id']);

		// Store the next item.
		$next = $next[0];
		echo "Processing " . $next['file'] . " (ID: " . $next['import_queue_id'] . ")\n";

		// Get the client and user information.
		$user = $this->Users->get((int) $next['user_id']);
		$client = $this->Clients->get((int) $next['client_id']);

		// Prepare item information.
		$doc_dir = ABSPATH . 'clients/' . $client['code'] . '/imports/';

		// Sanitize the file name.
		$old_file = $next['file']; 
		if (strpos($next['file'], ' ') !== false) {
			$new_file = sanitize_filename($next['file']);
			if (rename($doc_dir . $next['file'] . '.' . $next['extension'], $doc_dir . $new_file . '.' . $next['extension'])) {
				// Update the database.
				$next['file'] = $new_file;
				$this->Queue->update($next['import_queue_id'], array('file' => $next['file']));
			}
		}

		// Create destination directory.
		if (!is_dir($doc_dir . $next['file'] . '_files')) {
			mkdir($doc_dir . $next['file'] . '_files');
		}

		// Prepare source and destination strings.
		$doc_source = $doc_dir . $next['file'] . '.' . $next['extension'];
		$doc_destination = $doc_dir . $next['file'] . '_files' . '/' . $next['file'] . '.html';

		// Convert item.
		$time_start = get_microtime();
		$this->convertToHtml($doc_source, $doc_destination);
		$time_end = get_microtime();

		// Record usage.
		$this->Usage->save($next['user_id'], $next['client_id'], 'queue', filesize($doc_source), round($time_end - $time_start));

		// Move the destination HTML file up a level to the client's import directory.
		rename($doc_destination, $doc_dir . $next['file'] . '.html');

		// Remove source document.
		unlink($doc_source);

		// Update queue item in database.
		$this->Queue->updateDone($next['import_queue_id']);

		// Email user with link when conversion finished.
		$subject = "Ready to import $old_file";
		$message = "The Word document $old_file has finished processing and is ready for import.\n\nImport the document into your MindTouch instance from the following link: " . ABSURL . 'client/' . $client['code'] . '/preparse/' . $next['file'];
		$send = send_email($subject, $message, $user['email'], $user['first_name'] . ' ' . $user['last_name']);
		if ($send !== true) {
			echo 'Message could not be sent.';
			echo 'Mailer Error: ' . $send;
		} else {
			echo 'Message has been sent';
		}
	}

	/**
	 * Converts the given document to the destination document using phpDocx.
	 * @param string $source Path and file name of source document.
	 * @param string $destination Path and file name of destination document.
	 */
	private function convertToHtml($source, $destination) {
		require_once(ABSPATH . 'includes/phpdocx/classes/CreateDocx.inc');
		$docx = new TransformDocAdvOpenOffice();
		$docx->transformDocument($source, $destination);
	}

	/**
	 * Routes the controller to the function.
	 * @param  string $route Route to follow.
	 */
	public function route($route = '') {
		if ($route === 'queue') {
			$this->processQueue();
		}
	}
}
