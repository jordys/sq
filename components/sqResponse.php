<?php

/**
 * Response component
 *
 * Handles setting headers and redirecting
 */

abstract class sqResponse extends component {
	public $options = array(
		'cache' => true
	);
	
	// Redirect to another page
	public function redirect($url = null, $code = 302) {
		if (!headers_sent() && !sq::error()) {
			if (!$url) {
				$url = $_SERVER['HTTP_REFERER'];
			}
			
			header('location:'.$url, true, $code);
			die();
		}
	}
}

?>