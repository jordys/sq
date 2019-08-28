<?php

/**
 * Validator component
 *
 * Handles validation data against prebuilt rules. Used by the framework to
 * validate models.
 */

abstract class sqValidator extends component {

	// Public property storing the validitiy of the validator
	public $isValid = true;

	// Array of validation errors
	public $errors = [];

	// Constructs the validator object and checks the current data against the
	// passed in rules array
	public function __construct($data, $rules, $options) {
		$this->data = $data;

		// Handle rules as a config string
		if (is_string($rules)) {
			$rules = sq::config($rules);
		}

		parent::__construct($options);

		// Loop through the rules and fields and add the errors to the session
		foreach ($rules as $field => $rules) {
			if (is_string($rules)) {
				$rules = [$rules];
			}

			$value = null;
			if (isset($this->$field)) {
				$value = $this->$field;
			}

			foreach ($rules as $rule => $message) {
				if (is_numeric($rule)) {
					$rule = $message;

					$message = $this->options['messages']['generic'];
					if (isset($this->options['messages'][$rule])) {
						$message = $this->options['messages'][$rule];
					}
				}

				$processedValue = self::$rule($value);

				// If result isn't a simple boolean update the data with the
				// result of the validation
				if ($processedValue !== true) {
					$this->$field = $processedValue;
				}

				if ($processedValue === false) {
					$this->errors[$field][] = [
						'rule' => $rule,
						'message' => $this->message($message, $field, $value)
					];

					$this->isValid = false;
				}
			}
		}

		// Save errors to the session
		$_SESSION['sq-form-errors'] = $this->errors;
	}


	/***************************************************************************
	 * Data validation methods
	 *
	 * These methods are the logic for checking object data against the object
	 * rules. More can be created by extending this class.
	 **************************************************************************/

	// Data is not null
	public static function required($value) {
		if ((string)$value) {
			return true;
		}

		return false;
	}

	// Value is numeric
	public static function numeric($value) {
		return !$value || is_numeric($value);
	}

	// Value is an integer
	public static function integer($value) {
		return !$value || is_int($value);
	}

	// Value is a valid email address
	public static function email($value) {
		return !$value || filter_var($value, FILTER_VALIDATE_EMAIL);
	}

	// Value is a valid url
	public static function url($value) {
		return !$value || filter_var($value, FILTER_VALIDATE_URL);
	}

	// Marks a field as having a NULL state in the database and handles form
	// parsing so the correct type is used
	public static function nullable($value) {
		if (!$value) {
			return null;
		}

		return true;
	}

	// Validate that the date is valid and sanatizes it into SQL compatible
	// format
	public static function date($value) {
		if (!$value) {
			return true;
		}

		$value = new DateTime($value);
		return $value->format('Y-m-d');
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

		return strtr($message, [
			'{label}' => $label,
			'{value}' => $value
		]);
	}
}
