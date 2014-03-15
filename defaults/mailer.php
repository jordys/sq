<?php

/**
 * Mailer defaults
 */

$defaults = array(
	'mailer' => array(
		
		// Boundary for multipart/alternative email content
		'boundary' => uniqid('np'),
		
		// Default send address if not overridden explicitly
		'from' => 'noreply@'.$_SERVER['HTTP_HOST'],
		
		// Email format: 'text', 'html' or 'both' for multipart
		'format' => 'both',
		
		// Default view used to render plain text and html emails.
		'text-view' => 'email/text',
		'html-view' => 'email/html'
	)
);

?>