<?php
class ClientsController extends BaseController {
	/**
	 * Instance of users class.
	 * @var object
	 */
	protected $Users;
	/**
	 * Current client.
	 * @var array
	 */
	protected $client;

	/**
	 * Constructs the users controller class. Requires a parent constructor.
	 * @param object $Controller The main controller.
	 */
	public function __construct(&$Controller) {
		$this->Database = $Controller->Database;
		$this->Session = $Controller->Session;
		$this->Router = $Controller->Router;
		$this->View = $Controller->View;
		$this->Clients = $Controller->Clients;
		$this->Menu = $Controller->Menu;
		$this->Users = new Users($this->Database);
	}

	/**
	 * Create the client.
	 */
	protected function create() {
		$this->Clients->setImportTypes();
		$data = array(
			'import_types' => $this->Clients->import_types
		);
		$this->title = 'Create Client';
		$this->content = $this->View->getHtml('content-clients-create', $data, $this->title);
	}

	/**
	 * Displays edit form for the client.
	 */
	protected function edit() {
		// Make sure a client exists.
		if (empty($this->client)) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The selected client was invalid.',
				'link' => 'management/clients'
			);
			redirect(ABSURL . 'error');
		}

		// Prepare data for contents.
		nonce_generate();
		$this->Clients->setImportTypes();
		$data = array(
			'client' =>& $this->client,
			'import_types' => $this->Clients->import_types
		);
		$this->title = 'Edit Client: ' . $this->client['name'];
		$this->content = $this->View->getHtml('content-clients-edit', $data, $this->title);
	}

	/**
	 * Updates or creates the client.
	 */
	protected function process() {
		// Validate.
		if (!$this->validateForm()) {
			redirect(ABSURL . 'error');
		}

		// Prepare code and class.
		$code = preg_replace("/[^a-zA-Z0-9]/", "", strtolower($_POST['code']));
		$class = 'class.' . preg_replace("/[^a-zA-Z0-9\.\-]/", "", strtolower($_POST['code'])) . '.php';

		// Build array of client information to update.
		$client = array(
			'name' => $_POST['name'],
			'code' => $code,
			'pages_per_import' => (int) $_POST['pages_per_import'],
			'import_domain' => $_POST['import_domain'],
			'import_path' => $_POST['import_path'],
			'api_url' => $_POST['api_url'],
			'api_username' => $_POST['api_username'],
			'api_password' => $_POST['api_password'],
			'prod_import_path' => $_POST['prod_import_path'],
			'import_drafts' => $_POST['import_drafts'],
			'archived' => $_POST['archived'],
		);

		if (!empty($_POST['extensions'])) {
			$client['extensions'] = $_POST['extensions'];
		}

		if (empty($_POST['client_id'])) {
			// Add the class.
			$client['class'] = $class;

			// Add the import type.
			$client['import_type_id'] = (int) $_POST['import_type_id'];
			if (!empty($client['import_type_id'])) {
				$ImportTypes = new ImportTypes($this->Database);
				$importType = $ImportTypes->get($client['import_type_id']);
				$client['extensions'] = $importType[0]['import_type_extensions'];
			}

			// Create the client.
			$client_id = $this->Clients->create($client);

			// Add admin to the client.
			if ($client_id !== false) {
				$UserClients = new UserClients($this->Database);
				$UserClients->update(1, array($client_id));
			}
		} else {
			// Update the client.
			$this->Clients->update($_POST['client_id'], $client);
		}

		// Forward to the client page.
		redirect(ABSURL . 'management/clients');
	}

	/**
	 * Routes the controller to the function.
	 */
	public function route() {
		// Set the client.
		$client = $this->Router->getCurrentRoute(3);
		if (!empty($client)) {
			if (!$this->clientSet($client)) {
				$_SESSION['title'] = 'Error';
				$_SESSION['data'] = array(
					'error' => 'The selected client was invalid.',
					'link' => 'management/clients'
				);
				redirect(ABSURL . 'error');
			}
		}

		// Get the function mapped to the route.
		$function = $this->Router->mappedFunction();
		$this->$function();
	}

	/**
	 * Validates a user ID before forwarding to the user edit page.
	 */
	protected function select() {
		// Validate.
		if (!$this->validateForm()) {
			redirect(ABSURL . 'error');
		}
		nonce_generate();

		// Get client.
		$client = $this->Clients->get((int) $_POST['client_id']);

		// Send to error page when client doesn't exist.
		if (empty($client)) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The selected client was invalid.',
				'link' => 'management/clients'
			);
			redirect(ABSURL . 'error');
		}

		// Forward to the user edit page.
		redirect(ABSURL . 'management/clients/edit/' . $client['code']);
	}

	/**
	 * Default function that displays a list of clients to edit.
	 */
	protected function clients() {
		// Get clients.
		$clients = $this->Clients->getAll();

		// Prepare data for contents.
		nonce_generate();
		$data = array(
			'clients' =>& $clients
		);

		$this->title = PROJECT_NAME;
		$this->content = $this->View->getHtml('content-clients', $data, $this->title);
	}

	/**
	 * Loads the provided user as the current client.
	 * @param integer $client User ID, username, or user email to load.
	 * @return boolean True when the user exists.
	 */
	protected function clientSet($client) {
		$client = $this->Clients->get($client);
		if (empty($client)) {
			return false;
		} else {
			$this->client = $client;
			return true;
		}
	}
}
