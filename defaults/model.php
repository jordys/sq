<?php

/**
 * Model component defaults
 */

return [

	// General configuration for all models
	'model' => [

		// Default type for models. Can be overridden by setting 'type' in the
		// model config.
		'default-type' => 'sql',

		// Sub types of the model. For example a pages model may have 'home',
		// 'hub' and 'standard' types. Null by default.
		'types' => null,

		// When true the model will be displayed as a hierarchy in the admin
		// module
		'hierarchy' => false,

		// Load relationships from config on read
		'load-relations' => true,

		// Relationships
		'belongs-to' => [],
		'has-one' => [],
		'has-many' => [],
		'many-many' => [],

		// Specifies the default type used when creating a new model record
		'default-item-type' => null,

		// Automatically create table based on schema in model config array
		'autogenerate-table' => true,

		// When cascade is true when models are deleted their related models
		// will be deleted as well
		'cascade' => true,

		// Views used for rendering model when rendered directly
		'list-view' => 'forms/list',
		'item-view' => 'forms/form',

		// Use inline view in admin
		'inline-view' => false,

		// Use picker view in admin
		'picker' => false,

		// Checks for form double submits and prevents them
		'prevent-duplicates' => true,

		// Tie model content to the currently authenticated user by the
		// users_id field
		'user-specific' => false,

		// Set to false to disable model layout views
		'use-layout' => true,

		// Validation rules. Either a string to another config object or an
		// array with the various validation rules.
		'rules' => [],

		// Model manipulation defaults
		'order' => false,
		'order-direction' => 'DESC',
		'limit' => false,
		'where' => [],
		'where-raw' => false,
		'where-operation' => 'AND',

		// Number of pages of results to show
		'pages' => 1,

		// Number of items to show per page when paginating
		'items-per-page' => 10,

		// Fields for admin module
		'fields' => [
			'list' => [],
			'form' => []
		],

		// Actions for admin modules
		'actions' => ['create' => 'New'],
		'inline-actions' => [],

		// Table schema array
		'schema' => null
	],

	// SQL model type configuration
	'sql' => [

		// PDO database credentials
		'dbtype' => 'mysql',
		'host' => 'localhost',
		'port' => 3306,
		'username' => 'root',
		'password' => 'root',
		'dbname' => 'database'
	],

	// File model type configuration
	'file' => [

		// File listings render in grid view by default
		'list-view' => 'forms/grid',

		// Base directory where the files are stored
		'path' => 'uploads',

		// Amount of memory allowed to be used for the image transformation
		// process
		'memory-limit' => '16M',

		// Read content from files. Values are false to never read, 'single' to
		// online read for single file querries and 'always' to read content
		// when searching and reading a single file.
		'read-content' => 'single'
	],

	// File model type configuration
	'extendedFile' => [
		'list-view' => 'forms/grid',
		'path' => 'uploads',
		'memory-limit' => '16M',
		'read-content' => 'single'
	],

	// Available image variant sizes. These variations can be called on file
	// model for images to resize them on the fly. The variant file format is
	// declarable as well. Formats can be gif, jpg or png. If not defined
	// format will match the base file format.
	'variants' => [
		'small'  => ['w' => 150, 'h' => 150],
		'medium' => ['w' => 400, 'h' => 400],
		'large'  => ['w' => 800, 'h' => 800]
	]
];
