<?php

/**
 * Module base class
 *
 * Modules extend this base class which gives basic bootstrap and autoloading
 * capabilities to the module. Modules are like mini apps with packaged views,
 * models, controllers, config and components. They can stand alone or peices of
 * them can be overridden and called outside the module. 
 *
 * Like all the core components this class can be extended by adding a class 
 * named module to your app.
 */

abstract class sqModule extends component {
	
	// Name is a bit of a hack to allow the load function to know the name of 
	// module
	private static $name;
	
	// The controller called by the module that can be rendered by the render
	// method
	private $controller;
	
	// Basic module setup
	public function __construct($options) {
		
		// Current class
		$class = get_class($this);
		
		// Register the autoloader with php. The static name property is a hack
		// to allow the autoloader to know the name of the module. I couldn't
		// find a better way to do this although there probably is one.
		self::$name = $options['name'];
		spl_autoload_register($class.'::load');
		
		// Load the module configuration defaults
		self::load('main');
		
		// Call component constructor
		parent::__construct($options);
		
		// Call the specified controller or the default if none is specified
		$controller = url::request('controller');
		if (!$controller) {
			$controller = $this->options['default-controller'];
		}
		
		// Set controller to the module
		$this->controller = sq::controller($this->options['name'].'/'.$controller)
			->action(url::request('action'));
	}
	
	// Renders and returns the current controller. Also called via the 
	// __tostring method when the controller is echoed.
	public function render() {
		return $this->controller;
	}
	
	/**
	 * Module Autoloader
	 *
	 * Add another autoloader that looks in the modules directories in addition
	 * to the base directories. To call module assets prefix the name with a 
	 * base path. Thus blog module views would be blog/posts. This is useful 
	 * because module views can be overridden in the base views directory.
	 */
	public static function load($class, $type = null) {
		$directories = array($type);
		if (!$type) {
			$directories = sq::config('autoload');
		}
		
		// Get the name of the module
		$module = self::$name;
		
		foreach ($directories as $dir) {
			$returned = false;
			
			// Look in app modules
			if (file_exists(sq::root().'modules/'.$module.'/'.$dir.'/'.$class.'.php')) {
				$returned = require_once(sq::root().'modules/'.$module.'/'.$dir.'/'.$class.'.php');
				
			// Look in framework modules
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