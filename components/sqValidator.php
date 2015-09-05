<?php

/**
 * Validation component
 *
 * Handles validation data against prebuilt rules. Used by the framework to
 * validate models.
 */

abstract class sqValidator extends component {
	public $rules, $isValid = true, $errors = array();
	
	public function __construct($data, $rules, $options = array()) {
		if (is_object($data) && is_a($data, 'component')) {
			$data = $data->data;
		}
		$this->data = $data;
		
		// Handle rules as a config string
		if (is_string($rules)) {
			$rules = sq::config($rules);
		}
		$this->rules = $rules;
		
		parent::__construct($options);
		
		// Loop through the rules and fields and add the errors to the session
		foreach ($this->rules as $field => $rules) {
			if (is_string($rules)) {
				$rules = array($rules);
			}
			
			foreach ($rules as $rule) {
				if (self::$rule($this->$field) === false) {
					$this->errors[$field][] = array(
						'rule' => $rule,
						'message' => $this->message($rule, $field)
					);
					
					$this->isValid = false;
				}
			}
		}
		
		// Save errors to the session
		$_SESSION['sq-form-errors'] = $this->errors;
		
		return $this->isValid;
	}
	
	// Data validation methods
	public static function required($value) {
		if ((string)$value) {
			return true;
		}
		
		return false;
	}
	
	public static function numeric($value) {
		return (!$value || is_numeric($value));
	}
	
	public static function integer($value) {
		return (!$value || is_int($value));
	}
	
	// Utility function to generate user friendly error messages
	private function message($rule, $name) {
		if (preg_match('!\[([^\)]+)\]!', $name, $match)) {
			$name = array_pop($match);
		}
		
		if (isset($_SESSION['sq-form-labels'][$name])) {
			$label = $_SESSION['sq-form-labels'][$name];
		} else {
			$label = ucwords(str_replace('_', ' ', $name));
		}
		
		return str_replace('[label]', $label, $this->options['messages'][$rule]);
	}
}

?>