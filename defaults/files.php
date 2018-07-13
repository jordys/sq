<?php

/**
 * Files model defaults
 */

return [
	'files' => [
		'type' => 'file',

		'actions' => ['upload', 'folder' => 'New Folder'],
		'inline-actions' => ['delete' => 'Delete'],
		'items-per-page' => 20,

		'fields' => [
			'list' => [
				'name' => 'text',
				'file' => 'text',
				'url' => 'link'
			]
		]
	],

	// SQ files is a database object that can overlay files in the system to
	// add additional metadata such as captions, alt text and ordering code.
	'extended_files' => [
		'type' => 'extendedFile',
		'read-content' => 'never',

		'actions' => ['upload'],
		'inline-actions' => ['delete' => 'Delete'],
	]
];
