<?php

/*******************************************************************************
 * Defaults file for all config settings in the sq framework app. It can be a 
 * good reference to view all the available properties, however NEVER edit this 
 * file directly instead override these settings in your sites index.php file.
 *
 * This list isn't necessarily complete as modules can add config defaults and 
 * templates may use custom config options.
 ******************************************************************************/

sq::load('/defaults/component');
sq::load('/defaults/view');
sq::load('/defaults/form');
sq::load('/defaults/model');

return array(
	
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
	'autoload' => array('components', 'controllers', 'modules', 'models', 'lib', 'config', 'defaults'),
	
	// Revision marker coded into the asset md5 urls. Can be any format that
	// interprets to a string. Changing the revision number changes the asset
	// urls hard breaking the browser cache.
	'asset-revision' => 0,
	
	// Enable logging of errors
	'log-errors' => true,
	
	// Friendly labels for php errors used for in the log instead of the useless
	// numbers
	'error-labels' => array(
		1    => '## FATAL ERROR ##',
		2    => 'WARNING',
		4    => '## PARSE ERROR ##',
		8    => 'NOTICE',
		16   => '## CORE ERROR ##',
		32   => 'CORE WARNING',
		64   => 'COMPILE ERROR',
		128  => 'COMPILE WARNING',
		256  => '## USER ERROR ##',
		512  => 'USER WARNING',
		1024 => 'USER NOTICE',
		6143 => 'ALL',
		2048 => 'STRICT',
		4096 => '## RECOVERABLE ERROR ##'
	)
);

?>