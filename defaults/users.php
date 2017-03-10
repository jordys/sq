<?php

/**
 * Users model defaults
 */

return array(
	'users' => array(
		'schema' => array(
			'id'       => 'INT(100) NOT NULL',
			'email'    => 'VARCHAR(100) NOT NULL',
			'first'    => 'VARCHAR(100) NOT NULL',
			'last'     => 'VARCHAR(100) NOT NULL',
			'level'    => 'ENUM("user", "admin") NOT NULL DEFAULT "admin"',
			'notes'    => 'TEXT NOT NULL',
			'hash'     => 'VARCHAR(255) NOT NULL',
			'password' => 'VARCHAR(255) NOT NULL',
			'created'  => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
		),
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