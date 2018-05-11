<?php

/**
 * Listing component
 *
 * Helpers to format different kinds of content as columns in a list view.
 */

abstract class sqListing {

	// Formats basic text
	public static function text($text) {
		return $text;
	}

	// Formats a value as yes / no
	public static function bool($value) {
		if ($value) {
			return 'Yes';
		}

		return 'No';
	}

	// Formats date string
	public static function date($value) {
		if (!$value) {
			return null;
		}

		return view::date(sq::config('list/date-format'), $value);
	}

	// Displays sort control
	public static function sort($value, $options) {
		return '<input name="save['.$options['field-id'].']['.$options['item-id'].']" type="text" autocomplete="off" inputmode="numeric" maxlength="3" value="'.$value.'"/>';
	}

	// Creates image from URL
	public static function image($value) {
		return '<img src="'.$value.'"/>';
	}

	// Truncates text in listing
	public static function blurb($value) {
		return view::truncate($value, 50);
	}

	// Returns value formatted as currency
	public static function currency($price) {
		return '&#36;'.$price;
	}

	// Returns value as a link
	public static function link($url) {
		return '<a href="'.$url.'">'.$url.'</a>';
	}

	// Checkbox data
	public static function checkbox($value) {
		return form::checkbox('checkbox['.$value.']', false);
	}

	// Displays model as a list of items
	public static function inline($model) {
		if ($model->count()) {
			$model->options['inline-view'] = true;
			return $model;
		}
	}
}
