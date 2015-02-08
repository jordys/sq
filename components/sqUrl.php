<?php

/**
 * Url helper component
 *
 * Handles get, request and post values for urls as well as basic sanitation.
 */

abstract class sqUrl {
	
	/**
	 * Methods used to get url parameters in controllers
	 *
	 * Get request and post methods sanatize input and check for null values. 
	 * These methods return false if the parameter is not set. Calling get('id')
	 * is the same as calling $_GET['id'] just safer with better input 
	 * validation and error checking.
	 */
	public static function get($url = null, $clean = true) {
		return url::getParam($url, $clean, 'get');
	}
	
	public static function post($url = null, $clean = true) {
		return url::getParam($url, $clean, 'post');
	}
	
	public static function request($url = null, $clean = true) {
		return url::getParam($url, $clean, 'request');
	}
	
	// Implementation for the get() post() and request() methods. Calls 
	// cleanParam if data is set to be cleaned. Clean is on by default.
	private static function getParam($url, $clean, $type) {
		
		// Check for truthy urls
		if ($url) {
			$data = null;
			
			switch($type) {
				case 'get':
					$type = $_GET;
					break;
				case 'post':
					if (isset($_POST)) {
						$type = $_POST;
					} else {
						return false;
					}
					
					break;
				case 'request':
					$type = $_REQUEST;
					break;
			}
			
			// Check if the value is set, not null and not undefined. Checking 
			// for undefined makes a lot of javascript ajax requests a lot
			// easier because if a field is empty and submitted with ajax the 
			// post will contain undefined instead of null.
			if (isset($type[$url]) && $type[$url] != '' && $type[$url] != 'undefined') {
				$data = $type[$url];
				
				// Check if data should be cleaned and call cleanParam if needed
				if ($clean) {
					$data = self::clean($data);
				}
			}
			
			return $data;
		
		// If no url argument is set simply see of the request method matches 
		// and return true or false
		} elseif ($_SERVER['REQUEST_METHOD'] == strtoupper($type)) {
			return true;
		} else {
			return false;
		}
	}
	
	// Makes a url out of an array of components
	public static function make($fragments) {
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
	
	// Returns true if request is ajax
	public static function ajax() {
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
			&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
		) {
			return true;
		}
		
		return false;
	}
	
	// Gets a model passed as part of a form
	public static function model($name) {
		if (self::request('sq-model') && in_array($name, self::request('sq-model'))) {
			return sq::model($name)->set(self::request($name));
		}
	}
	
	// Recursivly checks navigates an array and applies strip tags and database
	// securing code
	public static function clean($data) {
		if (is_array($data)) {
			
			// If $data is array iterate over it and recursivly call the
			// function again
			foreach($data as $key => $val) {
				$data[$key] = self::clean($val);
			}
		} else {
			$data = strip_tags($data);
			$data = addslashes($data);
		}
		
		return $data;
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