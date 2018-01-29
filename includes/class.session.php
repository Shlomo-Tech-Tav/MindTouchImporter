<?php
class Session {
	/**
	 * The database object.
	 * @var object
	 */
	private $Database;
	/**
	 * How long, in seconds, to keep the session alive since the last click.
	 * @var integer
	 */
	private $life_time = 86400;
	/**
	 * Name of the session database table.
	 * @var string
	 */
	private $table = 'session';

	/**
	 * Sets up the session object.
	 * @param object &$Database Database object.
	 */
	public function __construct(&$Database) {
		$this->Database = $Database;

		session_set_save_handler(
			array(&$this,'open'),
			array(&$this,'close'),
			array(&$this,'read'),
			array(&$this,'write'),
			array(&$this,'destroy'),
			array(&$this,'gc')
		);
	}

	/**
	 * Opens the session.
	 * @return boolean Returns true.
	 */
	public function open() {
		return true;
	}

	/**
	 * Closes the session
	 * @return boolean Returns true.
	 */
	public function close() {
		return true;
	}

	/**
	 * Reads the session information.
	 * @param string $session_id Session ID.
	 * @return string $session_data Serialized string of session data.
	 */
	public function read($session_id) {
		// Build where clause for the select query.
		$where = array(
			"AND" => array(
				"session_id" => $session_id,
				"expires[>]" => time()
			)
		);
		$rows = $this->Database->select($this->table, 'session_data', $where);
		$session_data = $rows[0];
		return $session_data;
	}

	/**
	 * Updates the session information.
	 * @param string $session_id Session ID.
	 * @param string $session_data Serialized string of session data.
	 * @return boolean Returns true.
	 */
	public function write($session_id, $session_data) {
		// Prepare data.
		$data = array(
			"session_data" => $session_data,
			"expires" => time() + $this->life_time
		);

		// See if there's a record.
		$where = array(
			"session_id" => $session_id
		);
		$rows = $this->Database->select($this->table, 'session_id', $where);

		if (count($rows) > 0) {
			// Update.
			$this->Database->update($this->table, $data, $where);
		} else {
			// Insert.
			$data['session_id'] = $session_id;
			$this->Database->insert($this->table, $data);
		}

		return true;
	}

	/**
	 * Destroys the given session.
	 * @param string $session_id Session ID.
	 * @return boolean Returns true.
	 */
	public function destroy($session_id) {
		$where = array(
			"session_id" => $session_id
		);
		$rows = $this->Database->delete($this->table, $where);
		return true;
	}

	/**
	 * Destroys any expired sessions.
	 * @return boolean Returns true.
	 */
	public function gc() {
		$where = array(
			"#expires[<]" => "UNIX_TIMESTAMP()"
		);
		$rows = $this->Database->delete($this->table, $where);
		return true;
	}
}
