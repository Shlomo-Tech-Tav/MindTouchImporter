<?php
class Users {
	/**
	 * Instance of database class.
	 * @var object
	 */
	public $Database;
	/**
	 * Name of the users database table.
	 * @var string
	 */
	private $table = 'users';

	/**
	 * Constructs the Users class.
	 * @param object &$Database Database object.
	 */
	public function __construct(&$Database) {
		$this->Database = $Database;
	}

	/**
	 * Creates the user.
	 * @param array $user_data User data.
	 * @return boolean True on success.
	 */
	public function create($user_data) {
		$this->Database->insert($this->table, $user_data);
		return true;
	}

	/**
	 * Returns the user information for the provided user ID.
	 * @param integer $user User ID, username, or email.
	 * @return array $user User information.
	 */
	public function get($user) {
		// Determine whether $user is the ID, username, or email address.
		if (is_int($user)) {
			$where = array(
				'user_id' => $user
			);
		} elseif (strpos($user, '@')) {
			$where = array(
				'email' => $user
			);
		} else {
			$where = array(
				'username' => $user
			);
		}
		$user = $this->Database->get($this->table, '*', $where);
		return $user;
	}

	/**
	 * Get all of the users in the database.
	 * @return array $users All the users in the users table.
	 */
	public function getAll() {
		$users = $this->Database->select($this->table, '*');
		return $users;
	}

	/**
	 * Compares user's expired date to now to determine access.
	 * @param string $date From user's expires_on field. Format: YYYY-MM-DD.
	 * @return boolean True when expired.
	 */
	public function isExpired($date) {
		// When set to all zeros, the user never is expired.
		if ($date === '0000-00-00') {
			return false;
		}

		// Convert the date to a Unix timestamp and compare to today.
		$date = strtotime($date);
		$today = strtotime(date('Y-m-d'));
		if ($date > $today) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Generates and stores the forgotten password token for the given user.
	 * @param integer $user_id User ID.
	 * @return string Forgotten password token.
	 */
	public function passwordTokenGenerate($user_id) {
		// Generate the token.
		$token = base64_encode(openssl_random_pseudo_bytes(10, $strong));
		if ($strong === TRUE) {
			$token = substr($token, 0, 12);
		}

		// Save the token and its time to live.
		$user_data = array(
			'forgot_token' => $token,
			'forgot_token_datetime' => date('Y-m-d H:i:s', time() + PASSWORD_TOKEN_TTL)
		);
		$this->update($user_id, $user_data);

		return $token;
	}

	/**
	 * Validates the token for the given user's email.
	 * @param string $email User's email address.
	 * @param string $token Forgotten password token.
	 * @return mixed True on success. Error message on false.
	 */
	public function passwordTokenValid($email, $token) {
		// Make sure the user exists.
		$user = $this->get($email);
		if (empty($user)) {
			return 'The user was not found.';
		}

		// Make sure the supplied token matches.
		if ($token !== $user['forgot_token']) {
			return 'Invalid token.';
		}

		// Make sure the token hasn't expired.
		if (strtotime($user['forgot_token_datetime']) < time()) {
			return 'Expired token.';
		}

		return true;
	}

	/**
	 * Updates the user.
	 * @param integer $user_id The user's ID.
	 * @param array $user_data User data.
	 * @return boolean True on success.
	 */
	public function update($user_id, $user_data) {
		$this->Database->update($this->table, $user_data, array('user_id' => $user_id));
		return true;
	}

	/**
	 * Updates the database with the last time the user accessed their account.
	 * @param integer $user_id User ID.
	 * @return boolean True on success.
	 */
	public function updateLastAccessed($user_id) {
		$user_data = array(
			'last_accessed' => date('Y-m-d H:i:s')
		);
		$this->update($user_id, $user_data);
		return true;
	}

}
