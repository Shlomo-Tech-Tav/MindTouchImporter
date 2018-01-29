<?php
class UsersController extends BaseController {
	/**
	 * Instance of users class.
	 * @var object
	 */
	protected $Users;
	/**
	 * Current user.
	 * @var array
	 */
	protected $user;

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
		$this->UserClients = $Controller->UserClients;
		$this->Users = new Users($this->Database);
	}

	/**
	 * Displays the form to assign clients to a user.
	 */
	protected function clients() {
		// Get the clients the user is connected to.
		$user_clients = $this->UserClients->get($this->user['user_id']);

		// Prepare data for contents.
		nonce_generate();
		$data = array(
			'clients' =>& $this->Clients->clients,
			'user' =>& $this->user,
			'user_clients' =>& $user_clients
		);
		$this->title = 'Edit User Clients: ' . $this->user['username'];
		$this->content = $this->View->getHtml('content-users-clients-edit', $data, $this->title);
	}

	/**
	 * Updates the user's clients.
	 */
	protected function clientsProcess() {
		// Validate.
		if (!$this->validateForm()) {
			redirect(ABSURL . 'error');
		}
		nonce_generate();

		// Get user.
		$user = $this->Users->get((int) $_POST['user_id']);

		// Delete existing clients for the user.
		$delete = $this->UserClients->delete($user['user_id']);

		// Update with the current clients for the user.
		$update = $this->UserClients->update($user['user_id'], $_POST['clients']);

		// Forward to the users page.
		redirect(ABSURL . 'management/users/');
	}

	/**
	 * Create the user.
	 */
	protected function create() {
		$data = array();
		$this->title = 'Create User';
		$this->content = $this->View->getHtml('content-users-create', $data, $this->title);
	}

	/**
	 * Displays edit form for the user.
	 */
	protected function edit() {
		// Make sure a user exists.
		if (empty($this->user)) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The selected user was invalid.',
				'link' => 'management/users'
			);
			redirect(ABSURL . 'error');
		}

		// Prepare data for contents.
		nonce_generate();
		$data = array(
			'user' =>& $this->user
		);
		$this->title = 'Edit User: ' . $this->user['username'];
		$this->content = $this->View->getHtml('content-users-edit', $data, $this->title);
	}

	/**
	 * Updates or creates the user.
	 */
	protected function process() {
		// Build error link.
		if (empty($_POST['user_id'])) {
			$link = 'management/users/create';
		} else {
			$link = 'management/users/edit/' . $this->user['username'];
		}

		// Validate.
		if (!$this->validateForm()) {
			redirect(ABSURL . 'error');
		}
		nonce_generate();

		// Validate required fields.
		if (empty($_POST['username'])) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'A username must be entered.',
				'link' => $link
			);
			redirect(ABSURL . 'error');
		}
		if (empty($_POST['user_id']) && empty($_POST['password'])) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'A password must be entered.',
				'link' => $link
			);
			redirect(ABSURL . 'error');
		}
		if ($_POST['password'] !== $_POST['password_confirm']) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The passwords did not match.',
				'link' => $link
			);
			redirect(ABSURL . 'error');
		}
		if (empty($_POST['email'])) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'An email address must be entered.',
				'link' => $link
			);
			redirect(ABSURL . 'error');
		}

		// Build array of user information to update.
		$user = array(
			'username' => $_POST['username'],
			'email' => $_POST['email'],
			'first_name' => $_POST['first_name'],
			'last_name' => $_POST['last_name'],
			'expires_on' => $_POST['expires_on']
		);
		if (!empty($_POST['password'])) {
			$user['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
		}

		if (empty($_POST['user_id'])) {
			// Create the user.
			$this->Users->create($user);
		} else {
			// Update the user.
			$this->Users->update($_POST['user_id'], $user);
		}

		// Forward to the users page.
		redirect(ABSURL . 'management/users');
	}

	/**
	 * Routes the controller to the function.
	 */
	public function route() {
		// Set the user.
		$user = $this->Router->getCurrentRoute(3);
		if (!empty($user)) {
			if (!$this->userSet($user)) {
				$_SESSION['title'] = 'Error';
				$_SESSION['data'] = array(
					'error' => 'The selected user was invalid.',
					'link' => 'management/users'
				);
				redirect(ABSURL . 'error');
			}
		}

		// Get the function mapped to the route.
		$function = $this->Router->mappedFunction();
		$this->$function();
	}

	/**
	 * Validates a user ID before forwarding to the user edit page.
	 */
	protected function select() {
		// Validate.
		if (!$this->validateForm()) {
			redirect(ABSURL . 'error');
		}
		nonce_generate();

		// Get user.
		$user = $this->Users->get((int) $_POST['user_id']);

		// Send to error page when user doesn't exist.
		if (empty($user)) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The selected user was invalid.',
				'link' => 'management/users'
			);
			redirect(ABSURL . 'error');
		}

		// Forward to the user edit page.
		redirect(ABSURL . 'management/users/edit/' . $user['username']);
	}

	/**
	 * Default function that displays a list of users to edit.
	 */
	protected function users() {
		// Get users.
		$users = $this->Users->getAll();

		// Prepare data for contents.
		nonce_generate();
		$data = array(
			'users' =>& $users
		);

		$this->title = PROJECT_NAME;
		$this->content = $this->View->getHtml('content-users', $data, $this->title);
	}

	/**
	 * Loads the provided user as the current user.
	 * @param integer $user User ID, username, or user email to load.
	 * @return boolean True when the user exists.
	 */
	protected function userSet($user) {
		$user = $this->Users->get($user);
		if (empty($user)) {
			return false;
		} else {
			$this->user = $user;
			return true;
		}
	}
}
