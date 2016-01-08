<?php

/**
 * Slot component defaults
 */

return array(
	'slot' => array(
		'replacers' => array(),
		'type' => 'markdown'
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