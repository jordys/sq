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
		
		// For AJAX requests return a JSON object
		if (sq::request()->isAjax) {
			echo json_encode(array(
				'status' => $status,
				'flash' => $flash
			));
			
			die();
		} else {
			if (!isset($_SESSION)) {
				session_start();
			}
			
			// Save flash to session for form to interpret
			$_SESSION['sq-form-status'] = $status;
			$_SESSION['sq-form-flash'] = $flash;
			
			// Save existing form data to session so it can be redisplayed
			$_SESSION['sq-form-data'] = sq::request()->post;
			
			$this->redirect();
		}
	}
	
	// Reviews last page showing errors
	public function review() {
		if (!isset($_SESSION)) {
			session_start();
		}
		
		// Save existing form data to session so it can be redisplayed
		$_SESSION['sq-form-data'] = sq::request()->post;
		
		$this->redirect();
	}
}

?>