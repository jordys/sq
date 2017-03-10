<?php

/**
 * Users model defaults
 */

return [
	'users' => [
		'schema' => [
			'id'       => 'INT(100) NOT NULL',
			'email'    => 'VARCHAR(100) NOT NULL',
			'first'    => 'VARCHAR(100) NOT NULL',
			'last'     => 'VARCHAR(100) NOT NULL',
			'level'    => 'ENUM("user", "admin") NOT NULL DEFAULT "admin"',
			'notes'    => 'TEXT NOT NULL',
			'hash'     => 'VARCHAR(255) NOT NULL',
			'password' => 'VARCHAR(255) NOT NULL',
			'created'  => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
		],
		'fields' => [
			'list' => [
				'created' => 'date',
				'first' => 'text',
				'last' => 'text',
				'email' => 'text',
				'level' => 'text'
			],
			'form' => [
				'first' => 'text',
				'last' => 'text',
				'email' => 'text',
				'level' => 'select|users/admin-types',
				'notes' => 'blurb'
			]
		],
		
		// Change password is a unique action to users so we override the inline
		// actions
		'inline-actions' => [
			'delete' => 'Delete',
			'password' => 'Change Password',
			'update' => 'Edit'
		],
		
		// Types of users selectable in admin module
		'admin-types' => [
			'user' => 'User',
			'admin' => 'Admin'
		]
	]
];

?>