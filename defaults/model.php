<?php

/**
 * Model component defaults
 */

return array(
	
	// General configuration for all models
	'model' => array(
		
		// Default type for models. Can be overridden by setting 'type' in the 
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
		
		// Set to false to disable model layout views
		'use-layout' => true,
		
		// Validation rules. Either a string to another config object or an 
		// array with the various validation rules.
		'rules' => array(),
		
		// Model manipulation defaults
		'order' => false,
		'order-direction' => 'DESC',
		'limit' => false,
		'where' => array(),
		'where-raw' => false,
		'where-operation' => 'AND',
		
		// Number of pages of results to show
		'pages' => 1,
		
		// Number of items to show per page when paginating
		'items-per-page' => 10,
		
		// Fields for admin module
		'fields' => array(
			'list' => array(),
			'form' => array()
		),
		
		// Actions for admin modules
		'actions' => array('create' => 'Create'),
		'inline-actions' => array('delete' => 'Delete', 'update' => 'Update')
	),
	
	// SQL model type configuration
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
		
		// Base directory where the files are stored
		'path' => 'files',
		
		// Amount of memory allowed to be used for the image transformation
		// process
		'memory-limit' => '16M',
		
		// Read content from files. Values are false to never read, 'single' to
		// online read for single file querries and 'always' to read content
		// when searching and reading a single file.
		'read-content' => 'single'
	),
	
	// Available image variant sizes. These variations can be called on file
	// model for images to resize them on the fly. The variant file format is
	// declarable as well. Formats can be gif, jpg or png. If not defined format
	// will match the base file format.
	'variants' => array(
		'small'  => array('w' => 150, 'h' => 150),
		'medium' => array('w' => 400, 'h' => 400),
		'large'  => array('w' => 800, 'h' => 800)
	)
);