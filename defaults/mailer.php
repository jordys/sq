<?php

/**
 * Mailer defaults
 */

return array(
	'mailer' => array(
		
		// Boundary for multipart/alternative email content
		'boundary' => uniqid('np'),
		
		// Default send address if not overridden explicitly
		'from' => 'noreply@'.$_SERVER['HTTP_HOST'],
	)
);

?>