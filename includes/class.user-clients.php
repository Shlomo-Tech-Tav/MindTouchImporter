<?php
class UserClients {
	/**
	 * Instance of database class.
	 * @var object
	 */
	public $Database;
	/**
	 * Name of the database table.
	 * @var string
	 */
	private $table = 'user_to_clients';

	/**
	 * Constructs the class.
	 * @param object &$Database Database object.
	 */
	public function __construct(&$Database) {
		$this->Database = $Database;
	}

	/**
	 * Deletes the user's clients.
	 * @param integer $user_id User ID.
	 * @return integer $delete Number of rows deleted.
	 */
	public function delete($user_id) {
		$where = array(
			'user_id' => $user_id
		);
		$delete = $this->Database->delete($this->table, $where);
		return $delete;
	}

	/**
	 * Returns the client IDs the user is attached to.
	 * @param integer $user_id User ID.
	 * @return array $clients Client IDs user is attached to.
	 */
	public function get($user_id) {
		$where = array(
			'user_id' => $user_id
		);
		$clients = $this->Database->select($this->table, 'client_id', $where);
		return $clients;
	}

	/**
	 * Returns the client information the user is attached to.
	 * @param integer $user_id User ID.
	 * @return array $clients Clients user is attached to.
	 */
	public function getAll($user_id) {
		$select = array(
			'user_id',
			'clients.client_id',
			'clients.code',
			'clients.name',
			'clients.archived',
		);
		$join = array(
			'[>]clients' => 'client_id'
		);
		$where = array(
			'user_id' => $user_id,
			"ORDER" => "clients.name ASC"
		);
		$clients = $this->Database->select($this->table, $join, $select, $where);
		return $clients;
	}

	/**
	 * Updates the user's clients.
	 * @param integer $user_id The user's ID.
	 * @param array $clients Array of client IDs the user should have access to.
	 * @return boolean True on success.
	 */
	public function update($user_id, $clients) {
		$insert = array();
		foreach ($clients as $client) {
			$insert[] = array(
				'user_id' => $user_id,
				'client_id' => $client
			);
		}
		$this->Database->insert($this->table, $insert);
		return true;
	}

}
