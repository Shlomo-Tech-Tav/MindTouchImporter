<?php
class UserUsage {
	/**
	 * Instance of database class.
	 * @var object
	 */
	public $Database;
	/**
	 * Name of the database table.
	 * @var string
	 */
	private $table = 'user_usage';
	private $types = array(
		'login',
		'logout',
		'parse',
		'process',
		'queue',
		'upload'
	);

	/**
	 * Constructs the class.
	 * @param object &$Database Database object.
	 */
	public function __construct(&$Database) {
		$this->Database = $Database;
	}

	/**
	 * Saves the usage report.
	 * @param integer $user_id User ID.
	 * @param integer $client_id Client ID.
	 * @param enum $type Type of usage to record. Must be one from types array.
	 * @param integer $size Size of import file in bytes.
	 * @param integer $time Number of seconds process took.
	 * @param integer $pages Number of pages parsed or processed.
	 * @return integer $usage_id ID of the usage entry.
	 */
	public function save($user_id, $client_id, $type, $size = 0, $time = 0, $pages = 0) {
		// Enforce type.
		if (!in_array($type, $this->types)) {
			return false;
		}

		// Build array of report information to save.
		$usage_data = array(
			'user_id' => $user_id,
			'client_id' => $client_id,
			'type' => $type,
			'size' => $size,
			'time' => $time,
			'pages' => $pages,
			'date' => date('Y-m-d H:i:s')
		);
		$usage_id = $this->Database->insert($this->table, $usage_data);
		return $usage_id;
	}

}
