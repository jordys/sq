<?php

/**
 * Mailer component defaults
 */

return [
	'mailer' => [

		// Boundary for multipart/alternative email content
		'boundary' => uniqid('np'),

		// Default send address if not overridden explicitly
		'from' => 'noreply@'.$_SERVER['HTTP_HOST'],
	]
];
