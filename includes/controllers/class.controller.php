<?php
class Controller extends BaseController {
	/**
	 * Instantiates objects.
	 */
	public function __construct($routes, $whitelist) {
		parent::__construct($routes, $whitelist);
	}

	/**
	 * Default function that displays a list of clients to choose.
	 */
	protected function clientSelection() {
		// Prepare data for contents.
		nonce_generate();
		$data = array(
			'clients' =>& $this->user_clients
		);

		$this->title = PROJECT_NAME;
		$this->content = $this->View->getHtml('content-client-selection', $data, $this->title);
	}

	/**
	 * Processes the selection of which client to use.
	 */
	protected function clientProcess() {
		nonce_generate();

		// Make sure client exists.
		if (!$this->Clients->clientExists($_POST['client'])) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The selected client was invalid.',
				'link' => ''
			);
			redirect(ABSURL . 'error');
		}
		$client = $_POST['client'];

		// Forward to the client import start page.
		$_SESSION['client'] = $client;
		redirect(ABSURL . 'client/' . $client);
	}

	/**
	 * Logs the user in.
	 */
	protected function loggingIn() {
		// Validate.
		if (!$this->validateForm()) {
			redirect(ABSURL . 'error');
		}
		nonce_generate();

		// Deal with redirect.
		$redirect = '';
		if (!empty($_POST['redirect'])) {
			$redirect = $_POST['redirect'];
		}

		// Check login form.
		if (empty($_POST['username']) || empty($_POST['password'])) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The credentials were invalid.',
				'link' => $redirect
			);
			redirect(ABSURL . 'error');
		}

		// Check user.
		$users = new Users($this->Database);
		$user = $users->get($_POST['username']);
		if (empty($user)) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The credentials were invalid.',
				'link' => $redirect
			);
			redirect(ABSURL . 'error');
		}

		// Validate credentials.
		if (!password_verify($_POST['password'], $user['password'])) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The credentials were invalid.',
				'link' => $redirect
			);
			redirect(ABSURL . 'error');
		}

		// Make sure user hasn't expired.
		if ($users->isExpired($user['expires_on'])) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The user no longer has access. <a href="' . ADMIN_EMAIL . '">Contact the administrator</a> for support.',
				'link' => $redirect
			);
			redirect(ABSURL . 'error');
		}

		// Log user in.
		session_array('loggedin', true);
		session_array('user_id', $user['user_id']);

		// Record log in.
		$this->Usage->save(get_user_id(), 0, 'login');
		$users->updateLastAccessed($user['user_id']);

		redirect(ABSURL . $redirect);
	}

	/**
	 * Displays the log in form to anonymous users.
	 */
	protected function logIn() {
		nonce_generate();

		// Prepare data for contents.
		$redirect = str_replace(ABSURL, '', current_url());
		$data = array(
			'redirect' => $redirect
		);

		$this->title = PROJECT_NAME;
		$this->content = $this->View->getHtml('content-login', $data, $this->title);
	}

	/**
	 * Logs out the user.
	 */
	protected function logOut() {
		// Record log out.
		$this->Usage->save(get_user_id(), 0, 'logout');

		// Log user out.
		$_SESSION = array();
		redirect(ABSURL);
	}

	/**
	 * Figures out which function to process.
	 */
	public function route() {
		if (!$this->Router->isUserAllowed()) {
			// User is not allowed.
			if (is_logged_in()) {
				// Redirect logged-in users to main page.
				redirect(ABSURL);
			} else {
				// Redirect anonymous users to log-in page.
				$this->logIn();
			}
		} else {
			// User must be logged in or the route doesn't require being logged in.
			$controller = '';
			if ($this->Router->getCurrentRouteCount() >= 1) {
				// Get the controller.
				switch ($this->Router->getCurrentBaseRoute()) {
					case 'client':
						$controller = new ClientController($this);
					break;
					case 'management':
						$this->Menu->setActive('Management');
						switch ($this->Router->getCurrentRoute(1)) {
							case 'clients':
								$controller = new ClientsController($this);
							break;
							case 'users':
								$controller = new UsersController($this);
							break;
						}
					break;
					case 'password':
						$controller = new PasswordController($this);
					break;
					case 'reports';
						$controller = new ReportsController($this);
					break;
				}
			}
			if (is_object($controller)) {
				$controller->route();
				$this->title = $controller->title;
				$this->content = $controller->content;
			} else {
				// Set the user's menu to active.
				$this->Menu->setActive('Client Selection');

				// Get the function mapped to the route.
				$function = $this->Router->mappedFunction();
				$this->$function();
			}
		}
	}

}
