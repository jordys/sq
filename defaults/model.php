<?php

/**
 * Model defaults
 */

return array(
	
	// General configuration for all models
	'model' => array(
		
		// Model objects aren't cached in framework by default
		'cache' => false,
		
		// Debug mode is used to print querries on the screen
		'debug' => false,
		
		// Default type for models. Can be overridden by setting ['type'] in the 
		// model config.
		'default-type' => 'sql',
		
		// Load relationships from config on read
		'load-relations' => true,
		
		// Relationships
		'belongs-to' => array(),
		'has-one' => array(),
		'has-many' => array(),
		'many-many' => array(),
		
		// When cascade is true when models are deleted their related models
		// will be deleted as well
		'cascade' => true,
		
		// Use inline view in admin
		'inline-view' => false,
		
		// Checks for form double submits and prevents them
		'prevent-duplicates' => true,
		
		// Tie model content to the currently authenticated user by the users_id
		// field
		'user-specific' => false,
		
		// Fields for admin module
		'fields' => array(
			'list' => array(),
			'form' => array()
		),
		
		// Validation rules. Either a string to another config object or an 
		// array with the various validation rules.
		'rules' => array(),
		
		// Number of items to show per page when paginating
		'items-per-page' => 10,
		
		// Model manipulation defaults
		'order' => false,
		'order-direction' => 'DESC',
		'limit' => false,
		'where' => array(),
		'where-raw' => false,
		'where-operation' => 'AND',
		
		// Number of pages of results to show
		'pages' => 1,
		
		// Set to false to disable model layout views
		'use-layout' => true,
		
		// Actions for admin modules
		'actions' => array('create' => 'Create'),
		'inline-actions' => array('delete' => 'Delete', 'update' => 'Update')
	),
	
	// MySQL database connection information
	'sql' => array(
		
		// PDO database credentials
		'dbtype' => 'mysql',
		'host' => 'localhost',
		'port' => 3306,
		'username' => 'root',
		'password' => 'root',
		'dbname' => 'database'
	),
	
	// File model type configuration
	'file' => array(
		
		// Base path of the file storage directory
		'path' => 'uploads/',
		
		// Read the content of files and add it to array in adition to metadata.
		// Useful for text files or JSON stores.
		'read-content' => false,
		
		// Dimensions of image to resize / crop to. False for none.
		'resize-x' => false,
		'resize-y' => false,
		
		// Amount of memory allowed to be used for the image transformation
		// process
		'memory-limit' => '16M',
		
		// Look in sub directories when searching
		'recursive' => false,
		
		// Defines variants of images
		'variations' => array()
	),
	
	// Files model
	'files' => array(
		'type' => 'file',
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
		'prevent-duplicates' => false
	),
	
	// Directories to upload files in to. Used by the files model.
	'upload-directories' => array(
		'' => 'uploads'
	)
);