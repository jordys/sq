<?php

/**
 * Files model defaults
 */

return array(
	'files' => array(
		'type' => 'file',
		'path' => 'uploads',
		
		'fields' => array(
			'list' => array(
				'name' => 'text',
				'file' => 'text',
				'url' => 'link'
			),
			'form' => array(
				'directory' => 'select|files/upload-directories',
				'image' => 'file'
			)
		),
		
		// Directories available to upload files to in admin module
		'upload-directories' => array(
			'' => 'uploads'
		)
	)
);

?>