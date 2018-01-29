<?php
abstract class BaseController {
	/**
	 * Instance of clients class.
	 * @var object
	 */
	public $Clients;
	/**
	 * HTML output of function.
	 * @var string
	 */
	public $content = '';
	/**
	 * Instance of database class.
	 * @var object
	 */
	public $Database;
	/**
	 * Instance of menu class.
	 * @var object
	 */
	public $Menu;
	/**
	 * Instance of router class.
	 * @var object
	 */
	public $Router;
	/**
	 * Instance of session class.
	 * @var object
	 */
	public $Session;
	/**
	 * HTML title for the output.
	 * @var string
	 */
	public $title = '';
	/**
	 * Instance of user usage monitoring class.
	 * @var object
	 */
	protected $Usage;
	/**
	 * Instance of user clients class.
	 * @var object
	 */
	protected $UserClients;
	/**
	 * Clients the logged-in user has access to.
	 * @var array
	 */
	protected $user_clients = '';
	/**
	 * Instance of View class. Creates the content.
	 * @var object
	 */
	public $View;
	
	/**
	 * Instantiates objects.
	 */
	public function __construct($routes, $whitelist) {
		// Connect to database.
		$this->Database = new medoo(array(
			'database_type' => 'mysql',
			'database_name' => DB_NAME,
			'server' => DB_HOST,
			'username' => DB_USERNAME,
			'password' => DB_PASSWORD,
			'charset' => 'utf8'
		));

		// Start session.
		$this->Session = new Session($this->Database);
		session_start();

		$this->Router = new Router($routes, $whitelist);
		$this->View = new View();
		$this->Clients = new Clients($this->Database);
		$this->UserClients = new UserClients($this->Database);
		$this->Usage = new UserUsage($this->Database);

		// Deal with logged-in users.
		if (is_logged_in()) {
			// Set ADMIN constant to true for the one admin user.
			if (get_user_id() === 1) {
				define('ADMIN', true);
			} else {
				define('ADMIN', false);
			}

			// Get the user's clients.
			$this->user_clients = $this->UserClients->getAll(get_user_id());

			// Update router with the user's clients.
			$this->Router->setUserClients($this->user_clients);
		} else {
			define('ADMIN', false);
		}

		$this->Menu = new Menu($this->Router->getCurrentRoute(), $this->user_clients);
	}

	/**
	 * Builds content for errors.
	 */
	public function error() {
		nonce_generate();
		$this->title = !empty($_SESSION['title']) ? $_SESSION['title'] : 'Error';
		if (!empty($_SESSION['data'])) {
			$data = $_SESSION['data'];
		} else {
			$data = array(
				'error' => 'An unknown error ocurred.',
				'link' => ''
			);
		}
		$this->content = $this->View->getHtml('content-error', $data, $this->title);
	}

	/**
	 * Validates the form.
	 * @param string $form Name of form to validate.
	 * @return boolean
	 */
	public function validateForm($form = '') {
		if (!nonce_validate('post')) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The form submitted was invalid.'
			);
			return false;
		}

		switch ($form) {
			// Validate the import form.
			case 'import':
				if (empty($_POST['import'])) {
					$_SESSION['title'] = 'Error';
					$_SESSION['data'] = array(
						'error' => 'No file was selected to import.'
					);
					return false;
				}
				if (!$this->Client->importFileExists($_POST['import'])) {
					$_SESSION['title'] = 'Error';
					$_SESSION['data'] = array(
						'error' => 'The selected file does not exist.'
					);
					return false;
				}
			break;
		}
		return true;
	}

	abstract public function route();
}
