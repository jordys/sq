<?php

/**
 * sq base static class
 *
 * The absolute first class called in the app. Handles calls to the controller 
 * and provides useful static variables and methods to the rest of the app such 
 * as config.
 *
 * Also manages overriding defaults with config values and a few other setup 
 * tasks.
 */

class sq {
	
	// Global static properties. $config is the merged configuration of the app
	// and error is the current application error (404, PHP warning, etc...).
	private static $config, $error;
	
	// Startup static function for the entire app. Handles setup tasks and 
	// starts the controller bootstrap.
	public static function init() {
		
		// Error handling function for the entire framework
		function sqErrorHandler($number, $string, $file, $line, $context) {
			$trace = debug_backtrace();
			
			// Remove this function from the trace
			array_shift($trace);
			
			if (sq::config('debug') || $number == E_USER_ERROR) {
				sq::error('500', array(
					'number'  => $number,
					'string'  => $string,
					'file'    => $file,
					'line'    => $line,
					'context' => $context,
					'trace'   => $trace
				));
			}
			
			// Logging can be disabled
			if (sq::config('log-errors')) {
				error_log('PHP '.sq::config('error-labels/'.$number).':  '.$string.' in '.$file.' on line '.$line);
			}
		}
		
		// Define framework custom error handler
		set_error_handler('sqErrorHandler');
		
		// Framework config defaults
		sq::load('/defaults/main');
		
		// Set the date timezone to avoid error on some systems
		date_default_timezone_set(self::config('timezone'));
		
		// Set up the autoload function to automatically include class files. 
		// Directories checked by the autoloader are set in the global config.
		spl_autoload_register('sq::load');
		
		// If module is url parameter exists call the module instead of the
		// controller.
		if (url::request('module')) {
			echo self::module(url::request('module'));
		} else {
			
			// Get controller parameter from the url. If no controller parameter
			// is set then we call the default-controller from config.
			$controller = url::request('controller');
			if (!$controller) {
				$controller = self::config('default-controller');
			}
			
			// Call the currently specified controller
			echo self::controller($controller)->action(url::request('action'));
		}
	}
	
	// Adds error to the error array. Can be called anywhere in the app as 
	// self::error().
	public static function error($code = null, $details = array()) {
		if ($code) {
			$details['code'] = $code;
			
			// Only set error if one doesn't already exist
			if (!self::$error) {
				self::$error = $details;
			}
		}
		
		return self::$error;
	}
	
	// Autoloader. Can be called directly. Checks for class files in the app
	// directories then in the framework directories. The paths checked are
	// specified in the autoload config option.
	public static function load($class, $type = null) {
		if (strpos($class, '\\')) {
			$class = explode('\\', $class);
			
			if ($class[0]) {
				$type = $class[0];
			}
			
			$class = array_pop($class);
		}
		
		$directories = array($type);
		$direct = false;
		
		if (!$type) {
			if ($class[0] == '/') {
				$directories = array(substr($class, 1));
				
				$direct = true;
			} elseif (self::config('autoload')) {
				$directories = self::config('autoload');
			} else {
				$directories = array('config');
			}
		}
		
		foreach ($directories as $dir) {
			if ($direct) {
				$path = $dir.'.php';
			} else {
				$path = $dir.'/'.$class.'.php';
			}
			
			$returned = null;
			if (file_exists($path)) {
				$returned = require_once($path);
			} elseif (file_exists(self::path().'/'.$path)) {
				$returned = require_once(self::path().'/'.$path);
			}
			
			// Add configuration to the application
			if (is_array($returned)) {
				self::$config = self::merge($returned, self::$config);
			}
		}
	}
	
	// Combines the global, module, and passed in options for use in a component
	private static function configure($name, $options, $component = null) {
		
		// Explode pieces. Strings with a '/' are part of a module.
		$pieces = explode('/', $name);
		$name = end($pieces);
		
		// Load configuration
		sq::load('/config/'.$name);
		sq::load('/defaults/'.$name);
		
		// Merge direct config
		if (isset($config[1])) {
			$config = self::merge(self::config($pieces[0]), self::config($pieces[1]));
		} else {
			$config = self::config($pieces[0]);
		}
		
		$type = false;
		
		// Get component config
		if ($component) {
			$component = self::config($component);
			$config = self::merge($component, $config);
			
			$type = $component['default-type'];
		}
		
		// Merge type options
		if (isset($config['type'])) {
			$type = $config['type'];
		}
		
		// Merge type options
		if ($type) {
			$config = self::merge(self::config($type), $config);
		}
		
		// Merge passed in options
		$options = self::merge($config, $options);
		
		// Set name to config if it doesn't exist
		if (!isset($options['name'])) {
			$options['name'] = $name;
		}
		
		// Set class to name if it doesn't exist
		if (!isset($options['class'])) {
			$options['class'] = $name;
		}
		
		// Set type to config if it doesn't exist
		if (!isset($options['type'])) {
			$options['type'] = $type;
		}
		
		return $options;
	}
	
	/**
	 * Returns a component object
	 *
	 * Configures and returns the component object requested by name. For 
	 * instance calling sq::component('mailer') returns the mailer component
	 * object fully configured.
	 */
	public static function component($name, $options = array()) {
		$config = self::configure($name, $options, 'component');
		
		if (class_exists($config['name']) && is_subclass_of($config['name'], 'component')) {
			return new $config['name']($config);
		}
	}
	
	/**
	 * Returns a model object
	 *
	 * The type of model generated can be explicity passed in or specified in 
	 * the config. If no type is determined the framework default is used.
	 */
	public static function model($name, $options = array()) {
		$config = self::configure($name, $options, 'model');
		
		$class = 'models\\'.$config['class'];
		
		if (class_exists($class)) {
			return new $class($config);
		} elseif (class_exists($config['class']) && is_subclass_of($config['class'], 'model')) {
			return new $config['class']($config);
		}
		
		return new $config['type']($config);
	}
	
	/**
	 * Returns a view object
	 *
	 * Data my be initially set to the view using the data argument. Echoing the
	 * view causes it to render. Once the view is returned values can be added
	 * to it.
	 */
	public static function view($file, $data = array()) {
		return new view(self::config('view'), $file, $data);
	}
	
	/**
	 * Runs and then returns a controller object
	 *
	 * Optionally a different action parameter may be included. If no action 
	 * argument is given then the global action parameter will be used.
	 */
	public static function controller($name, $options = array()) {
		$config = self::configure($name, $options, 'component');
		
		$class = 'controllers\\'.$config['class'];
		
		if (class_exists($class)) {
			return new $class($config);
		} elseif (class_exists($config['class']) && is_subclass_of($config['class'], 'controller')) {
			return new $config['class']($config);
		} else {
			
			// Throw an error for unfound controller
			self::error(404);
			
			// Return the default controller if none is found
			return self::controller(self::config('default-controller'), $options);
		}
	}
	
	/**
	 * Runs and returns a module object
	 *
	 * Modules are like mini apps. They contain there own views, models and
	 * controllers.
	 */
	public static function module($name, $options = array()) {
		$config = self::configure($name, $options, 'model');
		
		if (!isset($config['default-controller'])) {
			$config['default-controller'] = $config['name'];
		}
		
		return new module($config);
	}
	
	/**
	 * Getter / setter for framework configuration
	 *
	 * Returns the full config array with no arguments. With one argument
	 * config() returns a config parameter using slash notation "sql/dbname" 
	 * etc... Using two arguemnts the function sets a config value.
	 */
	public static function config($name = null, $change = -1) {
		
		// Return the entire config array with no arguments
		if (!$name) {
			$config = self::$config;
		
		// If the first argument is an array add it to config
		} elseif (is_array($name)) {
			if ($change === true) {
				self::$config = self::merge($name, self::$config);
			} else {
				self::$config = self::merge(self::$config, $name);
			}
			
			$config = self::$config;
			
		// Get or set the config parameter based on array path notation
		} else {
			$name = explode('/', $name);
			$config = self::$config;
			
			// Sets the changed parameter to the config array by looping 
			// backwards over the name and creating nested arrays
			if ($change !== -1) {
				$new = $change;
				$name = array_reverse($name);
				
				foreach ($name as $key => $val) {
					$new = array($val => $new);
				}
				
				self::$config = self::merge(self::$config, $new);
			}
			
			// Find the requested parameter by looping through the name of the
			// requested parameter until it is found
			foreach ($name as $key => $val) {
				if (isset($config[$val])) {
					$config = $config[$val];
				} else {
					return null;
				}
			}
		}
		
		return $config;
	}
	
	// Redirect to another page
	public static function redirect($url, $code = 302) {
		if (!headers_sent() && !self::$error) {
			header('location:'.$url);
			die();
		}
	}
	
	// Returns the framework path
	public static function path() {
		return dirname(__FILE__).'/';
	}
	
	// Returns the application path
	public static function root() {
		return dirname($_SERVER['SCRIPT_FILENAME']).'/';
	}
	
	// Returns the document root of the application
	public static function base() {
		$base = sq::config('base');
		
		// If no root path is set then determine from php
		if (!$base) {
			$base = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
			$base .= $_SERVER['HTTP_HOST'];
			
			if (dirname($_SERVER['PHP_SELF'])) {
				$base .= dirname($_SERVER['PHP_SELF']);
			}
			
			if (substr($base, -1) != '/') {
				$base .= '/';
			}
		}
		
		return $base;
	}
	
	// Recursively merge the config and defaults arrays. array1 will be
	// overwritten by array2 where named keys match. Otherwise arrays will be
	// merged.
	public static function merge($array1, $array2) {
		if (is_array($array2)) {
			foreach ($array2 as $key => $val) {
				
				// Merge sub arrays together only if the array exists in both
				// arrays and is every key is a string
				if (is_array($val) 
					&& isset($array1[$key]) && is_array($array1[$key])
					&& array_unique(array_map("is_string", array_keys($val))) === array(true)
				) {
					$val = self::merge($array1[$key], $val);
				}
				
				$array1[$key] = $val;
			}
		}
		
		return $array1;
	}
}

?>