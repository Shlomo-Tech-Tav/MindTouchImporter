<?php
abstract class ClientExtension {
	protected $Client;
	protected $Controller;
	protected $content = '';
	protected $Tools;
	protected $title = '';

	/**
	 * Instantiates the client extension controller.
	 * @param object $Controller
	 * @param object $Client
	 */
	public function __construct(&$Controller, &$Client) {
		$this->Client = $Client;
		$this->Controller = $Controller;
		$this->routes = $routes;
		$this->Tools = new ContentTools();
	}

	/**
	 * Returns object properties.
	 * @param string $name Property to return.
	 * @return mixed Property requested.
	 */
	public function __get($name) {
		if (!empty($this->$name)) {
			return $this->$name;
		}
		return '';
	}

	/**
	 * Executes the routed function.
	 * @param array $routes Array of current location.
	 */
	public function route($routes) {
		if (empty($routes[3])) {
			$routes[3] = '';
		}
		switch($routes[3]) {
			// Processes step 1.
			case 'process':
				$this->process();
				break;

			// Step 2. Displays results of analyzing the import.
			case 'results':
				$this->displayResults();
				break;

			// Step 1. Displays the import selection page.
			default:
				$this->displayIndex();
				break;
		}
	}
}
