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
	
	// Show a quick message to the user
	public function flash($flash, $status = 'info') {
		
		// Save flash to session for form to interpret
		$_SESSION['sq-form-status'] = $status;
		$_SESSION['sq-form-flash'] = $flash;
		
		return $this;
	}
	
	// Reviews last page showing errors
	public function review() {
		
		// Save existing form data to session so it can be redisplayed
		$_SESSION['sq-form-data'] = sq::request()->post;
		
		// Clear the labels session variable
		unset($_SESSION['sq-form-labels']);
		
		$this->redirect();
	}
	
	// Go back to previous page in a pristine state with no form data or error
	// messages
	public function reset() {
		unset($_SESSION['sq-form-errors']);
		unset($_SESSION['sq-form-data']);
		
		$this->redirect();
	}
}

?>