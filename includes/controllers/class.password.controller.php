<?php
class PasswordController extends BaseController {
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
	 * Constructs the user controller class. Requires a parent constructor.
	 * @param object $Controller The main controller.
	 */
	public function __construct(&$Controller) {
		$this->Database = $Controller->Database;
		$this->Session = $Controller->Session;
		$this->Router = $Controller->Router;
		$this->View = $Controller->View;
		$this->Menu = $Controller->Menu;
		$this->Usage = $Controller->Usage;
		$this->Users = new Users($this->Database);
	}

	/**
	 * Displays the form to initiate the password reset process.
	 */
	protected function forgot() {
		nonce_generate();

		// Prepare data for contents.
		$data = array();

		$this->title = 'Forgot Password';
		$this->content = $this->View->getHtml('content-password-forgot', $data, $this->title);
	}

	/**
	 * Processes the user's email address, generates a reset token, and emails the user.
	 */
	protected function forgotProcess() {
		// Validate.
		$error_link = 'password/forgot';
		if (!$this->validateForm()) {
			$_SESSION['data'] = array(
				'link' => $error_link
			);
			redirect(ABSURL . 'error');
		}
		if (!DEBUG) {
			nonce_generate();
		}

		// Make sure user exists.
		if (empty($_POST['email'])) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'No email address was provided.',
				'link' => $error_link
			);
			redirect(ABSURL . 'error');
		}
		$this->user = $this->Users->get($_POST['email']);
		if (empty($this->user)) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The user was not found.',
				'link' => $error_link
			);
			redirect(ABSURL . 'error');
		}

		// Generate and save forgotten password token.
		$token = $this->Users->passwordTokenGenerate($this->user['user_id']);

		// Send email to the user.
		$subject = 'Password Reset for ' . PROJECT_NAME;
		$data = array(
			'first_name' => $this->user['first_name'],
			'last_name' => $this->user['last_name'],
			'email' => $this->user['email'],
			'token' => $token
		);
		$message = $this->View->getHtml('email-password-forgot', $data);
		send_email($subject, $message, $this->user['email'], $this->user['first_name'] . ' ' . $this->user['last_name']);

		// Forward user to results page.
		redirect(ABSURL . 'password/forgot-results');
	}

	/**
	 * Display message about the reset instructions email having been sent.
	 */
	protected function forgotResults() {
		nonce_generate();

		// Prepare data for contents.
		$data = array();

		$this->title = 'Forgot Password';
		$this->content = $this->View->getHtml('content-password-forgot-results', $data, $this->title);
	}

	/**
	 * Validates the provided email and token before presenting the reset form.
	 */
	protected function reset() {
		$error_link = 'password/forgot';
		$email = !empty($_GET['email']) ? $_GET['email'] : '';
		$token = !empty($_GET['token']) ? $_GET['token'] : '';

		// Make sure the supplied token is valid.
		$valid = $this->Users->passwordTokenValid($email, $token);
		if ($valid !== true) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				// 'error' => 'Invalid email address and token.',
				'error' => $valid,
				'link' => $error_link
			);
			redirect(ABSURL . 'error');
		}

		// Get the user.
		$this->user = $this->Users->get($email);

		// Prepare data for contents.
		nonce_generate();
		$data = array(
			'email' => $this->user['email'],
			'token' => $this->user['forgot_token'],
			'user_id' => $this->user['user_id']
		);

		// Show the form to reset the user's password.
		$this->title = 'Reset Password';
		$this->content = $this->View->getHtml('content-password-reset', $data, $this->title);
	}

	/**
	 * Resets the password before logging the user in.
	 */
	protected function resetProcess() {
		// Validate.
		$email = !empty($_POST['email']) ? $_POST['email'] : '';
		$token = !empty($_POST['token']) ? $_POST['token'] : '';
		$error_link = 'password/reset?email=' . $email . '&token=' . $token;
		if (!$this->validateForm()) {
			$_SESSION['data'] = array(
				'link' => $error_link
			);
			redirect(ABSURL . 'error');
		}
		if (!DEBUG) {
			nonce_generate();
		}

		// Make sure the supplied token is valid.
		$valid = $this->Users->passwordTokenValid($email, $token);
		if ($valid !== true) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				// 'error' => 'Invalid email address and token.',
				'error' => $valid,
				'link' => $error_link
			);
			redirect(ABSURL . 'error');
		}

		// Get the user.
		$this->user = $this->Users->get($email);

		// Compare the passwords to make sure they match.
		if (empty($_POST['password'])) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'A password must be entered.',
				'link' => $error_link
			);
			redirect(ABSURL . 'error');
		}
		if ($_POST['password'] !== $_POST['password_confirm']) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The passwords did not match.',
				'link' => $error_link
			);
			redirect(ABSURL . 'error');
		}

		// Update the user's password.
		$user_data = array(
			'password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
		);
		$this->Users->update($this->user['user_id'], $user_data);

		// Make sure user hasn't expired.
		if ($this->Users->isExpired($this->user['expires_on'])) {
			$_SESSION['title'] = 'Error';
			$_SESSION['data'] = array(
				'error' => 'The user no longer has access. <a href="' . ADMIN_EMAIL . '">Contact the administrator</a> for support.',
				'link' => $error_link
			);
			redirect(ABSURL . 'error');
		}

		// Log user in.
		session_array('loggedin', true);
		session_array('user_id', $this->user['user_id']);

		// Record log in.
		$this->Usage->save($this->user['user_id'], 0, 'login');
		$this->Users->updateLastAccessed($this->user['user_id']);

		// Forward to the main page.
		redirect(ABSURL . $redirect);
	}

	/**
	 * Routes the controller to the function.
	 */
	public function route() {
		// Get the function mapped to the route.
		$function = $this->Router->mappedFunction();
		$this->$function();
	}

}
