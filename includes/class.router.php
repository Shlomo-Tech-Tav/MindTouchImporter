<?php
class Router {
	protected $count = 0;
	protected $current_route = array();
	protected $routes = array();
	protected $user_clients = array();
	protected $whitelist = array();

	/**
	 * Instantiates the router object.
	 * @param array $routes Definition of routes and corresponding functions.
	 * @param array $whitelist Base routes that don't require a logged in user.
	 */
	public function __construct($routes, $whitelist = array()) {
		$this->setCurrentRoute();
		$this->setRoutes($routes);
		$this->setWhitelist($whitelist);
	}

	/**
	 * Add routes to the default ones.
	 * @param array $routes Array of routes to add.
	 */
	public function addRoutes($routes) {
		$this->routes = array_merge_recursive($this->routes, $routes);
	}

	/**
	 * Returns the base route of the current URI.
	 * @return string
	 */
	public function getCurrentBaseRoute() {
		if (!empty($this->current_route[0])) {
			return $this->current_route[0];
		} else {
			return '';
		}
	}

	/**
	 * Returns the number of steps in the current route.
	 * @return integer $count
	 */
	public function getCurrentRouteCount() {
		return $this->count;
	}

	/**
	 * Returns the current route array.
	 * @param integer $index Index of route to return.
	 * @return array $current_route
	 */
	public function getCurrentRoute($index = '') {
		if ($index !== '') {
			if (!empty($this->current_route[$index])) {
				return $this->current_route[$index];
			} else {
				return '';
			}
		} else {
			return $this->current_route;
		}
	}

	/**
	 * Determines if the current user is allowed access to the
	 * current route.
	 * @return boolean
	 */
	public function isUserAllowed() {
		$base_route = $this->getCurrentBaseRoute();
		if (is_logged_in()) {
			// Check to see if the route is allowed.
			// Management is currently the only admin-only route.
			if ($base_route === 'management' && !is_admin()) {
				return false;
			}

			// Deny access to clients that aren't the user's.
			if ($base_route === 'client' && !in_array($this->getCurrentRoute(1), $this->user_clients)) {
				return false;
			}
			return true;
		} else {
			// The user is not logged in. See if the route is in the white list.
			if (in_array($base_route, $this->whitelist)) {
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Returns the function mapped to the current route.
	 * @return string $function Name of function to execute.
	 */
	public function mappedFunction() {
		$base_route = $this->getCurrentBaseRoute();
		if (array_key_exists($base_route, $this->routes)) {
			$function = $this->routes[$base_route];
			if (is_array($function)) {
				// Client and Reports are different. The action is from the 3rd route.
				if ($base_route === 'client' || $base_route === 'reports') {
					$action = $this->getCurrentRoute(2);

					// Check for actions that are arrays.
					if (isset($function[$action])
						&& is_array($function[$action])
					) {
						$function = $function[$action];
					}

					// Get the function for the route.
					if (array_key_exists($action, $function)) {
						$function = $function[$action];
					} else {
						$function = $function['default'];
					}
				} elseif ($base_route === 'management') {
					// Get the actions.
					$action = $this->getCurrentRoute(1);
					$sub_action = $this->getCurrentRoute(2);

					// Get the function for the route.
					if (array_key_exists($action, $function)) {
						$function = $function[$action];
					} else {
						$function = $function['default'];
					}

					// Deal with sub_action.
					if (!empty($sub_action)) {
						$function = $function[$sub_action];
					} else {
						$function = $function['default'];
					}
				} else {
					$action = $this->getCurrentRoute(1);
					if (array_key_exists($action, $function)) {
						$function = $function[$action];
					} else {
						$function = $function['default'];
					}
				}
			}
		} else {
			$function = $this->routes['default'];
		}
		return $function;
	}

	/**
	 * Parses the URI into route array.
	 * @return array $uri
	 */
	protected function setCurrentRoute() {
		$uri = $_SERVER['SCRIPT_URI'];
		$uri = str_replace(ABSURL, '', $uri);
		$this->current_route = explode('/', $uri);
		$this->count = count($this->current_route);
		return $this->current_route;
	}

	/**
	 * Sets the defined routes and functions.
	 * @param array $routes Array of routes and corresponding functions.
	 */
	protected function setRoutes($routes) {
		$this->routes = $routes;
	}

	/**
	 * Sets the user's allowed clients.
	 * @param array $user_clients Array of clients.
	 */
	public function setUserClients($user_clients) {
		// Store the client's code.
		$this->user_clients = array();
		foreach ($user_clients as $user_client) {
			$this->user_clients[] = $user_client['code'];
		}
	}

	/**
	 * Sets the list of base routes that don't require a logged
	 * in user.
	 * @param array $whitelist Array of base routes for everyone.
	 */
	protected function setWhitelist($whitelist) {
		$this->whitelist = $whitelist;
	}
}
