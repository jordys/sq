<?php

/**
 * Controller component base class
 * 
 * This class is extended to create controllers in the application that contain
 * actions triggered by different routes. This base class contains methods to
 * call different actions, as well as a few default actions to inherit.
 *
 * Action methods end in 'Action'. If no action is specified in the route then
 * the index action is used. If the requested action doesn't exist the request
 * default action will be used.
 *
 * The filter method can be extended to create access restrictions for the
 * controller. Controllers contain a layout property that is rendered when the
 * echoed to the screen.
 */

abstract class sqController extends component {
	
	/**
	 * Call an action within the controller
	 * 
	 * Calls the passed in action. A null argument will result in the index 
	 * action being called. Additional arguments cann be passed to the action as
	 * an array.
	 */
	public function action($action = null, $args = array()) {
		if (!$action) {
			$action = 'index';
		}
		
		$action = strtolower($action);
		
		// Strip action parameter of dashes and underscores
		$method = str_replace('-', '', $action);
		$method = str_replace('_', '', $method);
		
		// Filter can return a view such as a login screen or true indicating
		// it's ok to proceed calling an action
		$data = $this->filter($action);
		
		if ($data === true) {
			
			// Call the action method or the default action
			if (method_exists($this, $method.$_SERVER['REQUEST_METHOD'].'Action')) {
				$method = $method.$_SERVER['REQUEST_METHOD'].'Action';
			} elseif (method_exists($this, $method.'Action')) {
				$method = $method.'Action';
			} elseif (method_exists($this, 'default'.$_SERVER['REQUEST_METHOD'].'Action')) {
				$method = 'default'.$_SERVER['REQUEST_METHOD'].'Action';
			} else {
				$method = 'defaultAction';
			}
			
			// Reflection allows http parameterss to be injected as method
			// arguments to actions
			$reflection = new ReflectionMethod(get_called_class(), $method);
			foreach ($reflection->getParameters() as $param) {
				if ($model = sq::request()->model($param->getName())) {
					$args[] = $model;
				} elseif ($request = sq::request()->any($param->getName())) {
					$args[] = $request;
				} elseif ($param->isOptional()) {
					$args[] = $param->getDefaultValue();
				} else {
					sq::error('404', array(
						'debug' => "Query parameter &lsquo;{$param->getName()}&rsquo; required for $action action."
					));
				}
			}
			
			$data = $reflection->invokeArgs($this, $args);
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
				$this->action('debug', array(sq::error()));
			} else {
				$this->action('error', array(sq::error()));
			}
		}
		
		if (is_object($this->layout) && !sq::request()->isAjax) {
			$this->layout->full = true;
		}
		
		if (is_object($this->layout)) {
			return $this->layout->render();
		}
		
		return $this->layout;
	}
	
	// Default filter action to be overridden in controller classes. Filter 
	// accepts the name of the action and returns true or false if it should be
	// executed. By default there are no access restrictions.
	public function filter($action) {
		return true;
	}
	
	// Default action is called when the specified action method doesn't exist.
	// The action argument is the name of the non-existant action. This default
	// implementation renders the view in views/<controller>/<action>.
	public function defaultAction($action = 'index') {
		$class = get_called_class();
		
		// Check if the file exists. If it doesn't throw a 404 error.
		if (view::exists($class.'/'.$action)) {
			
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
	
	// If debug mode is enabled this method will be used instead of the error
	// action above. This method prints out a stack trace of the PHP error
	// instead of a generic 404 page.
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