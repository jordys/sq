<?php

/**
 * Auth component defaults
 */

return array(
	'auth' => array(
		
		// Default group of user if none is set. This is whay all unreconized 
		// visitors to your site will be
		'default-group' => 'visitor',
		
		// Seconds until cookie experiation
		'cookie-timeout' => 60 * 60 * 24 * 14,
		
		// Enables cookie to be used for remembering users
		'remember-me' => true,
		
		// Cost of password hashing allowed on server
		'cost' => 10,
		
		// Algorithm used for password hashing
		'algorithm' => PASSWORD_DEFAULT,
		
		// Automatically rehash passwords using no security standards
		'rehash-passwords' => true,
		
		// Fields to use for authentication methods
		'username-field' => 'email',
		'password-field' => 'password',
		'hash-field'  => 'hash',
		
		// Message to be flashed when a login fails
		'login-failed-message' => 'Username or password not recognized.'
	)
);

?>