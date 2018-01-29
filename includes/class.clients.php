<?php
class Clients {
	public $clients;
	/**
	 * Instance of database class.
	 * @var object
	 */
	public $Database;
	public $directory;
	public $import_types;
	/**
	 * Name of the clients database table.
	 * @var string
	 */
	private $table = 'clients';

	/**
	 * Instantiates clients class.
	 */
	public function __construct(&$Database) {
		$this->Database = $Database;
		$this->directory = ABSPATH . 'clients';

		// Set list of clients.
		$this->clients = $this->buildClientsList();
	}

	/**
	 * Searches the clients directory and loads configuration information
	 * for all available clients.
	 * @return array $clientsList Configuration information for every installed client.
	 */
	protected function buildClientsList() {
		$clientsList = array();

		// Get the clients.
		$clients = $this->getAll();

		// Loop through each client to get their settings.
		foreach ($clients as $client) {
			// Open the configuration for each client.
			$client_dir = $this->directory . '/' . $client['code'] . '/';
			if (!file_exists($client_dir . $client['code'] . '.php')) {
				continue;
			}

			// Reset the array before getting the next client's data.
			$config = array();
			include($client_dir . $client['code'] . '.php');
			$clientsList[$client['code']] = array_merge($config, $client);
		}

		return $clientsList;
	}

	/**
	 * Gets information for all available import types.
	 * @return array $importTypesList Information on available import types.
	 */
	protected function buildImportTypesList() {
		$ImportTypes = new ImportTypes($this->Database);
		return $ImportTypes->getAll();
	}

	/**
	 * Checks if the client exists.
	 * @param string $client Client code.
	 * @return boolean
	 */
	function clientExists($client) {
		if (array_key_exists($client, $this->clients)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Creates the client.
	 * @param array $client_data client data.
	 * @return boolean True on success.
	 */
	public function create($client_data) {
		// Add client to the database.
		$client_id = $this->Database->insert($this->table, $client_data);

		// Create the client's directories.
		$this->createClientDirectory($client_data['code']);
		$this->createClientImportsDirectory($client_data['code']);

		// Create the configuration and the class files.
		$this->createConfig($client_data['code']);
		$this->createClass($client_data['code'], $client_data['class'], $client_data['import_type_id']);

		return $client_id;
	}

	/**
	 * Creates the client directory.
	 * @param string $code Client's code.
	 */
	public function createClientDirectory($code) {
		mkdir($this->directory . '/' . $code);
	}

	/**
	 * Creates the client imports directory.
	 * @param string $code Client's code.
	 */
	public function createClientImportsDirectory($code) {
		mkdir($this->directory . '/' . $code . '/imports');
	}

	/**
	 * Creates the client's default configuration.
	 * @param string $code Code for the client.
	 * @return boolean True on success.
	 */
	private function createConfig($code) {
		$contents = file_get_contents(ABSPATH . 'templates/clients-default-config.php');
		$return = file_put_contents($this->directory . '/' . $code . '/' . $code . '.php', $contents);
		if ($return !== false) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Creates the client's default class file.
	 * @param string $code Code for the client.
	 * @param string $class Class name for the client.
	 * @param integer $import_type_id Import type ID.
	 * @return boolean True on success.
	 */
	private function createClass($code, $class, $import_type_id) {
		// Get the import type information.
		if (!empty($import_type_id)) {
			$ImportTypes = new ImportTypes($this->Database);
			$import_type = $ImportTypes->get($import_type_id);

			// Get contents and replace import type variables.
			$contents = file_get_contents(ABSPATH . 'templates/clients-import-class.php');
			$contents = str_replace('$import_type_class', $import_type[0]['import_type_class'], $contents);
			$contents = str_replace('$import_type_file', $import_type[0]['import_type_file'], $contents);
		} else {
			// Get contents.
			$contents = file_get_contents(ABSPATH . 'templates/clients-default-class.php');
		}

		// Replace common variables.
		$contents = str_replace('$code', $code, $contents);

		// Store class.
		$return = file_put_contents($this->directory . '/' . $code . '/' . $class, $contents);
		if ($return !== false) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns the client information for the provided client ID.
	 * @param integer $client Client ID or client code.
	 * @return array $client Client information.
	 */
	public function get($client) {
		// Determine whether $client is the ID or code.
		if (is_int($client)) {
			$where = array(
				'client_id' => $client
			);
		} else {
			$where = array(
				'code' => $client
			);
		}
		$client = $this->Database->get($this->table, '*', $where);
		return $client;
	}

	/**
	 * Get all of the clients in the database.
	 * @return array $clients All the clients in the clients table.
	 */
	public function getAll($archived = false) {
		$select = '*';
		$join = array(
			'[>]import_types' => 'import_type_id'
		);
		$where = array(
			"archived" => 0,
			"ORDER" => $this->table . ".name ASC"
		);
		$clients = $this->Database->select($this->table, $join, $select, $where);
		return $clients;
	}

	/**
	 * Loads the given client, including its object.
	 * @param string $client Client code.
	 * @return array Information about the client.
	 */
	function loadClient($client) {
		// Make sure the client exists.
		if (!$this->clientExists($client)) {
			return false;
		}

		// Get the client's configuration information.
		$client = $this->clients[$client];

		// Deal with the client's allowed extensions.
		$allowed = preg_replace('/\s+/', '', $client['extensions']);
		if (!empty($allowed)) {
			$client['extensions'] = explode(',', $allowed);
		} else {
			$client['extensions'] = array();
		}
		$client['extensions'][] = 'zip';

		// Instantiate the client's object.
		require_once($this->directory . '/' . $client['code'] . '/' . $client['class']);
		$client = new $client['code']($this->Database, $client);

		return $client;
	}

	/**
	 * Sets the import types.
	 */
	public function setImportTypes() {
		$this->import_types = $this->buildImportTypesList();
	}

	/**
	 * Updates the client.
	 * @param integer $client_id The client's ID.
	 * @param array $client_data Client data.
	 * @return boolean True on success.
	 */
	public function update($client_id, $client_data) {
		$this->Database->update($this->table, $client_data, array('client_id' => $client_id));
		return true;
	}
}
