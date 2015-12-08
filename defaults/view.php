<?php

/**
 * View defaults
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
		
		// Data passed directly to javascript as a json array. Accessed in js as
		// the sq object.
		'js-data' => array(
			'base' => sq::base()
		),
		
		// Pagination defaults
		'pagination' => array(
			
			// Number of page numbers to show before hiding them
			'visible-numbers' => 9,
			
			// Text for prev / next links. False will disable the links.
			'prev' => '< Previous',
			'next' => 'Next >',
			
			// Text for first / last links. False will disable the links. 
			// {number} will be replaced with the last page number or first page
			// number.
			'first' => '<< First',
			'last' => 'Last ({number}) >>',
			
			// Show pagination even if there aren't enough entries to have more
			// than one page
			'show-always' => false
		)
	),
	
	// Slots database config. Slots are chunks of content defined in code and
	// editable via the Admin module or via a custom setup in your app.	
	'sq_slots' => array(
		'schema' => array(
			'id'       => 'VARCHAR(100) NOT NULL',
			'name'     => 'VARCHAR(100) NOT NULL',
			'type'     => 'VARCHAR(100) NOT NULL',
			'alt_text' => 'VARCHAR(100) NOT NULL',
			'content'  => 'TEXT'
		),
		'actions' => array(),
		'inline-actions' => array('update'),
		'fields' => array(
			'list' => array(
				'name' => 'text',
				'type' => 'text'
			)
		)
	)
);

?>