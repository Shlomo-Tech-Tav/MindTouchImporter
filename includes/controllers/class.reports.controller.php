<?php
class ReportsController extends ClientController {
	/**
	 * Instance of reports class.
	 * @var object
	 */
	protected $Reports;
	protected $report;

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

		if (!empty($Controller->Client)) {
			$this->Client = $Controller->Client;
		}

		$this->Reports = new Reports($this->Database);
	}

	/**
	 * Deletes the submitted report.
	 */
	protected function delete() {
		// Validate.
		if (!$this->validateForm()) {
			redirect(ABSURL . 'error');
		}
		// Only admins can delete reports.
		if (!is_admin()) {
			redirect(ABSURL . 'error');
		}
		nonce_generate();

		$delete = $this->Reports->delete($this->report['report_id']);
		redirect(ABSURL . 'client/' . $this->Client->config['code'] . '/report');
	}

	/**
	 * Default view function that displays a list of available reports.
	 */
	protected function reports() {
		// Get client's reports.
		if (is_admin()) {
			$reports = $this->Reports->getAll($this->Client->config['code']);
		} else {
			$reports = $this->Reports->getProduction($this->Client->config['code']);
		}
		if (!is_array($reports)) {
			$reports = array();
		}

		// Prepare data for contents.
		$data = array(
			'client' =>& $this->Client->config,
			'reports' =>& $reports
		);

		$this->title = $this->Client->config['name'] . ' Import Reports';
		$this->content = $this->View->getHtml('content-reports', $data, $this->title);
	}

	/**
	 * Loads the provided report ID and sets the internal report property.
	 * @param integer $report_id Report ID to load.
	 * @return boolean True on success.
	 */
	protected function reportSet($report_id) {
		$report = $this->Reports->load($report_id);
		if (empty($report)) {
			return false;
		} else {
			$this->report = $report;
			return true;
		}
	}

	/**
	 * Routes the controller to the function.
	 */
	public function route() {
		// Set the client when not set.
		if (empty($this->Client)) {
			$client = $this->Router->getCurrentRoute(1);
			if (!empty($client) && !$this->clientSet($client)) {
				$_SESSION['title'] = 'Error';
				$_SESSION['data'] = array(
					'error' => 'The client selected was invalid.',
					'link' => ''
				);
				redirect(ABSURL . 'error');
			}

			// Load the client's menu.
			$this->Menu->add($this->clientMenu($this->Router->getCurrentRoute(2)));
		}

		// Check for the report ID.
		if ($this->Router->getCurrentRouteCount() >= 4) {
			$report_id = $this->Router->getCurrentRoute(3);
			if (!$this->reportSet($report_id)) {
				$_SESSION['title'] = 'Error';
				$_SESSION['data'] = array(
					'error' => 'The report selected was invalid.',
					'link' => 'client/' . $this->Client->config['code'] . '/report'
				);
				redirect(ABSURL . 'error');
			}
			$sub_action = $report_id = $this->Router->getCurrentRoute(4);
			if (!empty($sub_action)) {
				$this->$sub_action();
			} else {
				$this->view();
			}
		} else {
			// Get the function mapped to the route.
			$function = $this->Router->mappedFunction();
			$this->$function();
		}
	}

	/**
	 * View function that displays a specific report.
	 */
	protected function view() {
		// Make sure the report exists.
		if (empty($this->report)) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The selected report was invalid.',
				'link' => 'reports'
			);
			redirect(ABSURL . 'error');
		}

		// Prepare data for contents.
		$this->title = 'Import Report: ' . $this->report['report_data']['import'];

		// Deal with the destination link.
		if (empty($this->report['report_data']['destination_link'])) {
			if ($this->report['report_data']['use_test'] === 'yes') {
				$this->report['report_data']['destination_link'] = $this->report['report_data']['client']['import_domain'] . '/' . $this->report['report_data']['client']['import_path'];
			} else {
				$this->report['report_data']['destination_link'] = $this->report['report_data']['client']['api_url'] . '/' . $this->report['report_data']['client']['prod_import_path'];
			}
		}

		$this->content = $this->View->getHtml('content-report-view', $this->report['report_data'], $this->title);
	}
}
