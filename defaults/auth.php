<?php

/**
 * Auth defaults
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
	),
	
	// Users model
	'users' => array(
		'name' => 'users',
		'fields' => array(
			'list' => array(
				'created' => 'date',
				'first' => 'text',
				'last' => 'text',
				'email' => 'text',
				'level' => 'text'
			),
			'form' => array(
				'first' => 'text',
				'last' => 'text',
				'email' => 'text',
				'level' => 'select|users/admin-types',
				'notes' => 'blurb'
			)
		),
		'inline-actions' => array('delete', 'password', 'update'),
		'admin-types' => array(
			'user' => 'User',
			'admin' => 'Admin'
		)
	)
);

?>