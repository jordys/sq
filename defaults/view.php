<?php

/**
 * View defaults
 */

$defaults = array(
	'view' => array(
		
		// Meta description string
		'description' => null,
		
		// Array of meta keywords
		'keywords' => array(),
		
		// Website default html title
		'title' => null,
		
		// Doctype used in template
		'doctype' => '<!DOCTYPE html>',
		
		// Lang attribute on html tag
		'language' => 'en',
		
		// Path to favicon
		'favicon' => 'favicon.ico',
		
		// id attribute applied to the body tag
		'id' => null,
		
		// Data passed directly to javascript as a json array. Accessed in js as
		// the sq object.
		'js-data' => array(
			'base' => sq::base()	
		),
		
		// Slots database config. Slots are chunks of content defined in code
		// and editable via the Admin module or via a custom setup in your 
		// app.
		'slots-db' => array(
			array(
				'id'      => 'VARCHAR(100) NOT NULL',
				'name'    => 'VARCHAR(100) NOT NULL',
				'type'    => 'VARCHAR(100) NOT NULL',
				'content' => 'TEXT'
			)
		)
	)
);

?>