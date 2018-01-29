<?php
class Menu {
	protected $active;
	/**
	 * Instance of View class. Creates the content.
	 * @var object
	 */
	protected $View;
	protected $clients;
	protected $menu;
	protected $items = array();
	protected $finished = false;
	protected $routes = array();

	/**
	 * Instantiates the object and starts the menu HTML.
	 * @param array $routes Current route.
	 * @param array $clients Array of allowed clients.
	 */
	function __construct($routes, $clients) {
		$this->View = new View();
		$this->clients = $clients;
		$this->routes = $routes;
		$this->start();
	}

	/**
	 * Adds a single item to the items array.
	 * @param string $name Name of menu item. Serves as link text.
	 * @param string $url URL of link.
	 * @param array $options Additional options to add to item.
	 */
	public function addItem($name, $url, $options = array()) {
		if (isset($this->items[$name])
			&& is_array($this->items[$name])
		) {
			$this->items[$name]['url'] = $url;
		} else {
			$this->items[$name] = array(
				'url' => $url
			);
		}
		$this->items[$name] = array_merge($this->items[$name], $options);
	}

	/**
	 * Adds a bunch of items to the items array.
	 * @param array $items Array of items to add.
	 */
	public function addItems($items) {
		$this->items = array_merge($this->items, $items);
	}

	/**
	 * Builds the HTML menu from the items array.
	 */
	private function buildMenu() {
		$data = array(
			'active' => $this->active,
			'items' => $this->items,
			'routes' => $this->routes,
			'clients' => $this->clients
		);
		$this->menu = $this->View->getHtml('menu', $data);
	}

	/**
	 * Finishes and returns the menu HTML.
	 * @return string $menu Menu HTML.
	 */
	public function getMenu() {
		if (!$this->finished) {
			$this->finished = true;
			$this->end();
			$this->buildMenu();
		}
		return $this->menu;
	}

	/**
	 * Finishes the menu HTML.
	 */
	private function end() {
		if (is_logged_in()) {
			if (is_admin()) {
				$management = array(
					'Management' => array(
						'_url' => ABSURL . 'management',
						'Clients' => ABSURL . 'management/clients',
						'Users' => ABSURL . 'management/users',
						'navbar' => 'right',
						'glyphicon' => 'glyphicon-cog'
					)
				);
				$this->addItems($management);
			}
			$this->addItem('Log Out', ABSURL . 'log-out', array('navbar' => "right", 'glyphicon' => 'glyphicon-off'));
		}
	}

	/**
	 * Sets the menu item to be active.
	 * @param string $name Name of item to be set to active.
	 */
	public function setActive($name) {
		$this->active = $name;
	}

	/**
	 * Starts the menu HTML.
	 */
	private function start() {
		if (is_logged_in()) {
			$this->items['Client Selection'] = array();
			if (count($this->clients) > 0) {
				$this->items['Client Selection'] = array(
					'_url' => ABSURL
				);
				foreach ($this->clients as $client) {
					if ($client['archived'] != 1) {
						$this->items['Client Selection'][$client['name']] = ABSURL . 'client/' . $client['code'];
					}
				}
			} else {
				$this->items['Client Selection'] = ABSURL;
			}
		}
	}
}
