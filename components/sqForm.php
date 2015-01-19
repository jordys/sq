<?php

/**
 * Form element helpers
 *
 * Simplifies the printing of generic form elements especially the more complex
 * ones with default values.
 */

abstract class sqForm {
	protected static $model, $i = 1;
	
	public static function open($attrs = array(), $attrs2 = array()) {
		if (is_object($attrs)) {
			self::$model = $attrs;
			$attrs = $attrs2;
		}
		
		if (is_string($attrs)) {
			$attrs = array(
				'action' => $attrs
			);
		}
		
		if (empty($attrs['method'])) {
			$attrs['method'] = 'post';
		}
		
		if (empty($attrs['action'])) {
			$attrs['action'] = null;
		}
		
		$form = '<form '.self::parseAttrs($attrs).'>';
		
		if (self::$model) {
			$form .= self::hidden('sq-model[]', self::$model->options['name']);
			$form .= self::hidden('id');
		}
		
		return $form;
	}
	
	public static function close() {
		self::$model = null;
		self::$i++;
		
		return '</form>';
	}
	
	// Prints form label
	public static function label($for, $value, $class = 'text') {
		return '<label class="'.$class.'" for="sq-form-'.self::$i.'-'.$for.'">'.$value.'</label>';
	}
	
	public static function element($name, $value = null, $attrs = array()) {
		if (empty($attrs['type'])) {
			$attrs['type'] = 'text';
		}
		
		$attrs = self::buildAttrs($name, $value, $attrs);
		
		return '<input '.$attrs.'/>';
	}
	
	// Basic text input
	public static function text($name, $value = null, $attrs = array()) {
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
		
		$attrs = self::parseAttrs($attrs);
		
		return '<textarea '.$attrs.'>'.htmlentities($value).'</textarea>';
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
	
	// Prints file upload button. If an image is set as value it is snown beside
	// the upload button.
	public static function file($name, $value = null, $attrs = array()) {
		$attrs['type'] = 'file';
		$attrs = self::buildAttrs('file', null, $attrs);
		
		$content = '<div class="field-block">';
		
		if ($value) {
			$content .= '
				<img class="file-image" src="'.sq::base().$value.'"/>
				<label class="replace-image" for="'.$attrs['id'].'">Replace image: </label>
			';
		}
		
		$content .= '
				<input '.$attrs.'/>
			</div>
		';
		
		return $content;
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
	public static function single($name, $value, $model, $attrs = array()) {
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
		
		return self::select($name, $value, $items, $attrs);
	}
	
	// Prints a checkbox. Optionally checked
	public static function checkbox($name, $checked = null, $attrs = array()) {
		if ($checked) {
			$attrs[] = 'checked';
		}
		
		$attrs['type'] = 'checkbox';
		$attrs = self::getAttrs($name, true, $attrs);
		
		$content = '<input type="hidden" name="'.$attrs['name'].'" value="0"/>';
		
		$attrs = self::parseAttrs($attrs);
		
		return $content.'<input '.$attrs.'/>';
	}
	
	// Prints a select box with an array of data
	public static function select($name, $default, $data, $attrs = array()) {
		$append = self::parseAttrs($attrs);
		
		if (!isset($attrs['id'])) {
			$append .= ' id="'.self::toId($name).'"';
		}
		
		if (is_string($data)) {
			$data = sq::config($data);
		}
		
		$content = '<select name="'.$name.'"'.$append.'>';
		foreach ($data as $value => $label) {
			
			$selected = null;
			if ($default == $value && $default !== false) {
				$selected = 'selected';
			}
			
			$content .= '<option '.$selected.' value="'.$value.'">'.$label.'</option>';
		}
		
		$content .= '</select>';
		
		return $content;
	}
	
	// Utility method to take a name parameter and convert it to a standard 
	// dashed id name
	private static function toId($string) {
		$string = preg_replace('/[^0-9a-zA-Z -]/', '', $string);
		$string = preg_replace('!\s+!', ' ', $string);
		$string = str_replace(' ', '-', $string);
		$string = strtolower($string);
		
		return $string;
	}
	
	private static function getAttrs($name, $value, $attrs) {
		if (is_array($value)) {
			$attrs = $value;
			$value = null;
		}
		
		$attrs['value'] = $value;
		$attrs['name'] = $name;
		
		if (empty($attrs['id'])) {
			$attrs['id'] = 'sq-form-'.self::$i.'-'.$name;
		}
		
		if (!$value && self::$model) {
			$attrs['value'] = self::$model->$name;
			$attrs['name'] = self::$model->options['name'].'['.$name.']';
		} elseif (isset($attrs['type']) && $attrs['type'] == 'date') {
			$attrs['value'] = view::date(sq::config('form/date-format'), $value);
		}
		
		return $attrs;
	}
	
	private static function buildAttrs($name, $value, $attrs) {
		return self::parseAttrs(self::getAttrs($name, $value, $attrs));
	}
	
	// Takes an array and turns them into html attributes or a string and
	// applies it as an id
	private static function parseAttrs($attrs) {
		$string = '';
		
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