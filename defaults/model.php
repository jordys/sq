<?php

/**
 * Model defaults
 */

$defaults = array(
	
	// General configuration for all models
	'model' => array(
		
		// Default type for models. Can be overridden by setting ['type'] in the 
		// model config.
		'default-type' => 'sql',
		
		// Load relationships from config on read
		'load-relations' => true,
		
		// Relationships
		'belongs-to' => array(),
		'has-one' => array(),
		'has-many' => array(),
		
		// When cascade is true when models are deleted their related models
		// will be deleted as well.
		'cascade' => true,
		
		// Use inline view in admin
		'inline-view' => false,
		
		// When enabled relations ignore extra passed in where parameters
		'ignore-params' => false,
		
		// Checks for form double submits and prevents them
		'prevent-duplicates' => true,
		
		// Fields for admin module
		'fields' => array(
			'list' => array(),
			'form' => array()
		),
		
		// Actions for admin modules
		'actions' => array('create'),
		'inline-actions' => array('delete', 'update')
	),

	// MySQL database connection information
	'sql' => array(
		
		// PDO database credentials
		'dbtype' => 'mysql',
		'host' => 'localhost',
		'username' => 'root',
		'password' => 'root',
		'dbname' => 'database',
	),
	
	// File model type configuration
	'file' => array(
		
		// Base path of the file storage directory
		'path' => 'uploads/',
		
		// Read the content of files and add it to array in adition to metadata.
		// Useful for text files or json stores.
		'read-content' => false,
		
		// With of image to resize / crop to. False for none.
		'resize-x' => false,
		
		// Height of image to resize / crop to. False for none.
		'resize-y' => false,
		
		// Amount of memory allowed to be used for the image transformation
		// process.
		'memory-limit' => '16M'
	),
	
	// Files model
	'files' => array(
		'fields' => array(
			'list' => array(
				'path' => 'image',
				'name' => 'text'
			),
			'form' => array(
				'directory' => 'select|upload-directories',
				'image' => 'file'
			)
		),
		'name' => 'files',
		'type' => 'file',
		'prevent-duplicates' => false,
	),
	
	// Directories to upload files in to. Used by the files model.
	'upload-directories' => array(
		'' => 'uploads'
	)
);