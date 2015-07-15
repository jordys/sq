<?php

/**
 * Route component
 *
 * Handles routing for sq application.
 */

abstract class sqRoute extends component {
	public $options = array(
		'cache' => true
	);
	
	// Makes a url out of an array of components
	public function to($fragments) {
		$url = '';
		
		if (!is_array($fragments)) {
			$fragments = func_get_args();
		}
		
		foreach ($fragments as $fragment) {
			if ($fragment) {
				$url .= '/'.$fragment;
			}
		}
		
		return sq::base().ltrim($url, '/');
	}
	
	// Converts a string into a clean url
	public static function format($url, $separator = '-') {
		$url = str_replace('&', 'and', $url);
		$url = preg_replace('/[^0-9a-zA-Z ]/', '', $url);
		$url = preg_replace('!\s+!', ' ', $url);
		$url = str_replace(' ', $separator, $url);
		$url = strtolower($url);
		
		return urlencode($url);
	}
}

?>