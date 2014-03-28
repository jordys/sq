<?php

/*******************************************************************************
 * Defaults file for all config settings in the sq framework app. It can be a 
 * good reference to view all the available properties, however NEVER edit this 
 * file directly instead override these settings in your sites index.php file.
 *
 * This list isn't necessarily complete as modules can add config defaults and 
 * templates may use custom config options.
 ******************************************************************************/

sq::load('/defaults/view');
sq::load('/defaults/model');

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
	
	// Revision marker coded into the asset md5 urls. Can be any format that
	// interprets to a string. Changing the revision number changes the asset
	// urls hard breaking the browser cache.
	'asset-revision' => 0
);

?>