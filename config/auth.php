<?php

/**
 * Auth configuration
 */

$defaults = array(
	'auth' => array(
		
		// Default group of user if none is set. This is whay all unreconized 
		// visitors to your site will be
		'default-group' => 'visitor',
		
		// Use portable platform independant hashes
		'portable-hashes' => false,
		
		// Seconds until cookie experiation
		'cookie-timeout' => 60 * 60 * 10,
		
		// Salt added to the cookie hash
		'salt' => md5('What\'s up, Doc?'),
		
		// Enables cookie to be used for remembering users
		'remember-me' => true,
		
		// Field to use as username
		'username-field' => 'email'
	)
);

?>