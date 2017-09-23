<?php

/**
 * Files model defaults
 */

return [
	'files' => [
		'type' => 'file',
		
		'actions' => ['upload', 'folder' => 'New Folder'],
		'inline-actions' => ['delete'],
		'items-per-page' => 20,
		
		'fields' => [
			'list' => [
				'name' => 'text',
				'file' => 'text',
				'url' => 'link'
			]
		]
	]
];

?>