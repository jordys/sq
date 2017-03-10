<?php

/**
 * Files model defaults
 */

return [
	'files' => [
		'type' => 'file',
		'path' => 'uploads',
		
		'fields' => [
			'list' => [
				'name' => 'text',
				'file' => 'text',
				'url' => 'link'
			],
			'form' => [
				'directory' => 'select|files/upload-directories',
				'image' => 'file'
			]
		],
		
		// Directories available to upload files to in admin module
		'upload-directories' => [
			'' => 'uploads'
		]
	)
);

?>