<?php

/**
 * Form element helpers
 *
 * Simplifies the printing of generic form elements especially the more complex
 * ones with default values.
 */

abstract class sqForm {
	protected static $model, $mark, $status, $flash;
	
	public static function open($attrs = array(), $attrs2 = array()) {
		if (is_object($attrs)) {
			self::$model = $attrs;
			$attrs = $attrs2;
		}
		
		if (is_string($attrs)) {
			$attrs = array('action' => $attrs);
		}
		
		if (empty($attrs['method'])) {
			$attrs['method'] = 'post';
		}
		
		self::$mark = uniqid();
		
		$form = '<form '.self::parseAttrs($attrs).'>';
		
		if (self::$model) {
			$form .= self::hidden('sq-model[]', self::$model->options['name']);
			$form .= self::hidden('id');
		}
		
		return $form;
	}
	
	public static function close() {
		self::$model = null;
		
		return '</form>';
	}
	
	// Prints form label
	public static function label($for, $value, $class = 'text') {
		return '<label class="'.$class.'" for="'.self::parseId($for).'">'.$value.'</label>';
	}
	
	public static function element($name, $value = null, $attrs = array()) {
		return '<input '.self::buildAttrs($name, $value, $attrs).'/>';
	}
	
	// Basic text input
	public static function text($name, $value = null, $attrs = array()) {
		$attrs['type'] = 'text';
		
		return self::element($name, $value, $attrs);
	}
	
	// Date input fields with processing
	public static function date($name, $value = null, $attrs = array()) {
		$attrs['type'] = 'date';
		$attrs['placeholder'] = sq::config('form/date-placeholder');
		
		return self::element($name, $value, $attrs);
	}
	
	// Password input
	public static function password($name, $value = null, $attrs = array()) {
		$attrs['type'] = 'password';
		
		return self::element($name, $value, $attrs);
	}
		
	// Hidden filed
	public static function hidden($name, $value = null, $attrs = array()) {
		$attrs['type'] = 'hidden';
		
		return self::element($name, $value, $attrs);
	}
	
	// Money input
	public static function currency($name, $value = null, $attrs = array()) {
		$attrs['class'] = 'currency';
		
		return '&#36; '.self::element($name, $value, $attrs);
	}
	
	// Textarea
	public static function textarea($name, $value = null, $attrs = array()) {
		$attrs = self::getAttrs($name, $value, $attrs);
		
		$value = $attrs['value'];
		unset($attrs['value']);
		
		return '<textarea '.self::parseAttrs($attrs).'>'.htmlentities($value).'</textarea>';
	}
	
	// Similar to textarea but with a richtext class presumably to use tinyMCE
	// or suchlike
	public static function richtext($name, $value = null, $attrs = array()) {
		$attrs['class'] = 'richtext';
		
		return self::textarea($name, $value, $attrs);
	}
	
	// Textarea
	public static function blurb($name, $value = null, $attrs = array()) {
		$attrs['class'] = 'blurb';
		
		return self::textarea($name, $value, $attrs);
	}
	
	// Displays an image upload widget. If a value is set the image will be
	// shown beside the file input. Labels are included in the widget.
	public static function image($name = 'upload', $value = null, $attrs = array()) {
		$attrs['type'] = 'file';
		$attrs = self::getAttrs($name, null, $attrs);
		
		if ($value) {
			$content = '
				<div class="sq-replace-image">
					<span style="background-image: url('.sq::base().$value.')"></span>
					<img src="'.sq::base().$value.'"/>
				</div>
				<label class="sq-replace-label" for="'.$attrs['id'].'">Replace Image</label>
			';
		} else {
			$content = '<label class="sq-new-label" for="'.$attrs['id'].'">Upload Image</label>';
		}
		
		return $content.'<input '.self::parseAttrs($attrs).'/>';
	}
	
	// Basic file input
	public static function file($name = 'upload', $value = null, $attrs = array()) {
		$attrs['type'] = 'file';
		$attrs = self::buildAttrs($name, null, $attrs);
		
		return '<input '.self::parseAttrs($attrs).'/>';
	}
	
	// Desplays a related model inline as a form within the form
	public static function inline($name, $value, $model) {
		$model = sq::model($model);
		
		if ($value) {
			$model->where($value);
			$model->read();
		} else {
			$model->limit();
			$model->schema();
		}
		
		// Inline view slightly alters the way the model form parameters work
		$model->options['inline-view'] = true;
		
		return $model;
	}
	
	// Choose from a list of related entries
	public static function single($name, $model, $value = null, $attrs = array()) {
		$model = sq::model($model);
		$model->options['load-relations'] = false;
		$model->read(array('name', 'id'));
		
		$emptyLabel = '';
		if (isset($attrs['empty-label'])) {
			$emptyLabel = $attrs['empty-label'];
		}
		$items = array('' => $emptyLabel);
		
		foreach ($model as $item) {
			$items[$item->id] = $item->name;
		}
		
		return self::select($name, $items, $value, $attrs);
	}
	
	// Prints a checkbox. Optionally checked
	public static function checkbox($name, $value = null, $attrs = array()) {
		$attrs['type'] = 'checkbox';
		$attrs = self::getAttrs($name, $value, $attrs);
		
		if ($attrs['value']) {
			$attrs[] = 'checked';
		}
		
		$attrs['value'] = 1;
		
		$content = '<input type="hidden" name="'.$attrs['name'].'" value="0"/>';
		
		return $content.'<input '.self::parseAttrs($attrs).'/>';
	}
	
	// Prints a select box with an array of data
	public static function select($name, $data, $default = null, $attrs = array()) {
		if (is_string($data)) {
			$data = sq::config($data);
		}
		
		if (is_array($default)) {
			$attrs = $default;
		}
		
		$attrs = self::getAttrs($name, $default, $attrs);
		$default = $attrs['value'];
		unset($attrs['value']);
		
		$attrs = self::parseAttrs($attrs);
		
		$content = '<select '.$attrs.'>';
		foreach ($data as $value => $label) {
			$selected = null;
			if ($default && $default == $value) {
				$selected = 'selected';
			}
			
			$content .= '<option '.$selected.' value="'.$value.'">'.$label.'</option>';
		}
		
		return $content.'</select>';
	}
	
	public static function success($flash = 'Success') {
		self::status('success', $flash);
	}
	
	public static function error($flash = 'Failure') {
		self::status('error', $flash);
	}
	
	public static function status($status, $flash = null) {
		self::$status = $status;
		self::$flash = $flash;
		
		if (!url::ajax()) {
			if (!isset($_SESSION)) {
				session_start();
			}
			
			$_SESSION['sq-form-status'] = $status;
			$_SESSION['sq-form-flash'] = $flash;
		}
	}
	
	public static function review() {
		if (url::ajax()) {
			echo json_encode(array(
				'status' => self::$status,
				'flash' => self::$flash
			));
			
			die();
		}
		
		$url = $_SERVER['PHP_SELF'];
		if (url::get() && $_SERVER['QUERY_STRING']) {
			$url .= '?'.$_SERVER['QUERY_STRING'];
		}
		
		sq::redirect($_SERVER['REQUEST_URI']);
	}
	
	public static function flash($flash = null, $status = 'info') {
		if (!isset($_SESSION)) {
			session_start();
		}
		
		if (isset($_SESSION['sq-form-flash'])) {
			$flash = $_SESSION['sq-form-flash'];
			unset($_SESSION['sq-form-flash']);
		}
		
		if (isset($_SESSION['sq-form-status'])) {
			$status = $_SESSION['sq-form-status'];
			unset($_SESSION['sq-form-status']);
		}
		
		if ($flash) {
			return '<div class="sq-flash '.$status.'">'.$flash.'</div>';
		}
	}
	
	// Utility method to take a name parameter and convert it to a standard 
	// dashed id name
	private static function parseId($string) {
		$string = preg_replace('/[^a-zA-Z0-9]/', '-', $string);
		$string = strtolower(trim($string, '-'));
		
		if (self::$model) {
			$string = 'sq-form-'.self::$mark.'-'.$string;
		}
		
		return $string;
	}
	
	// Handles the processing of attributes for form elements. Sanitizes the 
	// value, name, id and other attributes and handles using a model set to the
	// input value if a model is specified.
	private static function getAttrs($name, $value, $attrs) {
		if (is_array($value)) {
			$attrs = $value + $attrs;
			$value = null;
		}
		
		$attrs['value'] = $value;
		
		if (empty($attrs['id'])) {
			$attrs['id'] = self::parseId($name);
		}
		
		if (!isset($attrs['name'])) {
			if (self::$model && !$value) {
				$attrs['name'] = self::$model->options['name'].'['.$name.']';
			} else {
				$attrs['name'] = $name;
			}
		}
		
		if (!$value && self::$model && isset(self::$model->$name)) {
			$attrs['value'] = self::$model->$name;
		}
		
		// Format dates nicely
		if (isset($attrs['type']) && $attrs['type'] == 'date') {
			$attrs['value'] = view::date(sq::config('form/date-format'), $attrs['value']);
		}
		
		return $attrs;
	}
	
	// Gets attrs and then parses them and returns the result
	private static function buildAttrs($name, $value, $attrs) {
		return self::parseAttrs(self::getAttrs($name, $value, $attrs));
	}
	
	// Takes an array and turns them html attributes
	private static function parseAttrs($attrs) {
		$string = '';
		
		if (is_string($attrs)) {
			$attrs = array('class' => $attrs);
		}
		
		foreach ($attrs as $key => $val) {
			if (is_int($key)) {
				$string .= ' '.$val;
			} else {
				$string .= ' '.$key.'="'.$val.'"';
			}
		}
		
		return $string;
	}
}

?>