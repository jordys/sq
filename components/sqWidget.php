<?php

/**
 * Widget componenet
 *
 * Widgets contain small reusable pieces of front end content. The contain their
 * own views and setup functionality. Most of the code for the widget should be
 * written in the init method for setup.
 *
 * Widgets may either contain a render method to print content to the screen
 * directly or use a layout and assign content to it by overriding the widget
 * init method.
 */

abstract class sqWidget extends component {

	// Handle setting parameters to defined widget properties
	public function __construct($params = [], $options = []) {

		// Set the properties in the params array to the widget
		foreach ($params as $key => $val) {
			if (!property_exists($this, $key)) {
				sq::error('404', "Unsupported argument '{$key}' passed to ".get_class($this).' widget.');
			}

			$this->$key = $val;
		}

		$this->layout = 'widgets/'.get_class($this);

		parent::__construct($options);
	}

	// Render the layout to the screen if there is one
	public function render() {
		if (is_object($this->layout)) {
			return $this->layout;
		}
	}
}
