<?php

/**
 * Controller base class
 * 
 * Contains method to deal with calling and filtering actions and a few 
 * default actions. Actions are methods ending in Action.
 */

abstract class sqController extends component {
	
	/**
	 * Call actions within controller
	 * 
	 * Calls the action passed in. A null argument will result in the index 
	 * action being called.
	 */
	public function action($action = null) {
		if (!$action) {
			$action = 'index';
		}
		
		$action = strtolower($action);
		
		// Action parameter passed into functions isn't stripped of dashes and
		// underscores
		$raw = $action;
		
		$action = str_replace('-', '', $action);
		$action = str_replace('_', '', $action);
		
		// Filter can return a view such as a login screen or true indicating
		// it's ok to proceed calling an action
		$data = $this->filter($raw);
		if ($data === true || $action == 'error' || $action == 'debug') {
			$args = array();
			
			// Call the action method or the default action
			if (method_exists($this, $action.$_SERVER['REQUEST_METHOD'].'Action')) {
				$method = $action.$_SERVER['REQUEST_METHOD'].'Action';
			} elseif (method_exists($this, $action.'Action')) {
				$method = $action.'Action';
			} elseif (method_exists($this, 'default'.$_SERVER['REQUEST_METHOD'].'Action')) {
				$method = 'default'.$_SERVER['REQUEST_METHOD'].'Action';
				$args[] = $raw;
			} else {
				$method = 'defaultAction';
				$args[] = $raw;
			}
			
			// Reflection allows http params to be injected as method arguments
			// to actions
			$reflection = new ReflectionMethod(get_called_class(), $method);
			foreach ($reflection->getParameters() as $param) {
				if ($model = url::model($param->getName())) {
					$args[] = $model;
				} elseif ($request = url::request($param->getName())) {
					$args[] = $request;
				} elseif ($param->isOptional()) {
					break;
				} else {
					sq::error('404', array(
						'debug' => "Query parameter &lsquo;{$param->getName()}&rsquo; required for $raw action."
					));
				}
			}
			
			$data = call_user_func_array(array($this, $method), $args);
		}
		
		// If something was returned set it as a new layout
		if ($data) {
			$this->layout = $data;
		}
		
		return $this;
	}
	
	// Renders the controller layout
	public function render() {
		if (sq::error()) {
			if (sq::config('debug')) {
				$data = $this->debugAction(sq::error());
			} else {
				$data = $this->errorAction(sq::error());
			}
			
			if ($data) {
				$this->layout = $data;
			}
		}
		
		if (is_object($this->layout) && !url::ajax()) {
			$this->layout->full = true;
		}
		
		if (is_object($this->layout)) {
			return $this->layout->render();
		}
		
		return $this->layout;
	}
	
	// Default filter action to be overridden in controller classes. Filter 
	// takes is passed the name of the action and returns true or false if it 
	// should be executed. By default it always returns true.
	public function filter($action) {
		return true;
	}
	
	// Default action is called when the specific action method doesn't exist.
	// The action argument is the name of the unfound action. This default
	// implementation calls renders the view in views/<controller>/<action>.
	public function defaultAction($action = 'index') {
		$class = get_called_class();
		
		// Check if the file exists. If it doesn't throw a 404 error
		if (file_exists(sq::root().'views/'.$class.'/'.$action.'.php')) {
			
			// If a layout exists use the view as content
			if (is_object($this->layout)) {
				$this->layout->content = sq::view($class.'/'.$action);
			} else {
				return sq::view($class.'/'.$action);
			}
		} else {
			sq::error('404');
		}
	}
	
	// Default error action that may be overridden in the controller. You can 
	// also just create your own 404.php in the views directory and the 
	// framework will use your view with this action.
	public function errorAction($error) {
		if (!headers_sent()) {
			header(':', true, $error['code']);
		}
		
		// If a layout exists use the view as content
		if (is_object($this->layout)) {
			$this->layout->content = sq::view('error', array(
				'error' => $error));
		} else {
			return sq::view('error', array('error' => $error));
		}
	}
	
	// If config['debug'] is true this method will be used instead of the error
	// action above. This method prints out a stack trace of php errors instead
	// of a generic save 404 page. Again the view can be overridden like above.
	public function debugAction($error) {
		if (!headers_sent()) {
			header(':', true, $error['code']);
		}
		
		if (is_object($this->layout)) {
			$this->layout->content = sq::view('debug', array(
				'error' => $error));
		} else {
			return sq::view('debug', array('error' => $error));
		}
	}
}

?>