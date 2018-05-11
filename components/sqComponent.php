<?php

/**
 * Component base class
 *
 * All types of interactions in sq derive from this base component class.
 * Controllers, modules, models and views are all components in sq. This class
 * mainly deals with groundwork and configuration.
 *
 * It implements the iterator and countable interfaces so it can be iterated
 * like an array and a set() method for mass assignment as well as methods for
 * exporting the component data as JSON, xml or a PHP array.
 */

abstract class sqComponent implements iterator, countable, arrayAccess {

	// Array of component options
	public $options = [];

	// Optional layout view that is automatically used when the component is
	// rendered. Variables can be added to it by the component and when the
	// component is echoed the layout is rendered.
	public $layout;

	// Array all model data is stored in
	protected $data = [];

	// Constructor to set options
	public function __construct($options) {
		$this->options = sq::merge($options, $this->options);

		// Layout can be defined in options as well as in the class
		if (isset($options['layout'])) {
			$this->layout = $options['layout'];
		}

		// If a view is defined for layout generate it as a view
		if ($this->layout) {
			$this->layout = sq::view($this->layout);
		}

		$this->init();
	}

	// Destructor for cleanup operations
	public function __destruct() {
		$this->cleanup();
	}

	// Call the component render function when it is echoed.
	public function __tostring() {
		return (string)$this->render();
	}

	// Getter and setter methods that add and remove properties from the data
	// array behind the scenes
	public function __set($name, $val) {
		$this->data[$name] = $val;
	}

	public function __get($name) {
		return $this->data[$name];
	}

	public function __unset($name) {
		unset($this->data[$name]);
	}

	public function __isset($name) {
		return isset($this->data[$name]);
	}

	/**
	 * Methods implementing the iterator interface
	 *
	 * Allows a model object to be used directly in a foreach loop. If the
	 * object is a list object with multple entries each ineration will be an
	 * object. If the model represents a single entry (limit is set) it will
	 * loop through the values of the object as key, value.
	 */
	public function rewind() {
		reset($this->data);
	}

	public function current() {
		return current($this->data);
	}

	public function key() {
		return key($this->data);
	}

	public function next() {
		return next($this->data);
	}

	public function valid() {
		$key = key($this->data);
		return ($key !== null && $key !== false);
	}

	/**
	 * Methods implementing arrayAccess interface
	 *
	 * This interface allows data in component objects to be treated as an
	 * array. This is useful in relations so that multiple items in a list can
	 * be accessed. Things like user->posts[0] can be used easily.
	 */
	public function offsetSet($offset, $val) {
		if (is_null($offset)) {
			$this->data[] = $val;
		} else {
			$this->data[$offset] = $val;
		}
	}

	public function offsetExists($offset) {
		return isset($this->data[$offset]);
	}

	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->data[$offset]) ? $this->data[$offset] : null;
	}

	/**
	 * Method implementing the countable interface
	 *
	 * This method and interface allow the data in the array to be counted with
	 * the PHP count() function.
	 */
	public function count() {
		return count($this->data);
	}

	// Object setup code. Called by component constructor.
	public function init() {}

	// Object cleanup code. Called by component destructor.
	public function cleanup() {}

	// Returns content to screen. Called by component __tostring.
	public function render() {}

	// Mass assignment method for class data. All the usual mass assignment
	// security warnings apply.
	public function set($data, $overwrite = true) {

		// Sets new properties
		if (is_array($data)) {
			foreach ($data as $key => $val) {

				// Only replace existing properties if overwrite is true
				if ($overwrite || !isset($this->data[$key])) {
					$this->data[$key] = $val;
				}
			}
		}

		// Allow two value assignment use. Example: set($key, $value)
		if (is_string($data)) {
			$this->data[$data] = $overwrite;
		}

		return $this;
	}

	// Returns component data as an array
	public function toArray($recursive = true) {
		$data = [];

		// Do some recursive shnanigians to get relations into a proper array
		foreach ($this->data as $key => $val) {
			if (is_object($val) && $recursive) {
				$data[$key] = $val->toArray();
			} else {
				$data[$key] = $val;
			}
		}

		return $data;
	}

	// Return component data as a JSON object
	public function toJSON() {

		// Pretty print JSON object for debuging
		if (sq::config('debug')) {
			return json_encode($this->toArray(), JSON_PRETTY_PRINT);
		}

		return json_encode($this->toArray());
	}

	// Return component data as XML
	public function toXML() {
		$element = new SimpleXMLElement("<?xml version=\"1.0\"?><data></data>");

		self::processXML($this->toArray(), $element);

		// Pretty print XML for debuging
		if (sq::config('debug')) {
			$dom = dom_import_simplexml($element)->ownerDocument;
			$dom->formatOutput = true;
			return $dom->saveXML();
		}

		return $element->asXML();
	}

	// Utility function to recursively create xml from array
	private static function processXML($array, &$xml) {
		foreach ($array as $key => $val) {
			if (is_array($val)) {
				if (!is_numeric($key)) {
					$node = $xml->addChild($key);
					self::processXML($val, $node);
				} else {
					$node = $xml->addChild('item');
					self::processXML($val, $node);
				}
			} else {
				$xml->$key = $val;
			}
		}
	}

	// Output to csv
	public function toCSV() {
		$out = '';

		foreach ($this->data as $item) {
			$itemClean = [];

			foreach ($item as $key => $val) {
				if (!is_array($val) && !is_object($val)) {
					$itemClean[$key] = $val;
				}
			}

			$out .= '"'.implode('","', $itemClean)."\"\r\n";
		}

		return $out;
	}
}
