<?php

/**
 * Option component defaults
 */

return [
	'option' => [

		// Type of option. Default is text if the type isn't specified.
		'format' => 'text',

		// Default value of option
		'default' => null,

		// Default help text
		'help' => ''
	],

	// Options model config
	'sq_options' => [
        'title' => 'Options',
		'schema' => [
			'id'     => 'VARCHAR(100) NOT NULL',
			'title'  => 'VARCHAR(100) NOT NULL',
			'format' => 'VARCHAR(100) NOT NULL',
			'help'   => 'VARCHAR(255) NOT NULL',
			'value'  => 'VARCHAR(100)'
		],
		'actions' => [],
		'inline-actions' => [
			'update' => 'Edit'
		],
		'fields' => [
			'list' => [
				'name' => 'text',
				'type' => 'text'
			]
		]
	]
];
