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
	
	/**
	 * App wide static properties
	 *
	 * Data is an array of data for the entire framework used with the 
	 * self::get() and self::set() methods. Kind of like a global key/value 
	 * store. Config is the merged config.
	 */
	private static $data = array(), $error, $config;
	
	// Startup static function for the entire app. Handles setup tasks and 
	// starts the controller bootstrap.
	public static function init() {
		
		// Error handling function for the entire framework
		function sqErrorHandler($number, $string, $file, $line) {
			sq::error('500', array(
				'number' => $number,
				'string' => $string,
				'file'   => $file,
				'line'   => $line,
				'trace'  => debug_backtrace()
			));
		}
		
		set_error_handler('sqErrorHandler', -1);
		
		// Framework config defaults
		sq::load('/defaults/main');
		
		// Set the date timezone to avoid error on some systems
		date_default_timezone_set(self::config('timezone'));
		
		// Set up the autoload function to automatically include class files. 
		// The directories checked by the autoloader can be changed in the 
		// config autoload setting.
		spl_autoload_register('sq::load');
		
		// If module is url parameter exists call the module instead of the
		// controller.
		if (url::request('module')) {
			echo self::module(url::request('module'));
		} else {
			
			// Get controller parameter from the url. If no controller parameter
			// is set then we call the default-controller from config. If not 
			// changed this will call the site controller.
			$controller = url::request('controller');
			if (!$controller) {
				$controller = self::config('default-controller');
			}
			
			// Call the currently specified controller
			echo self::controller($controller);
		}
	}
	
	// get(), set() and remove() form a basic global key/value store for the 
	// entire application
	public static function get($value = false) {
		if ($value === false) {
			return self::$data;
		} elseif (isset(self::$data[$value])) {
			return self::$data[$value];
		} else {
			return false;
		}
	}
	
	public static function set($name, $value) {
		self::$data[$name] = $value;
	}
	
	public static function remove($name) {
		unset(self::$data[$name]);
	}
	
	// Adds error to the error array. Can be called anywhere in the app as 
	// self::error().
	public static function error($code, $details = array()) {
		$details['code'] = $code;
		
		self::$error = $details;
	}
	
	// Autoloader. Can be called directly. Checks for class files in the app
	// directories then in the framework directories. The paths checked are
	// specified in the autoload config option.
	public static function load($class, $type = false) {
		$directories = array($type);
		$direct = false;
		
		if ($type === false) {
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
			
			$returned = false;
			if (file_exists($path)) {
				$returned = require_once($path);
			} elseif (file_exists(self::path().'/'.$path)) {
				$returned = require_once(self::path().'/'.$path);
			}
			
			// Add any config and / or defaults to the application
			if (is_array($returned)) {
				self::config($returned);
			}
			
			if (isset($defaults)) {
				self::defaults($defaults);
			}
			
			if (isset($config)) {
				self::config($config);
			}
		}
	}
	
	// Combines the config passed into a method with the type and component 
	// configs.
	private static function configure($config, $type, $component = false) {		
		
		// If config is a string get the configuration with the config function
		if (is_string($config)) {
			$config = explode('/', $config);
			$name = end($config);
			
			// Load configuration
			sq::load('/defaults/'.$name);
			sq::load('/config/'.$name);
			
			if (isset($config[1])) {
				$options = self::merge(self::config($config[0]), self::config($config[1]));
			} else {
				$options = self::config($config[0]);
			}
		}
		
		// Get component config
		if ($component) {
			$component = self::config($component);
		}
		
		// Figure out how the type is defined. If it is in the options use that.
		// Otherwise use the default type.
		if ($type) {
			// Use type as is
		} elseif (isset($options['type'])) {
			
			// Use type from the options array
			$type = $options['type'];
		} elseif (isset($component['default-type'])) {
			
			// Get the default type from the component config
			$type = $component['default-type'];
		}
		
		if ($type) {
			
			// Merge the options with the type options
			$options = self::merge(self::config($type), $options);
		}
		
		// Merge the options with the component options
		if ($component) {
			$options = self::merge($component, $options);
		}
		
		// Set name and config if they don't exist in the options
		if (!isset($options['name'])) {
			$options['name'] = $name;
		}
		
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
	public static function component($config, $type = false) {		
		$config = self::configure($config, $type, 'component');
		$name = $config['name'];
		
		return new $name($config);
	}
	
	/**
	 * Returns a model object
	 *
	 * The type of model generated can be explicity passed in or specified in 
	 * the config. If no type is determined the framework default is used.
	 */
	public static function model($config, $type = false) {		
		$config = self::configure($config, $type, 'model');
		$type = $config['type'];
		
		return new $type($config);
	}
	
	/**
	 * Returns a view object
	 *
	 * Data my be initially set to the view using the data argument. Echoing the
	 * view causes it to render. Once the view is returned values can be added
	 * to it.
	 */
	public static function view($file, $data = array(), $full = null) {
		
		// If ajax is request assume no autorendered header and footer  sections
		// in view.
		if (url::ajax()) {
			$full = false;
		}
		
		return new view(self::config('view'), $file, $data, $full);
	}
	
	/**
	 * Runs and then returns a controller object
	 *
	 * Optionally a different action parameter may be included. If no action 
	 * argument is given then the global action parameter will be used.
	 */
	public static function controller($config, $action = null) {
		
		// If no action argument use the action parameter
		if ($action === null) {
			$action = url::request('action');
		}
		
		// Replace dashes and underscores in actions. Function calls are case 
		// insensative so there is no need to mess with that here.
		$action = str_replace('-', '', $action);
		$action = str_replace('_', '', $action);
		
		// Configure the controller
		$config = self::configure($config, false, 'component');
		$name = $config['name'];
		
		// Create the controller action
		$controller = new $name($config);
		
		// Don't execute controller if errors exist
		if (!self::$error) {
			
			// Call the default action if the action isn't defined as a method.
			// If no action is defined call the indexAction.
			if ($action) {
				if (method_exists($controller, $action.'Action')) {
					$controller->action($action);
				} else {
					$controller->action('default', strtolower(url::request('action')));
				}
			} else {
				$controller->action('index');
			}
		}
		
		// If errors exist the controller renders an error or debug screen and
		// ends execution
		if (self::$error) {
			if (self::config('debug')) {
				$controller->action('debug', self::$error);
			} else {
				$controller->action('error', self::$error);
			}
			
			echo $controller;
			die();
		}
		
		return $controller;
	}
	
	/**
	 * Runs and returns a module object
	 *
	 * Modules are like mini apps. They contain there own views, models and
	 * controllers.
	 */
	public static function module($config, $type = false) {
		$config = self::configure($config, $type, 'model');
		
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
	public static function config($name = false, $change = -1) {
		
		// Return the entire config array with no arguments
		if (!$name) {
			$config = self::$config;
		
		// If the first argument is an array add it to config
		} elseif (is_array($name)) {
			self::$config = self::merge(self::$config, $name);
			
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
					$config = false;
					break;
				}
			}
		}
		
		return $config;
	}
	
	// Adds default configuration options to the app
	public static function defaults($defaults) {
		self::$config = self::merge($defaults, self::$config);
	}
	
	// Redirect the page to another page
	public static function redirect($url, $code = '302') {
		if (!headers_sent()) {
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
	
	// Recursively merge the config and defaults arrays
	public static function merge($array1, $array2) {
		$merged = $array1;
		
		if (is_array($array2)) {
			foreach ($array2 as $key => $val) {
				if (is_array($array2[$key])) {
					if (isset($merged[$key]) && is_array($merged[$key])) {
						
						// Check is the array is associative. If it is the array
						// is overwritten not merged.
						if (array_keys($array2[$key]) === range(0, count($array2[$key]) - 1)) {
							$merged[$key] = $val;
						} else {
							$merged[$key] = self::merge($merged[$key], $array2[$key]);
						}
					} else {
						$merged[$key] = $val;
					}
				} else {
					$merged[$key] = $val;
				}
			}
		}
		
		return $merged;
	}
}

?>