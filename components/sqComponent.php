<?php

/**
 * Component base class
 *
 * All types of interactions in sq derive from this base component class. This
 * class can be overridder by making a component class in your app. Controllers,
 * Modules, Models and Views are all components in sq. The class mainly deals 
 * with groundwork. It implements the iterator and countable interfaces so it 
 * can be looped like an array and has parameters for managing options and
 * a set() method for easy mass assignment.
 */

abstract class sqComponent implements iterator, countable, arrayaccess {
	
	// Array of component options
	public $options = array();
	
	// Optional layout view that is automatically used when the component is 
	// rendered. Variables can be added to it by the controller and when the 
	// controller is finished the layout is rendered.
	public $layout;
	
	// Array all model data is stored in
	protected $data = array();
	
	// Constructor to set options
	public function __construct($options = false) {
		$this->options = $options;
		
		// If a view is defined for layout generate it as a view
		if ($this->layout) {
			$this->layout = sq::view($this->layout);
			$this->layout->layout = true;
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
	 * the php count() function.
	 */
	public function count() {
		return count($this->data);
	}
	
	public function init() {
		// Method for object setup code. Called by component constructor.
	}
	
	public function cleanup() {
		// Method for object cleanup code. Called by component destructor.
	}
	
	// Mass asignment method for class data. All the usual security warnings
	// apply to mass asignment.
	public function set($data, $overwrite = true) {
		
		// Only overwrite data if it exists
		if ($overwrite) {
			$this->data = array();
		}
		
		if (is_object($data)) {
			$data = $data->data;
		}
		
		// Sets new properties
		if (is_array($data)) {
			foreach ($data as $key => $val) {
				$this->data[$key] = $val;
			}
		}
		
		return $this;
	}
	
	// Function to turn the component data into a view 
	public function render() {
		
	}
	
	// Returns component data as an array
	public function toArray($recursive = true) {
		$data = array();
		
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
				if (!is_numeric($key)){
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
			$itemClean = array();
			
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

?>