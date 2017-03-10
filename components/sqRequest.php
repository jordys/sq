<?php

/**
 * Request component
 *
 * Handles get, request and post values for urls and basic url sanitation.
 */

abstract class sqRequest extends component {
	public $options = [
		'cache' => true
	];
	
	// Public properties
	public $get, $post, $any, $isPut, $isHead, $isPost, $isGet, $context, $isAjax;
	
	// Set values to the various public properties
	public function __construct($options) {
		parent::__construct($options);
		
		$this->get = $_GET;
		$this->post = $_POST;
		$this->any = $_REQUEST;
		
		$this->isPut = $_SERVER['REQUEST_METHOD'] == 'PUT';
		$this->isHead = $_SERVER['REQUEST_METHOD'] == 'HEAD';
		$this->isPost = $_SERVER['REQUEST_METHOD'] == 'POST';
		$this->isGet = $_SERVER['REQUEST_METHOD'] == 'GET';
		
		$this->context = $this->any('sqContext');
		
		// Boolean marking if the request is an ajax request
		$this->isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
			&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	}
	
	/**
	 * Methods used to get url parameters
	 *
	 * These methods retrieve values from get, post and request globals and
	 * return null if the requested parameter is not set.
	 */
	public function get($param, $default = null) {
		return $this->param('get', $param, $default);
	}
	
	public function post($param, $default = null) {
		return $this->param('post', $param, $default);
	}
	
	public function any($param, $default = null) {
		return $this->param('any', $param, $default);
	}
	
	// Gets a model passed as part of a form
	public function model($name) {
		if ($this->post('sq-model') && in_array($name, $this->post('sq-model'))) {
			if ($name == 'form') {
				return sq::form();
			} else {
				return sq::model($name)->set($this->post($name));
			}
		}
	}
	
	// Implementation for the get() post() and request() methods above
	private function param($type, $param, $default) {
		if (isset($this->{$type}[$param])) {
			return $this->{$type}[$param];
		} else {
			return $default;
		}
	}
}

?>