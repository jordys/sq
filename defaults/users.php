<?php

/**
 * Users model defaults
 */

return array(
	'users' => array(
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
		
		// Change password is a unique action to users so we override the inline
		// actions
		'inline-actions' => array(
			'delete' => 'Delete',
			'password' => 'Change Password',
			'update' => 'Edit'
		),
		
		// Types of users selectable in admin module
		'admin-types' => array(
			'user' => 'User',
			'admin' => 'Admin'
		)
	)
);

?>