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
		return view::date(sq::config('list/date-format'), $value);
	}
	
	// Creates image from URL
	public static function image($value) {
		return '<img src="'.sq::base().$value.'"/>';
	}
	
	// Truncates text in listing
	public static function blurb($value) {
		return view::blurb($value, 50);
	}
	
	// Returns value formatted as currency
	public static function currency($price) {
		return '&#36;'.$price;
	}
}

?>