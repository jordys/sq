<?php

/**
 * Form component
 *
 * Simplifies the printing of generic form elements especially the more complex
 * ones with default values. Also can be used as a model to get data in
 * controller actions.
 */

abstract class sqForm extends model {

	// Stores toptional model that may be passed into the form
	protected static $model;

	// Stores a unique string to avoid duplicating html ids
	protected static $mark;

	// Populates form model object from the post parameters in the URL
	public function __construct($options) {
		parent::__construct($options);

		$this->data = sq::request()->post;
		unset($this->data['sq-model']);
	}

	// Opens a new form
	public static function open($attrs = [], $attrs2 = []) {
		if (is_object($attrs)) {
			self::$model = $attrs;
			$attrs = $attrs2;
		}

		if (is_string($attrs)) {
			$attrs = ['action' => $attrs];
		}

		if (empty($attrs['method'])) {
			$attrs['method'] = 'post';
		}

		// Set a unique string for the model to avoid duplicating html ids
		self::$mark = uniqid();

		$form = '<form '.self::parseAttrs($attrs).'>';

		// Add model config option to the form
		if (self::$model && $attrs['method'] == 'post') {
			$form .= self::hidden('sq-model[]', self::$model->options['name']);

			// Add id so the model will will have the correct where statement
			$form .= self::hidden('id');
		} else {
			$form .= self::hidden('sq-model[]', 'form');
		}

		return $form;
	}

	// Close current form
	public static function close() {
		self::$model = null;

		// Clear errors after they are shown to the user
		unset($_SESSION['sq-form-errors']);
		unset($_SESSION['sq-form-data']);

		return '</form>';
	}

	// Prints form label
	public static function label($for, $value, $class = 'text') {
		if (self::inputError($for)) {
			$class .= ' sq-error sq-error-label';
		}

		// Set the labels of the fields to the session for later use by the
		// validator
		if (preg_match('!\[([^\)]+)\]!', $for, $match)) {
			$name = array_pop($match);
		} else {
			$name = $for;
		}

		$_SESSION['sq-form-labels'][$name] = $value;

		return '<label class="'.$class.'" for="'.self::parseId($for).'">'.$value.'</label>';
	}

	// General form input
	public static function element($name, $value = null, $attrs = []) {
		if (self::inputError($name)) {
			if (isset($attrs['class'])) {
				$attrs['class'] .= ' sq-error sq-error-field';
			}

			$attrs['class'] = 'sq-error sq-error-field';
		}

		return '<input '.self::buildAttrs($name, $attrs, $value).'/>'.self::inputError($name);
	}

	// Basic text input
	public static function text($name, $value = null, $attrs = []) {
		$attrs['type'] = 'text';

		return self::element($name, $value, $attrs);
	}

	// Date input fields with processing
	public static function date($name, $value = null, $attrs = []) {
		$attrs['type'] = 'date';

		if (empty($attrs['placeholder'])) {
			$attrs['placeholder'] = sq::config('form/date-placeholder');
		}

		return self::element($name, $value, $attrs);
	}

	// Password input
	public static function password($name, $value = null, $attrs = []) {
		$attrs['type'] = 'password';

		return self::element($name, $value, $attrs);
	}

	// Hidden filed
	public static function hidden($name, $value = null, $attrs = []) {
		$attrs['type'] = 'hidden';

		return self::element($name, $value, $attrs);
	}

	// Basic file input
	public static function file($name = 'upload', $value = null, $attrs = []) {
		$attrs['type'] = 'file';

		return self::element($name, $value, $attrs);
	}

	// Textarea
	public static function textarea($name, $value = null, $attrs = []) {
		$attrs = self::getAttrs($name, $attrs, $value);
		$value = $attrs['value'];
		unset($attrs['value']);

		return '<textarea '.self::parseAttrs($attrs).'>'.$value.'</textarea>'.self::inputError($name);
	}

	// Similar to textarea but with a richtext class presumably to use tinyMCE
	// or suchlike
	public static function richtext($name, $value = null, $attrs = []) {
		$attrs['class'] = 'sq-input-richtext';

		return self::textarea($name, $value, $attrs);
	}

	// Textarea with sq-input-blurb class
	public static function blurb($name, $value = null, $attrs = []) {
		$attrs['class'] = 'sq-input-blurb';

		return self::textarea($name, $value, $attrs);
	}

	// Money input
	public static function currency($name, $value = null, $attrs = []) {
		$attrs['class'] = 'sq-input-currency';

		if (empty($attrs['symbol'])) {
			$attrs['symbol'] = '$';
		}

		return htmlentities($attrs['symbol']).' '.self::element($name, $value, $attrs);
	}

	public static function files($name, $model, $value = null) {
		$model = sq::model($model);
		$value = self::getValue($name, $value);

		// @TODO Clean up this hack
		if (!$value) {
			echo '<span class="sq-help-text">Please pick a folder containing the images you want to use for your gallery.</span>';
			return form::picker($name, [
				'model' => 'files',
				'button-text' => 'Choose Folder',
				'open' => true,
				'where' => ['type' => 'dir']
			]);
		}

		echo '<span class="sq-help-text">Click and drag to reorder images. Images are stored in \''.sq::root().$value.'\'. Upload images there to add them to this gallery.</span>';

		$model->search([
			'path' => $value,
			'type' => 'file'
		]);

		$model->layout->view = 'forms/files';

		return $model;
	}

	// Desplays a related model inline as a form within the form
	public static function inline($name, $model, $value = null) {
		$model = sq::model($model);
		$value = self::getValue($name, $value);

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

	// File picker
	public static function picker($name, $options, $value = null, $attrs = []) {
		if (is_string($options)) {
			$options = ['model' => $options];
		}

		// @TODO Refactor this
		$attrs['class'] = 'sq-picker-input';

		$options['button-text'] = isset($options['button-text']) ? $options['button-text'] : 'Pick '.ucwords($options['model']);
		$options['fields'] = isset($options['fields']) ? $options['fields'] : ['id' => 'checkbox', 'name' => 'text'];

		$input = self::text($name, $value, $attrs);

		$data = '<div class="sq-picker '.(!empty($options['open']) ? 'is-open' : 'sq-picker-files').' '.(isset($options['class']) ? $options['class'] : '').'">';

		if (!isset($options['show-field']) || $options['show-field'] == true) {
			$data .= '
				'.self::text($name, $value, $attrs).'
				<button class="'.(empty($options['open']) ? 'sq-toggle is-closed' : 'sq-pick').'">'.$options['button-text'].'</button>
				<a href="clear" class="sq-picker-clear">Clear</a>';
		} else {
			$data .= self::hidden($name, $value, $attrs);
		}

		$model = sq::model($options['model'], [
			'picker' => true, 'inline-actions' => null]);

		if (isset($options['where'])) {
			$model->where($options['where']);
		}

		$model->order('type', 'ASC')->read();

		$model->options['fields']['list'] = $options['fields'];
		$model->options['inline-actions'] = [];

		return $data.$model.'</div>';
	}

	// Choose from a list of related entries
	public static function single($name, $model, $value = null, $attrs = []) {
		$model = sq::model($model);
		$model->options['load-relations'] = false;
		$model->read(['name', 'id']);

		$emptyLabel = '';
		if (isset($attrs['empty-label'])) {
			$emptyLabel = $attrs['empty-label'];
		}
		$items = ['' => $emptyLabel];

		foreach ($model as $item) {
			$items[$item->id] = $item->name;
		}

		return self::select($name, $items, $value, $attrs);
	}

	// Prints a checkbox. Optionally checked
	public static function checkbox($name, $value = null, $attrs = []) {
		$attrs['type'] = 'checkbox';
		$attrs = self::getAttrs($name, $attrs, $value);

		if ($attrs['value']) {
			$attrs[] = 'checked';
		}

		$attrs['value'] = 1;

		$content = '<input type="hidden" name="'.$attrs['name'].'" value="0"/>';

		return $content.'<input '.self::parseAttrs($attrs).'/>'.self::inputError($name);
	}

	// Prints a select box with an array of data
	public static function select($name, $data, $default = null, $attrs = []) {
		if (is_string($data)) {
			$data = sq::config($data);
		}

		// Default value if omitted will be replaced with attrs variable
		if (is_array($default)) {
			$attrs = $default;
		}

		$attrs = self::getAttrs($name, $attrs, $default);
		$default = $attrs['value'];
		unset($attrs['value']);

		$content = '<select '.self::parseAttrs($attrs).'>';
		foreach ($data as $value => $label) {
			$selected = null;
			if ($default && $default == $value) {
				$selected = 'selected';
			}

			$content .= '<option '.$selected.' value="'.$value.'">'.$label.'</option>';
		}

		return $content.'</select>'.self::inputError($name);
	}

	// Output flash message into the form with possible default message
	public static function flash($flash = null, $status = 'info') {
		if (isset($_SESSION['sq-form-flash'])) {
			$flash = $_SESSION['sq-form-flash'];
			unset($_SESSION['sq-form-flash']);
		}

		if (isset($_SESSION['sq-form-status'])) {
			$status = $_SESSION['sq-form-status'];
			unset($_SESSION['sq-form-status']);
		}

		if ($flash) {
			return sq::view('forms/flash', [
				'status' => $status,
				'flash' => $flash
			]);
		}
	}

	// Helper to print out error message below form input
	private static function inputError($name) {
		if (preg_match('!\[([^\)]+)\]!', $name, $match)) {
			$name = $match;
		}

		if (is_string($name) && isset($_SESSION['sq-form-errors'][$name])) {
			foreach ($_SESSION['sq-form-errors'][$name] as $error) {
				return '<span class="sq-error sq-error-message">'.$error['message'].'</span>';
			}
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
	private static function getAttrs($name, $attrs, $value = null) {
		if (is_array($value)) {
			$attrs = $value + $attrs;
			$value = null;
		}

		$attrs['value'] = self::getValue($name, $value);

		if (empty($attrs['id'])) {
			$attrs['id'] = self::parseId($name);
		}

		if (self::$model && $value === null) {
			$attrs['name'] = self::$model->options['name'].'['.$name.']';
		} else {
			$attrs['name'] = $name;
		}

		// Format dates nicely
		if (isset($attrs['type']) && $attrs['type'] == 'date') {
			$attrs['data-date'] = $attrs['value'];
			$attrs['value'] = view::date(sq::config('form/date-format'), $attrs['value']);
		}

		return $attrs;
	}

	// Gets the value from the form
	private static function getValue($name, $value) {
		$value = htmlentities($value);

		if (isset($_SESSION['sq-form-data'][$name])) {
			$value = $_SESSION['sq-form-data'][$name];
		} elseif (self::$model && isset($_SESSION['sq-form-data'][self::$model->options['name']][$name])) {
			$value = $_SESSION['sq-form-data'][self::$model->options['name']][$name];
		} elseif (!$value && self::$model && isset(self::$model->$name)) {
			$value = self::$model->$name;
		}

		return $value;
	}

	// Gets attrs and then parses them and returns the result
	private static function buildAttrs($name, $attrs, $value = null) {
		return self::parseAttrs(self::getAttrs($name, $attrs, $value));
	}

	// Takes an array and turns them html attributes
	private static function parseAttrs($attrs) {
		$string = '';

		if (is_string($attrs)) {
			$attrs = ['class' => $attrs];
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
