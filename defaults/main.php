<?php

/*******************************************************************************
 * Defaults file for all configuration settings in the sq framework app. It may
 * be a good reference to view available properties, however, NEVER edit this
 * file directly instead override these settings in your sites configuration.
 *
 * This list isn't necessarily complete as components may have their own
 * configuration files that aren't loaded until the component is used.
 ******************************************************************************/

// Load configuration for other components
sq::load('/defaults/component');
sq::load('/defaults/view');
sq::load('/defaults/form');
sq::load('/defaults/model');
sq::load('/defaults/auth');

return [
	
	// Debug mode
	'debug' => false,
	
	// Parameter to manually set the root path of the app. If false PHP will
	// attempt to derive the path.
	'base' => false,
	
	// Controller to call if none is requested
	'default-controller' => 'site',
	
	// PHP timezone for date_default_timezone_set
	'timezone' => 'America/Phoenix',
	
	// Directories to look in for autoloading classes. The order determines what
	// directories are searched first. As soon as a class is found the script
	// stops looking.
	'autoload' => ['components', 'controllers', 'models', 'vendor'],
	
	// Enable logging of errors
	'log-errors' => true,
	
	// Friendly labels for PHP errors used for in the log instead of the useless
	// numbers
	'error-labels' => [
		1    => 'FATAL ERROR',
		2    => 'WARNING',
		4    => 'PARSE ERROR',
		8    => 'NOTICE',
		16   => 'CORE ERROR',
		32   => 'CORE WARNING',
		64   => 'COMPILE ERROR',
		128  => 'COMPILE WARNING',
		256  => 'USER ERROR',
		512  => 'USER WARNING',
		1024 => 'USER NOTICE',
		6143 => 'ALL',
		2048 => 'STRICT',
		4096 => 'RECOVERABLE ERROR'
	]
];

?>