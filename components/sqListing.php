<?php

abstract class sqListing {
	public static function text($text) {
		return $text;
	}
	
	public static function bool($value) {
		if ($value) {
			return 'Yes';
		} else {
			return 'No';
		}
	}
	
	public static function date($value) {
		return view::date(sq::config('list/date-format'), $value);
	}
	
	public static function image($value) {
		return '<img src="'.sq::base().$value.'"/>';
	}
	
	public static function blurb($value) {
		return view::blurb($value, 50);
	}
	
	public static function currency($price) {
		return '&#36;'.$price;
	}
}

?>