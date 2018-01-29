<?php
class Reports {
	/**
	 * The database object.
	 * @var object
	 */
	private $Database;
	/**
	 * Name of the reports database table.
	 * @var string
	 */
	private $table = 'reports';

	/**
	 * Constructs the Reports class.
	 * @param object &$Database Database object.
	 */
	public function __construct(&$Database) {
		$this->Database = $Database;
	}

	/**
	 * Deletes the report.
	 * @param integer $report_id Report ID.
	 * @return integer $delete Number of rows deleted.
	 */
	public function delete($report_id) {
		$where = array(
			'report_id' => $report_id
		);
		$delete = $this->Database->delete($this->table, $where);
		return $delete;
	}

	/**
	 * Returns all of the reports.
	 * @param string $client Provide client code to limit reports by client.
	 * @return array $reports Array containing the reports.
	 */
	public function getAll($client = '') {
		$select = array(
			'reports.report_id',
			'reports.user_id',
			'reports.client',
			'reports.import_title',
			'reports.production',
			'reports.created_on',
			'users.username',
			'users.first_name',
			'users.last_name'
		);
		$join = array(
			'[>]users' => 'user_id'
		);
		$where = array();
		if (!empty($client)) {
			$where = array(
				'client' => $client,
				"ORDER" => "created_on DESC",
			);
		}
		$reports = $this->Database->select($this->table, $join, $select, $where);
		return $reports;
	}

	/**
	 * Returns all of the production reports.
	 * @param string $client Provide client code to limit reports by client.
	 * @return array $reports Array containing the reports.
	 */
	public function getProduction($client = '') {
		$select = array(
			'reports.report_id',
			'reports.user_id',
			'reports.client',
			'reports.import_title',
			'reports.production',
			'reports.created_on',
			'users.username',
			'users.first_name',
			'users.last_name'
		);
		$join = array(
			'[>]users' => 'user_id'
		);

		if (!empty($client)) {
			$where = array(
				'AND' => array(
					'client' => $client,
					'production' => 1
				)
			);
		} else {
			$where = array(
				'production' => 1
			);
		}
		$reports = $this->Database->select($this->table, $join, $select, $where);
		return $reports;
	}

	/**
	 * Loads the given report based on its ID.
	 * @param integer $report_id Report ID.
	 * @param boolean $data_to_array Whether to convert the report data to an array.
	 * @return array $report All the data for the report.
	 */
	public function load($report_id, $data_to_array = true) {
		$select = array(
			'reports.report_id',
			'reports.user_id',
			'reports.client',
			'reports.import_title',
			'reports.production',
			'reports.report_data',
			'reports.created_on',
			'users.username',
			'users.first_name',
			'users.last_name'
		);
		$join = array(
			'[>]users' => 'user_id'
		);
		$where = array(
			"report_id" => $report_id
		);

		$report = $this->Database->get($this->table, $join, $select, $where);
		if ($data_to_array) {
			$report['report_data'] = json_decode($report['report_data'], true);
		}
		return $report;
	}

	/**
	 * Saves the report.
	 * @param integer $user_id User ID.
	 * @param string $client Client code.
	 * @param string $title Title of the import.
	 * @param boolean $production Whether the import was to production.
	 * @param array $data Array containing the data to store.
	 * @return integer $report_id ID of the report.
	 */
	public function save($user_id, $client, $title, $production, $data) {
		// Build array of report information to save.
		$report_data = array(
			'user_id' => $user_id,
			'client' => $client,
			'import_title' => $title,
			'production' => $production,
			'report_data' => json_encode($data)
		);
		$report_id = $this->Database->insert($this->table, $report_data);
		return $report_id;
	}

	/**
	 * Updates the report.
	 * @param integer $report_id The report's ID.
	 * @param array $report_data Report data.
	 * @return boolean True on success.
	 */
	public function update($report_id, $report_data) {
		$this->Database->update($this->table, $report_data, array('report_id' => $report_id));
		return true;
	}

}
