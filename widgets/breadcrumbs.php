<?php

/**
 * Breadcrumbs widget
 *
 * Generates breadcrumbs on a page.
 */

abstract class sqBreadcrumbs extends widget {

	// Model attribute is passed into the widget
	public static $breadcrumbs = [];

	// Extend the render method to add the breadcrumbs to the view
	public function render() {
		$this->layout->set([
			'breadcrumbs' => self::$breadcrumbs,
			'options' => $this->options
        ]);

        return parent::render();
    }

    // Pushes a new link onto the end of the breadcrumb chain
    public function add($key, $url = null) {
        self::$breadcrumbs[$key] = $url;

        return $this;
    }
}
