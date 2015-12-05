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
			
			foreach ($rules as $rule => $message) {
				if (is_numeric($rule)) {
					$rule = $message;

					$message = $this->options['messages']['generic'];
					if (isset($this->options['messages'][$rule])) {
						$message = $this->options['messages'][$rule];
					}
				}
				
				if (self::$rule($this->$field) === false) {
					$this->errors[$field][] = array(
						'rule' => $rule,
						'message' => $this->message($message, $field, $this->$field)
					);
					
					$this->isValid = false;
				}
			}
		}
		
		// Save errors to the session
		$_SESSION['sq-form-errors'] = $this->errors;
	}
	
	// Data validation methods
	public static function required($value) {
		if ((string)$value) {
			return true;
		}
		
		return false;
	}
	
	public static function numeric($value) {
		return !$value || is_numeric($value);
	}
	
	public static function integer($value) {
		return !$value || is_int($value);
	}
	
	public static function email($value) {
		return !$value || filter_var($value, FILTER_VALIDATE_EMAIL);
	}
	
	public static function url($value) {
		return !$value || filter_var($value, FILTER_VALIDATE_URL);
	}
	
	// Utility function to generate user friendly error messages
	private function message($message, $name, $value) {
		if (preg_match('!\[([^\)]+)\]!', $name, $match)) {
			$name = array_pop($match);
		}
		
		$label = ucwords(str_replace('_', ' ', $name));
		if (isset($_SESSION['sq-form-labels'][$name])) {
			$label = $_SESSION['sq-form-labels'][$name];
		}
		
		return strtr($message, array(
			'[label]' => $label,
			'[value]' => $value
		));
	}
}

?>