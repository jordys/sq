<?php

/*******************************************************************************
 * Defaults file for all config settings in the sq framework app. It can be a 
 * good reference to view all the available properties, however NEVER edit this 
 * file directly instead override these settings in your sites index.php file.
 *
 * This list isn't necessarily complete as modules can add config defaults and 
 * templates may use custom config options.
 ******************************************************************************/

$defaults = array(
	
	// Debug mode
	'debug' => false,
	
	// Parameter to manually set the root path of the app. If false the app will
	// try to derive the path.
	'base' => false,
	
	// Default template if none is set
	'default-template' => 'page',
	
	// Controller to call if none is requested
	'default-controller' => 'site',
	
	// PHP timezone for date_default_timezone_set
	'timezone' => 'America/Phoenix',
	
	// Directories to look in for autoloading classes. The order determines what
	// directories are searched first. As soon as a class is found the script
	// stops looking.
	'autoload' => array('components', 'controllers', 'modules', 'models', 'lib', 'config'),
	
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
	
	// View configuration
	'view' => array(
		
		// Meta description string
		'meta-description' => null,
		
		// Array of meta keywords
		'meta-keywords' => array(),
		
		// Base tag path. False for no base tag.
		'base' => false,
		
		// Website default html title
		'title' => null,
		
		// Doctype used in template
		'doctype' => '<!DOCTYPE html>',
		
		// Lang attribute on html tag
		'language' => 'en',
		
		// Path to favicon
		'favicon' => 'favicon.ico'
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
	),
	
	// Users model
	'users' => array(
		'name' => 'users',
		'fields' => array(
			'list' => array(
				'created' => 'date',
				'first' => 'text',
				'last' => 'text',
				'email' => 'text',
				'level' => 'text'
			),
			'form' => array(
				'first' => 'text',
				'last' => 'text',
				'email' => 'text',
				'level' => 'select|users/admin-types',
				'notes' => 'blurb'
			)
		),
		'inline-actions' => array('delete', 'password', 'update'),
		'admin-types' => array(
			'user' => 'User',
			'admin' => 'Admin'
		)
	)
);

?>