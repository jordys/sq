<?php

/**
 * View component defaults
 */

return array(
	'view' => array(
		
		// Meta description string
		'description' => null,
		
		// Array of meta keywords
		'keywords' => array(),
		
		// Website default html title
		'title' => null,
		
		// Doctype used in template
		'doctype' => '<!DOCTYPE html>',
		
		// Default charset declared in html head. False for none.
		'charset' => 'UTF-8',
		
		// Lang attribute on html tag
		'language' => 'en',
		
		// Path to favicon
		'favicon' => sq::base().'favicon.ico',
		
		// id attribute applied to the body tag
		'id' => null,
		
		// Data passed directly to javascript as a json array. Accessed in
		// javascript in the sq.data object.
		'js-data' => array(
			'base' => sq::base()
		)
	)
);

?>