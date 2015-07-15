<?php

/**
 * Auth controller
 *
 * Basic authorization controller. Handles actions like login and logout and has
 * helper methods check(), login(), logout(), hash() and user() to allow 
 * integration into a web application.
 *
 * The login system uses the phpass library (the same one used by wordpress) to
 * securly hash passwords. Cookie support as well as session based logins are
 * possible.
 */

abstract class sqAuth extends controller {
	private static $user;
	
	// Start session on object creation
	public function init() {
		self::session();
	}
	
	// Utility function to start session if it doesn't exist
	private static function session() {
		if (!isset($_SESSION)) {
			session_start();
		}
	}
	
	// Checks login posted from form
	public function loginPostAction($username, $password, $remember = false) {
		if ($username && $password) {
			$user = sq::model('users', array('load-relations' => false))
				->where(array(sq::config('auth/username-field') => $username))
				->limit()
				->read(array('id', 'password'));
			
			if (isset($user->password) && self::authenticate($password, $user->password)) {
				self::login($username, $remember);
			} else {
				form::error($this->options['login-failed-message']);
			}
		} else {
			form::error($this->options['login-failed-message']);
		}
		
		sq::redirect($_SERVER['HTTP_REFERER']);
	}
	
	// Log out action
	public function logoutAction() {
		self::logout();
		
		sq::redirect($_SERVER['HTTP_REFERER']);
	}
	
	// Checks a password against a hashed password
	public static function authenticate($password, $hash) {
		$hasher = new PasswordHash(8, sq::config('auth/portable-hashes'));
		return $hasher->checkPassword($password, $hash);
	}
	
	// Logs the user with the username passed in into the system and sets a 
	// session. If the remember argument is true and the remember-me option is 
	// true a cookie will be set as well.
	public static function login($username, $remember = false) {
		self::session();
		
		$user = sq::model('users', array('load-relations' => false))
			->where(array(sq::config('auth/username-field') => $username))
			->limit()
			->read();
		
		// Set the user info to the session
		$_SESSION['sq-username'] = $user->{sq::config('auth/username-field')};
		$_SESSION['sq-level'] = $user->level;
		
		if ($remember && sq::config('auth/remember-me')) {
			$timeout = time() + sq::config('auth/cookie-timeout');
			
			// A hashkey is saved to the user and into a cookie. If these two
			// parameters match the user will be allowed to log in.
			$hash = self::hash($user->{sq::config('auth/username-field')}.$user->password);
			
			setcookie('auth', $hash, $timeout, '/');
			
			$user->hashkey = $hash;
			$user->update();
		}
	}
	
	// Checks if the currently logged in users level. Returns true if user level
	// matches. If no argument is passed the function will return true for any
	// level.
	public static function check($level = true) {
		self::session();
		
		// Check session
		if (isset($_SESSION['sq-level']) && $level == $_SESSION['sq-level']) {
			return true;
		
		// If no session then check cookies if enabled
		} elseif (isset($_COOKIE['sq-auth']) && sq::config('auth/remember-me')) {
			$user = sq::model('users');
			$user->options['load-relations'] = false;
			$user->limit();
			$user->where(array('hashkey' => $_COOKIE['sq-auth']));
			$user->read('level');
			
			// If a cookie is found re-login to set a session and increase the
			// user cookie timeout
			if (isset($user->level) && $user->level == $level) {
				self::login($user->{sq::config('auth/username-field')}, true);
				
				return true;
			}
		}
		
		return false;
	}
	
	// Logs the current user out of the system
	public static function logout() {
		self::session();
		
		$_SESSION['sq-level'] = null;
		$_SESSION['sq-username'] = null;
		
		// Clear the cookie
		setcookie('auth', null, time() - 10, '/');
	}
	
	// Returns a model object of the currently logged in user with optional
	// recursion.
	public static function user($options = array('load-relations' => false)) {
		if (is_object(self::$user)) {
			return self::$user;
		}
		
		self::session();
		
		$user = sq::model('users', $options)
			->limit();
		
		// Check session for login
		if (isset($_SESSION['sq-username'])) {
			$user->where(array(sq::config('auth/username-field') => $_SESSION['sq-username']));
			$user->read();
			
		// If no session than check for a cookie if cookie login is enabled
		} elseif (isset($_COOKIE['sq-auth']) && sq::config('auth/remember-me')) {
			$user->where(array('hashkey' => $_COOKIE['sq-auth']));
			$user->read();
			
			// If a user is found log the user in again to increase the length
			// of the cookie
			if (isset($user->level)) {
				self::login($user->{sq::config('auth/username-field')}, true);
			}
		}
		
		self::$user = $user;
		
		return $user;
	}
		
	// Returns hashed password
	public static function hash($password) {
		$hasher = new PasswordHash(8, sq::config('auth/portable-hashes'));
		
		return $hasher->HashPassword($password);
	}
	
	// This function is a special action for the admin module that handles the
	// changing of passwords in the admin interface
	public function passwordGetAction($model, $id) {
		if (sq::config('admin/require-login') || !self::check('admin')) {
			return sq::view('admin/login');
		}
		
		$request = sq::request();
		
		$users = sq::model($model, array('load-relations' => false))
			->where($id)
			->read();
		
		return sq::view('admin/forms/password', array(
			'model' => $users
		));
	}
	
	public function passwordPostAction($password, $confirm, $model) {
		if ($password == $confirm) {
			$users->password = self::hash($password);
			$users->update();
			
			sq::redirect(sq::base().'admin/'.$model);
		}
	}
}

?>