<?php

/**
 * sq framework core
 *
 * Provides the core framework and provides the global sq::<component> syntax
 * for initializing component objects.
 *
 * The application is bootstrapped by calling sq::init(). Once the application
 * is initialized the route component will call the correct controller and begin
 * the rendering process. The class also sets up error handling and autoloading.
 */

class sq {

	// $config holds the merged configuration of the application and error
	// stores the current framework error (404, PHP warning, etc...)
	private static $config, $error;

	// Store for components so they don't have to be realoaded from memory
	// unnecessarily
	private static $cache = [];

	/**
	 * Startup method for the framework
	 *
	 * Sets up error handling, autoloading, sessions and date / time defaults
	 * then triggers the routing component and calls a controller.
	 */
	public static function init() {

		// Define the framework's error handler
		set_error_handler(function($number, $string, $file, $line) {
			$trace = debug_backtrace();

			// Remove this function from the trace
			array_shift($trace);

			// Trigger the framework error method
			if (sq::config('debug') || $number == E_USER_ERROR) {
				sq::error('500', [
					'debug'  => 'A PHP error occured.',
					'string' => strip_tags($string),
					'file'   => $file,
					'line'   => $line,
					'trace'  => $trace
				]);
			}

			// Logging can be disabled
			if (sq::config('log-errors')) {
				error_log('PHP '.sq::config('error-labels/'.$number).':  '.$string.' in '.$file.' on line '.$line);
			}
		}, E_ALL & E_NOTICE & E_USER_NOTICE);

		// Require the autoloader for 3rd party code
		require 'vendor/autoload.php';

		// Framework configuration defaults
		self::load('/defaults/main');

		// Set the date timezone
		date_default_timezone_set(self::config('timezone'));

		// Start a session
		session_start();

		// Set up the autoloader for component files
		spl_autoload_register('sq::load');

		// Route urls
		sq::route()->start();

		// If module is url parameter exists render the module instead of the
		// controller
		if (sq::request()->any('module')) {
			echo self::module(sq::request()->any('module'));
		} else {

			// Get controller parameter from the url. If no controller parameter
			// is set then call the configured 'default-controller'.
			$controller = sq::request()->any('controller', self::config('default-controller'));

			// Call the controller component
			$controller = self::controller($controller);

			// Check for routing errors before calling the controller action
			if (!self::$error) {
				$controller->action(sq::request()->any('action'));
			}

			// Render the controller
			echo $controller;
		}
	}

	/**
	 * Getter / setter for framework configuration
	 *
	 * Returns the full config array with no arguments. With one string argument
	 * sq::config() returns a config parameter using slash notation like
	 * 'sql/dbname'. With one array argument the array is merged into the config
	 * array. When using two arguemnts the function sets a config value.
	 */
	public static function config($name = null, $change = -1) {

		// If method has no arguments return the entire config array
		if (!$name) {
			return self::$config;
		}

		// If the first argument is an array add it to config
		if (is_array($name)) {
			if ($change === true) {
				self::$config = self::merge($name, self::$config);
			} else {
				self::$config = self::merge(self::$config, $name);
			}

			return self::$config;
		}

		// Get or set the config parameter based on array path notation
		$name = explode('/', $name);

		// Sets the changed parameter to the config array by looping  backwards
		// over the name and creating nested arrays
		if ($change !== -1) {
			foreach (array_reverse($name) as $val) {
				$change = [$val => $change];
			}

			self::$config = self::merge(self::$config, $change);
		}

		// Find the requested parameter by looping through the name of the
		// requested parameter until it is found
		$config = self::$config;
		foreach ($name as $val) {
			if (isset($config[$val])) {
				$config = $config[$val];
			} else {
				return null;
			}
		}

		return $config;
	}

	/**
	 * Loads configuration, class and component files
	 *
	 * Includes configuration, class and component files into the application.
	 * sq::load is called by the autoloader but can also be called directly.
	 * If the class name contains a leading slash like a directory then the file
	 * will be loaded directly instead of searching for a class file.
	 *
	 * The method searches a list of directories specified in the 'autoload'
	 * configuration property or the directory specified in the type argument.
	 * Application code is searched first then module code then framework code
	 * so framework files may be overridden in the application.
	 *
	 * When loading components that are part of the sq framework, sq::load will,
	 * if necessary, create stub classes without the sq prefix that extend the
	 * framework component. This allows you to use the unprefixed versions
	 * throughout your application code even if the file hasn't been extended.
	 */
	public static function load($class, $type = null) {
		if (strpos($class, '\\')) {
			$class = explode('\\', $class);

			if ($class[0]) {
				$type = $class[0];
			}

			$class = array_pop($class);
		}

		$directories = [$type];
		$direct = false;

		if (!$type) {
			if ($class[0] == '/') {
				$directories = [substr($class, 1)];

				$direct = true;
			} elseif (self::config('autoload')) {
				$directories = self::config('autoload');
			} else {
				$directories = ['config'];
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

		if ($class && strpos($class, 'sq') !== 0 && !class_exists($class, false) && class_exists('sq'.ucfirst($class))) {
			eval("class $class extends sq$class {}");
		}
	}

	/**
	 * Gets or creates an error
	 *
	 * Code indicates the HTTP status code to return and details contains all
	 * the metadata about the error. This method can be called directly and is
	 * called when PHP errors occur. When called without arguments sq::error()
	 * returns the current framework error.
	 */
	public static function error($code = null, $details = []) {

		// No arguments gets the current error
		if (!$code) {
			return self::$error;
		}

		// String shorthand for details array
		if (is_string($details)) {
			$details = ['debug' => $details];
		}

		// Details can also be an exception object
		if ($details instanceof Exception) {
			$details = [
				'debug'  => 'A PHP exception occured.',
				'string' => $details->getMessage(),
				'file'   => $details->getFile(),
				'line'   => $details->getLine(),
				'trace'  => $details->getTrace()
			];
		}

		// Add the http status code to the details
		$details['code'] = $code;

		// Only set the error details if there isn't already an error. This
		// prevents errors in the error rendering from masking the initial
		// error.
		if (!self::$error) {
			self::$error = $details;
		}
	}

	/**
	 * Returns a component object
	 *
	 * Configures and returns the component object requested by name. For
	 * instance calling sq::component('mailer') returns the mailer component
	 * object fully configured.
	 *
	 * Any arguments extra arguments passed in will be relayed to the component.
	 * Using __callstatic this method is streamlined from sq::component('route')
	 * to sq::route().
	 */
	public static function component() {
		$args = func_get_args();
		$name = array_shift($args);

		// Check for cached component object
		if (isset(self::$cache[$name])) {
			return self::$cache[$name];
		}

		if (class_exists('components\\'.$name)) {
			$class = 'components\\'.$name;
		} elseif (class_exists($name) && is_subclass_of($name, 'component')) {
			$class = $name;
		}

		$reflection = new ReflectionClass($class);
		$paramCount = $reflection->getConstructor()->getNumberOfParameters();

		if ($paramCount > count($args)) {
			foreach ($reflection->getConstructor()->getParameters() as $key => $param) {
				if (!isset($args[$key]) && $param->isOptional()) {
					$args[$key] = $param->getDefaultValue();
				}
			}
		}

		$options = [];
		if (isset($args[$paramCount - 1])) {
			$options = array_pop($args);
		}

		$args[$paramCount - 1] = self::configure($name, $options);
		$component = $reflection->newInstanceArgs($args);

		// Force override with passed in options
		$component->options = self::merge($component->options, $options);

		// Cache the component for reuse if specified in the config
		if ($component->options['cache']) {
			self::$cache[$name] = $component;
		}

		return $component;
	}

	// Maps method calls to sq::component so calling sq::mailer($arg) is the
	// equivalent of calling sq::component('mailer', $arg)
	public static function __callStatic($name, $args = null) {
		array_unshift($args, $name);

		return forward_static_call_array(['sq', 'component'], $args);
	}

	/**
	 * Returns a model component
	 *
	 * The type of model generated can be explicity passed in or specified in
	 * the config. If no type is determined the framework default is used.
	 */
	public static function model($name, $options = []) {
		$config = self::configure($name, $options, 'model');

		// Check for namespaced model
		$class = $config['type'];
		if (class_exists('models\\'.$config['class'])) {
			$class = 'models\\'.$config['class'];
		} elseif (class_exists($config['class']) && is_subclass_of($config['class'], 'model')) {
			$class = $config['class'];
		}

		$model = new $class($config);

		// Force override with passed in options
		$model->options = self::merge($model->options, $options);

		return $model;
	}

	/**
	 * Returns a view component
	 *
	 * Data my be initially set to the view using the data argument. Echoing the
	 * view causes it to render. Once the view is returned values can be added
	 * to it.
	 */
	public static function view($file, $data = []) {
		return new view($file, $data, self::config('view'));
	}

	/**
	 * Runs and returns a controller component
	 *
	 * Optionally a different action argument may be included. If no action
	 * argument is given then the action parameter from the url will be used.
	 */
	public static function controller($name, $options = []) {
		$config = self::configure($name, $options);

		// Check for namespaced controller
		$class = 'controllers\\'.$config['class'];
		if (class_exists($class)) {
			$controller = new $class($config);
		} elseif (class_exists($config['class']) && is_subclass_of($config['class'], 'controller')) {
			$controller = new $config['class']($config);
		} else {

			// Throw an error for unfound controller
			self::error('404');

			// Return the default controller if none is found
			return self::controller(self::config('default-controller'), $options);
		}

		// Force override with passed in options
		$controller->options = self::merge($controller->options, $options);

		return $controller;
	}

	/**
	 * Runs and returns a module component
	 *
	 * Modules are like mini applications. They contain their own views, models
	 * and controllers.
	 */
	public static function module($name, $options = []) {
		$config = self::configure($name, $options, 'module');

		if (!isset($config['default-controller'])) {
			$config['default-controller'] = $config['name'];
		}

		$module = new module($config);

		// Force override with passed in options
		$module->options = self::merge($module->options, $options);

		return $module;
	}

	/**
	 * Creates a widget
	 *
	 * Widgets are wrappers for presentation functionality stored in the widgets
	 * directory.
	 */
	public static function widget($name, $params = [], $options = []) {
		$config = self::configure($name, $options);

		// Check for namespaced widget
		$class = $config['class'];
		if (class_exists('widgets\\'.$config['class'])) {
			$class = 'widgets\\'.$config['class'];
		}

		$widget = new $class($params, $config);

		// Force override with passed in options
		$widget->options = self::merge($widget->options, $options);

		return $widget;
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
		if (self::config('base')) {
			return self::config('base');
		}

		// If root path isn't configured then get it from PHP
		$base = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
		$base .= $_SERVER['HTTP_HOST'];

		if (dirname($_SERVER['PHP_SELF'])) {
			$base .= dirname($_SERVER['PHP_SELF']);
		}

		if (substr($base, -1) != '/') {
			$base .= '/';
		}

		return $base;
	}

	// Utility to recursively merge the config and defaults arrays. array1 is
	// overwritten by array2 where named keys match. Otherwise arrays are
	// merged.
	public static function merge($array1, $array2) {
		if (is_array($array2)) {
			foreach ($array2 as $key => $val) {

				// Merge sub arrays together only if the array exists in both
				// arrays and is every key is a string
				if (is_array($val)
					&& isset($array1[$key]) && is_array($array1[$key])
					&& array_unique(array_map('is_string', array_keys($val))) === [true]
				) {
					$val = self::merge($array1[$key], $val);
				}

				$array1[$key] = $val;
			}
		}

		return $array1;
	}

	// Combines the global, module, and passed in options for use in a component
	private static function configure($name, $options, $component = null) {

		// Explode pieces. Strings with a '/' are part of a module.
		$pieces = explode('/', $name);
		$name = end($pieces);

		// Load configuration files for the component
		self::load('/config/'.$name);
		self::load('/defaults/'.$name);

		// Merge direct config
		if (isset($pieces[1])) {
			$config = self::merge(self::config($pieces[0]), self::config($pieces[1]));
		} else {
			$config = self::config($pieces[0]);
		}

		// Get component config
		if ($component) {
			$config = self::merge(self::config($component), $config);
		}

		if (isset($config['default-type']) || isset($config['type'])) {
			if (!isset($config['type'])) {
				$config['type'] = $config['default-type'];
			}

			$config = self::merge($config, self::config($config['type']));
		}

		$config = self::merge(self::config('component'), $config);

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

		return $options;
	}
}
