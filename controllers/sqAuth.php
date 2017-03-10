<?php

/**
 * Auth controller / component
 *
 * Basic authorization controller. Handles actions like login and logout and has
 * helper methods authenticate(), login(), logout(), and hash() and user() to 
 * integrate into the web application. This component can also be used as a
 * controller and provides login and logout actions.
 */

abstract class sqAuth extends controller {
	public $options = array(
		'cache' => true
	);
	
	// The user object and their status are available as properties in the
	// component
	public $user, $level, $isLoggedIn = false;
	
	public function __construct($options) {
		parent::__construct($options);
		
		$this->user = sq::model('users')->limit();
		
		// Check session for login
		if (isset($_SESSION['sq-username'])) {
			$this->user->find(array('email' => $_SESSION['sq-username']));
			
		// If no session than check for a cookie if cookie login is enabled
		} elseif (isset($_COOKIE['sq-auth']) && $this->options['remember-me']) {
			$this->user->find(array('hash' => $_COOKIE['sq-auth']));
			
			// If a user is found log the user in again to increase the length
			// of the cookie
			if (isset($this->user->level)) {
				$this->login($this->user->email, $this->user->password, true);
			}
		}
		
		if (isset($this->user->level)) {
			$this->level = $this->user->level;
			$this->isLoggedIn = true;
		}
	}
	
	// Logs the user with the username and password supplied into the system and
	// sets a session. If the remember argument is true and the remember-me
	// option is true a cookie will be set as well.
	public function login($username, $password = false, $remember = false) {
		$user = sq::model('users')
			->find(array('email' => $username));
		
		// Guard against invalid login
		if ($password === false || !$user->count() || !self::authenticate($password, $user->password)) {
			sq::response()->flash($this->options['login-failed-message']);
			
			return false;
		}
		
		// Set the user info to the session
		$_SESSION['sq-username'] = $user->email;
		$_SESSION['sq-level'] = $user->level;
		
		// Check if the hash is outdated and update it if it is
		if ($this->options['rehash-passwords'] && password_needs_rehash($user->password, $this->options['algorithm'], array('cost' => $this->options['cost']))) {
			$user->password = self::hash($password);
			$user->save();
		}
		
		if ($remember && $this->options['remember-me']) {
			$timeout = time() + $this->options['cookie-timeout'];
			
			// A hash is saved to the user and into a cookie. If these two
			// parameters match the user will be allowed to log in.
			$hash = self::hash($user->email.$user->password);
			
			setcookie('sq-auth', $hash, $timeout, '/');
			
			$user->hash = $hash;
			$user->update();
		}
		
		return true;
	}
	
	// Logs the current user out of the system
	public function logout() {
		$this->user = null;
		$this->isLoggedIn = false;
		$this->level = null;
		
		unset($_SESSION['sq-level']);
		unset($_SESSION['sq-username']);
		
		// Clear the cookie
		setcookie('sq-auth', null, time() - 10, '/');
	}
	
	// Checks a password against a hashed password
	public static function authenticate($password, $hash) {
		return password_verify($password, $hash);
	}
	
	// Returns hashed string
	public static function hash($password) {
		return password_hash($password, sq::config('auth/algorithm'), array(
			'cost' => sq::config('auth/cost')
		));
	}
	
	// Checks login posted from form
	public function loginPostAction($username = null, $password = null, $remember = false) {
		if (!$username || !$password) {
			sq::response()->flash($this->options['login-failed-message']);
		}
		
		$this->login($username, $password, $remember);
		
		if (!sq::request()->isAjax) {
			sq::response()->redirect();
		}
	}
	
	// Logs out the currently logged in user
	public function logoutAction() {
		$this->logout();
		
		if (!sq::request()->isAjax) {
			sq::response()->redirect();
		}
	}
}

?>