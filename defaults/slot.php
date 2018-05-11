<?php

/**
 * Slot component defaults
 */

return [
	'slot' => [

		// Array of the names of content variable eg {variable} and the values
		// to replace them with. This allows slot content to be slightly dynamic
		// with some passed in values.
		'replacers' => [],

		// Type of slot. Default is markdown if type isn't specified.
		'type' => 'markdown',

		// Default content of slots
		'content' => ''
	],

	// Slots model config
	'sq_slots' => [
		'schema' => [
			'id'       => 'VARCHAR(100) NOT NULL',
			'name'     => 'VARCHAR(100) NOT NULL',
			'type'     => 'VARCHAR(100) NOT NULL',
			'alt_text' => 'VARCHAR(100) NOT NULL',
			'content'  => 'TEXT'
		],
		'actions' => [],
		'inline-actions' => ['update'],
		'fields' => [
			'list' => [
				'name' => 'text',
				'type' => 'text'
			]
		]
	]
];
