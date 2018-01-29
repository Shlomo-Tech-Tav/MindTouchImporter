<?php
class ClientController extends BaseController {
	/**
	 * Instance of client class. Deals with the import file.
	 * @var object
	 */
	protected $Client;

	/**
	 * Constructs the client controller class. Requires a parent constructor.
	 * @param object $Controller The main controller.
	 */
	public function __construct(&$Controller) {
		$this->Database = $Controller->Database;
		$this->Session = $Controller->Session;
		$this->Router = $Controller->Router;
		$this->View = $Controller->View;
		$this->Clients = $Controller->Clients;
		$this->Menu = $Controller->Menu;
		$this->Usage = $Controller->Usage;
	}

	/**
	 * Function that displays a client's list of imports.
	 */
	protected function clientImport() {
		// Prepare data for contents.
		nonce_generate();
		$data = array(
			'client' =>& $this->Client->config,
			'description' => 'This tool allows content in the import files to be imported into the MindTouch instance for ' . $this->Client->config['name'] . '.',
			'form_action' => ABSURL . 'client/' . $this->Client->config['code'] . '/parse',
			'imports' =>& $this->Client->import_files,
			'queue' =>& $this->Client->import_queue,
			'submit_button' => 'Parse the import file'
		);
		$this->title = 'Import ' . $this->Client->config['name'] . ' Content';

		// Admins are the only ones who can upload to the test instance.
		if (is_admin()) {
			$data['upload_form'] = $this->View->getHtml('content-import-upload', $data, $this->title);
			$this->content = $this->View->getHtml('content-import', $data, $this->title);
		} else {
			if (valid_production_credentials($this->Client->config['api_url'], $this->Client->config['api_username'], $this->Client->config['api_password'])) {
				$data['upload_form'] = $this->View->getHtml('content-import-upload', $data, $this->title);
				$this->content = $this->View->getHtml('content-import', $data, $this->title);
			} else {
				$this->content = $this->View->getHtml('content-import-error-production', $data, $this->title);
			}
		}
	}

	/**
	 * Parses the selected client's import file.
	 * @param boolean $validate Whether to validate the form.
	 * @param string  $import Import file to parse.
	 */
	protected function clientImportParse($validate = true, $import = '') {
		ini_set('max_execution_time', TIME_LIMIT);
		// Validate.
		if ($validate) {
			if (!$this->validateForm('import')) {
				redirect(ABSURL . 'error');
			}
		}
		if (!DEBUG) {
			nonce_generate();
		}

		// Reset session.
		session_reset_custom();

		// Deal with import file.
		if (empty($import)) {
			$import = $_POST['import'];
		}
		$time_start = get_microtime();
		$parse_data = $this->Client->parse($import);
		$time_end = get_microtime();
		// xmp_print($parse_data, 'parse_data');
		// exit;

		// Record usage.
		$this->Usage->save(get_user_id(), $this->Client->config['client_id'], 'parse', $this->Client->getImportFileSize(), round($time_end - $time_start), $parse_data['pages_total']);

		// Prepare data for preprocess page and redirect.
		$_SESSION['title'] = 'Import ' . $this->Client->config['name'] . ' Content';
		$_SESSION['data'] = $parse_data;
		$_SESSION['data']['import'] = $import;
		$_SESSION['data']['client'] = $this->Client->config;
		$_SESSION['data']['destination_link_production'] = $this->Client->config['api_url'] . '/' . $this->Client->config['prod_import_path'];
		$_SESSION['data']['destination_link_test'] = $this->Client->config['import_domain'] . '/' . $this->Client->config['import_path'];
		$_SESSION['data']['pages_to_import'] = $parse_data['pages_data'];
		redirect(ABSURL . 'client/' . $this->Client->config['code'] . '/preprocess');
	}

	/**
	 * Allows user to parse provided import without selecting from the dropdown.
	 */
	protected function clientImportPreParse() {
		// Get the file to process.
		$import = $this->Router->getCurrentRoute(3);
		// Check to see if the import exists.
		if (!empty($import) && $this->Client->importFileExists($import)) {
			// Parse the import.
			$this->clientImportParse(false, $import);
		} else {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The import was not found.',
				'link' => 'client/' . $this->Client->config['code']
			);
			redirect(ABSURL . 'error');
		}
	}

	/**
	 * Function that displays a preview of the client's import.
	 */
	protected function clientImportPreprocess() {
		nonce_generate();
		$this->title = !empty($_SESSION['title']) ? $_SESSION['title'] : 'Analyze Import';
		if (!empty($_SESSION['data'])) {
			$data = $_SESSION['data'];
		} else {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'No results to display.',
				'link' => 'client/' . $this->Client->config['code']
			);
			redirect(ABSURL . 'error');
		}

		// Build nested array of pages.
		$data['nested_pages'] = build_pages_tree($data['pages_data']);

		// Get the production tree.
		$data['target_select'] = array();
		if (valid_production_credentials($this->Client->config['api_url'], $this->Client->config['api_username'], $this->Client->config['api_password'])) {
			$data['target_select'] = $this->Client->buildMindTouchProductionTree('home', 'fancytree', 1);
		}

		$this->content = $this->View->getHtml('content-import-preprocess', $data, $this->title);
	}

	/**
	 * Processes the client's import. This function controls the adding of
	 * the content to the MindTouch instance.
	 */
	protected function clientImportProcess() {
		ini_set('max_execution_time', TIME_LIMIT);
		// Validate.
		if (!$this->validateForm('import')) {
			redirect(ABSURL . 'error');
		}
		if (!DEBUG) {
			// nonce_generate();
		}

		// Process the import file.
		$import = !empty($_POST['import']) ? $_POST['import'] : '';
		$use_test = !empty($_POST['use_test']) ? true : false;
		$target_select = !empty($_POST['target_select']) ? (int) $_POST['target_select'] : 0;
		$delete_import = !empty($_POST['delete_import']) ? true : false;
		if (empty($_SESSION['time_start'])) {
			$_SESSION['time_start'] = get_microtime();
		}
		$this->Client->importSetFile($import);
		if ($this->Client->isImportFinished()) {
			// The import has finished. Build process data from session.
			$data = session_array('data');
			$data['assets_added'] = session_array('assets_added');
			$data['assets_dir'] = $this->Client->getDirectoryAssets($import);
			$data['assets_unadded'] = $this->Client->getImportAssetsNotAdded($data['assets_added']);
			$data['successes'] = session_array('successes');
			$data['failures'] = session_array('failures');
			$data['pages_with_internal_links'] = session_array('pages_with_internal_links');
			// $data['pages_with_tables'] = session_array('pages_with_tables');
			// $data['pages_with_assets'] = session_array('pages_with_assets');
			$data['import'] = $import;
			$data['use_test'] = !empty($use_test) ? 'yes' : '';
			$data['target_select'] = !empty($target_select) ? (int) $target_select : 0;
			$data['delete_import'] = !empty($delete_import) ? 'yes' : '';
			$data['client'] = $this->Client->config;

			// Record the destination link.
			if ($data['use_test'] === 'yes') {
				$data['destination_link'] = $data['destination_link_test'];
			} else {
				$data['destination_link'] = $data['destination_link_production'];
			}

			// Store the report.
			$Reports = new Reports($this->Database);
			$report_id = $Reports->save(get_user_id(), $this->Client->config['code'], $data['import'], (empty($use_test) ? 1 : 0), $data);

			// Record usage.
			$time_end = get_microtime();
			$this->Usage->save(get_user_id(), $this->Client->config['client_id'], 'process', $this->Client->getImportFileSize(), round($time_end - $_SESSION['time_start']), count($data['successes']) + count($data['failures']));

			// Delete the import and its files.
			if ($delete_import) {
				$this->Client->importRemove();
			}

			// Reset session.
			session_reset_custom();

			// Prepare data for results page.
			$_SESSION['title'] = 'Import ' . $this->Client->config['name'] . ' Content';
			session_array('report_id', $report_id);

			// Forward to results page.
			redirect(ABSURL . 'client/' . $this->Client->config['code'] . '/results');
		} else {
			// The import hasn't finished. Continue processing.
			$process_data = $this->Client->process($import, $target_select, $use_test);

			// Prepare data for processing page.
			$this->title = 'Import ' . $this->Client->config['name'] . ' Content';
			$data = array();
			if (!empty($_SESSION['data'])) {
				$data = $_SESSION['data'];
			}

			if (!$use_test) {
				$data['destination_link_production'] = $this->Client->config['api_url'] . '/' . $this->Client->config['import_path'];
			}
			$data['import'] = $import;
			$data['use_test'] = !empty($use_test) ? 'yes' : '';
			$data['target_select'] = !empty($target_select) ? (int) $target_select : 0;
			$data['delete_import'] = !empty($delete_import) ? 'yes' : '';
			$data['client'] = $this->Client->config;
			$this->content = $this->View->getHtml('content-import-processing', $data, $this->title);
		}
	}

	/**
	 * Deals with the uploaded import file.
	 */
	protected function clientImportUpload() {
		// Process the file upload.
		$time_start = get_microtime();
		$zip_file = '';
		if (!empty($this->Client->config['zip_file'])) {
			$zip_file = $this->Client->config['zip_file'];
		}
		$upload = new Upload($_FILES['importUpload'], $this->Client->config['extensions'], $zip_file);
		$file = $upload->getFile();
		if ($upload->move(ABSPATH . 'clients/' . $this->Client->config['code'] . '/imports/')) {
			// The upload was successful.
			// Check for Word documents.
			if ($file['extension'] === 'doc' || $file['extension'] === 'docx') {
				// Add to import queue.
				$Queue = new ImportQueue($this->Database);
				$Queue->save(get_user_id(), $this->Client->config['client_id'], $file['filename'], $file['extension'], 'queue');

				// Inform user that the file is being processed and to expect an email.
				$data = array(
					'client' => $this->Client->config['code'],
					'error' => true,
					'message' => "The Word document was updated successfully. It will take time to process. An email message will be sent when it's ready for importing.",
					'refresh' => true
				);
			} else {
				$import = $upload->getImportFile();
				$data = array(
					'client' => $this->Client->config['code'],
					'error' => false,
					'import' => $import,
					'message' => "The file was uploaded successfully.",
					'refresh' => false
				);
			}
		} else {
			// The upload failed.
			$data = array(
				'client' => $this->Client->config['code'],
				'error' => true,
				'message' => $upload->getError(),
				'refresh' => false
			);
		}
		$time_end = get_microtime();

		// Record usage.
		$this->Usage->save(get_user_id(), $this->Client->config['client_id'], 'upload', $file['size'], round($time_end - $time_start));

		if (AJAX) {
			$this->content = $this->View->getJson($data);
		}
	}

	/**
	 * Function that displays the results of the client's import.
	 */
	protected function clientImportResults() {
		nonce_generate();
		$this->title = 'Process Import';

		// Make sure there's a report ID.
		if (empty($_SESSION['report_id'])) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'No results to display.',
				'link' => 'client/' . $this->Client->config['code']
			);
			redirect(ABSURL . 'error');
		}

		// Get the report data.
		$Reports = new Reports($this->Database);
		$report = $Reports->load((int) $_SESSION['report_id']);
		if (empty($report)) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'No results to display.',
				'link' => 'client/' . $this->Client->config['code']
			);
			redirect(ABSURL . 'error');
		}
		$this->content = $this->View->getHtml('content-results', $report['report_data'], $this->title);
	}

	/**
	 * Builds the client's menu items.
	 * @param string $route Route of the application.
	 * @return string $menu
	 */
	function clientMenu($route) {
		if (method_exists($this->Client, 'menuClientCustom')) {
			$this->Menu->addItems($this->Client->menuClientCustom());
		} else {
			$this->Menu->addItem($this->Client->config['name'], ABSURL . 'client/' . $this->Client->config['code']);
		}
		$this->Menu->addItem('Reports', ABSURL . 'client/' . $this->Client->config['code'] . '/report');
		if (!empty($this->Client->config['import_domain']) && !empty($this->Client->config['import_path'])) {
			if (is_admin()) {
				$this->Menu->addItem('Test', 'http://' . $this->Client->config['import_domain'] . '/' . $this->Client->config['import_path'], array('target' => '_blank'));
			}
		}
		if (valid_production_credentials($this->Client->config['api_url'], $this->Client->config['api_username'], $this->Client->config['api_password'])) {
			$api_url = 'https://' . $this->Client->config['api_url'] . '/' . $this->Client->config['prod_import_path'];
			$this->Menu->addItem('Production', $api_url, array('target' => '_blank'));
		}

		if (!empty($route) && $route === 'report') {
			// Set the client's reports menu item to active.
			$this->Menu->setActive('Reports');
		} else {
			// Set the client's menu item to active.
			$this->Menu->setActive($this->Client->config['name']);
		}
	}

	/**
	 * Loads the given client.
	 * @param string $client The client code.
	 * @return boolean Returns true when the client loads.
	 */
	protected function clientSet($client) {
		// Get client object.
		$client = $this->Clients->loadClient($client);
		if ($client !== false) {
			$this->Client =& $client;

			// When the client has custom routes, add them.
			if (!empty($this->Client->config['routes'])) {
				$routes = array(
					'client' => $this->Client->config['routes']
				);
				$this->Router->addRoutes($routes);
			}

			// Add the client's template directory.
			$this->View->addToPath($this->Client->getDirectory('template'));

			return true;
		} else {
			return false;
		}
	}

	public function clientTree() {
		$page_id = !empty($_GET['page_id']) ? $_GET['page_id'] : '';
		if (empty($page_id)) {
			$this->content = json_encode(array());
			return;
		}

		// Get the children.
		try {
			$children = $this->Client->buildMindTouchProductionTree((int) $page_id, 'fancytree', 'all');
		} catch (\Exception $e) {
			$children = array();
		}

		$this->content = json_encode($children);
	}

	/**
	 * Routes the controller to the function.
	 */
	public function route() {
		// Set the client.
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
		$this->clientMenu($this->Router->getCurrentRoute(2));

		// Get the function mapped to the route.
		$function = $this->Router->mappedFunction();
		if ($function === 'reports') {
			$ReportsController = new ReportsController($this);
			$ReportsController->route();
			$this->title = $ReportsController->title;
			$this->content = $ReportsController->content;
		} else if (method_exists($this->Client, $function)) {
			list($this->title, $this->content) = $this->Client->$function($this, $this->Router->getCurrentRoute());
		} else {
			$this->$function();
		}
	}
}
