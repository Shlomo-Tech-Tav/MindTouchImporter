<?php
class ImportTypes {
	/**
	 * Instance of database class.
	 * @var object
	 */
	public $Database;
	/**
	 * Name of the database table.
	 * @var string
	 */
	private $table = 'import_types';

	/**
	 * Constructs the class.
	 * @param object &$Database Database object.
	 */
	public function __construct(&$Database) {
		$this->Database = $Database;
	}

	/**
	 * Returns information on the import type.
	 * @param integer $import_type_id Import type ID.
	 * @return array $import_type Information on the import type.
	 */
	public function get($import_type_id) {
		$where = array(
			'import_type_id' => $import_type_id
		);
		$import_type = $this->Database->select($this->table, '*', $where);
		return $import_type;
	}

	/**
	 * Get all of the import types in the database.
	 * @return array $import_types All the import types in the table.
	 */
	public function getAll() {
		$where = array(
			"ORDER" => "import_type_name ASC"
		);
		$import_types = $this->Database->select($this->table, '*', $where);
		return $import_types;
	}
}
