<?php
class ImportQueue {
	/**
	 * Instance of database class.
	 * @var object
	 */
	public $Database;
	/**
	 * Name of the database table.
	 * @var string
	 */
	private $table = 'import_queue';

	/**
	 * Constructs the class.
	 * @param object &$Database Database object.
	 */
	public function __construct(&$Database) {
		$this->Database = $Database;
	}

	/**
	 * Returns the next import item to be processed.
	 * @return array Array containing queue item to be processed.
	 */
	public function getNext() {
		$where = array(
			'status' => 'queue',
			'ORDER' => 'created_on ASC',
			'LIMIT' => 1
		);
		$next = $this->Database->select($this->table, '*', $where);
		return $next;
	}

	/**
	 * Returns the imports for the client that haven't processed.
	 * @param integer $client_id Client ID.
	 * @return array Array containing queued items to be processed.
	 */
	public function getByClient($client_id) {
		$where = array(
			'AND' => array(
				'client_id' => $client_id,
				'status[!]' => 'done'
			),
			'ORDER' => 'file ASC'
		);
		$queue = $this->Database->select($this->table, '*', $where);
		return $queue;
	}

	/**
	 * Adds the queue item to the database.
	 * @param integer $user_id User ID.
	 * @param integer $client_id Client ID.
	 * @param string $file Name of file without extension.
	 * @param string $extension The file's extension.
	 * @param string $status Status of the queue. Allowed: 'done','fail','processing','queue'.
	 * @return integer ID of the queue item.
	 */
	public function save($user_id, $client_id, $file, $extension, $status = 'queue') {
		// Build array of queue information to save.
		$queue_data = array(
			'user_id' => $user_id,
			'client_id' => $client_id,
			'file' => $file,
			'extension' => $extension,
			'status' => $status
		);
		$queue_id = $this->Database->insert($this->table, $queue_data);
		return $queue_id;
	}

	/**
	 * Updates the queue item.
	 * @param integer $queue_id The queue's ID.
	 * @param array $queue_data Queue data.
	 * @return boolean True on success.
	 */
	public function update($queue_id, $queue_data) {
		$this->Database->update($this->table, $queue_data, array('import_queue_id' => $queue_id));
		return true;
	}

	/**
	 * Updates the queue item to a status of processing.
	 * @param integer $queue_id ID of queue item.
	 * @return boolean True on success.
	 */
	public function updateProcessing($queue_id) {
		return $this->update($queue_id, array('status' => 'processing'));
	}

	/**
	 * Updates the queue item to a status of done.
	 * @param integer $queue_id ID of queue item.
	 * @return boolean True on success.
	 */
	public function updateDone($queue_id) {
		return $this->update($queue_id, array('status' => 'done'));
	}
}