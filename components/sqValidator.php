<?php

abstract class sqValidator extends component {
	public $rules;
	protected $errors = array();
	
	public function __construct($data, $rules, $options = array()) {
		if (is_object($data) && is_a($data, 'component')) {
			$data = $data->data;
		}
		$this->data = $data;
		
		if (is_string($rules)) {
			$rules = sq::config($rules);
		}
		$this->rules = $rules;
		
		parent::__construct($options);
		$this->options = sq::merge(sq::config('validator'), $this->options);
	}
	
	public function isValid() {
		$status = true;
		
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
					
					$status = false;
				}
			}
		}
		
		return $status;
	}
	
	public function errors() {
		return $this->errors;
	}
	
	public static function required($value) {
		if ((string)$value) {
			return $value;
		}
		
		return false;
	}
	
	public static function numeric($value) {
		if (!$value || is_numeric($value)) {
			return $value;
		}
		
		return false;
	}
	
	public static function integer($value) {
		if (!$value || is_int($value)) {
			return $value;
		}
		
		return false;
	}
	
	private function message($rule, $name) {
		if (preg_match('!\[([^\)]+)\]!', $name, $match)) {
			$name = array_pop($match);
		}
		
		if (!isset($_SESSION)) {
			session_start();
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