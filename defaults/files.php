<?php

/**
 * Files model defaults
 */

return [
	'files' => [
		'type' => 'file',
		
		'actions' => ['upload'],
		'inline-actions' => ['delete'],
		
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