<?php

/**
 * Module component
 *
 * Modules extend this base class which gives basic bootstrap and autoloading
 * capabilities to the module. Modules are like mini apps with packaged views,
 * models, controllers, configuration and components. They can stand alone or
 * peices of them can be overridden and called outside the module.
 */

abstract class sqModule extends component {
	
	// The controller called by the module that can be rendered by the render
	// method
	private $controller;
	
	// Name is a bit of a hack to allow the load function to know the name of 
	// module
	private static $name;
	
	// Basic module setup
	public function __construct($options) {
		
		// Register the autoloader with PHP. The static name property is a hack
		// to allow the autoloader to know the name of the current module.
		self::$name = $options['name'];
		spl_autoload_register('module::load');
		
		// Load the module configuration defaults
		sq::load('/modules/'.self::$name.'/defaults/main');
		
		// Call component constructor
		parent::__construct($options);
		
		// Call the specified controller or the default if none is specified
		$controller = sq::request()->any('controller', $this->options['default-controller']);
		
		// Set controller to the module
		$this->controller = sq::controller($this->options['name'].'/'.$controller)
			->action(sq::request()->any('action'));
	}
	
	// Renders the current controller. Called via __tostring.
	public function render() {
		return $this->controller;
	}
	
	/**
	 * Module autoloader
	 *
	 * Add another autoloader that looks in the modules directories in addition
	 * to the base directories. To call module assets prefix the name with a 
	 * base path. Thus blog module views would be blog/posts. This is useful 
	 * because module views can be overridden in the base views directory.
	 */
	public static function load($class, $type = null) {
		$directories = [$type];
		if (!$type) {
			$directories = sq::config('autoload');
		}
		
		// Get the name of the module
		$module = self::$name;
		
		foreach ($directories as $dir) {
			$returned = false;
			
			// Look for application modules
			if (file_exists(sq::root().'modules/'.$module.'/'.$dir.'/'.$class.'.php')) {
				$returned = require_once(sq::root().'modules/'.$module.'/'.$dir.'/'.$class.'.php');
				
			// Look for framework modules
			} elseif (file_exists(sq::path().'modules/'.$module.'/'.$dir.'/'.$class.'.php')) {
				$returned = require_once(sq::path().'modules/'.$module.'/'.$dir.'/'.$class.'.php');
			}
			
			// Add any config and / or defaults to the application
			if (is_array($returned)) {
				sq::config($returned, true);
			}
		}
	}
}

?>