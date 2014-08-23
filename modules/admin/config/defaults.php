<?php

/**
 * Admin module defaults
 */

return array(
	'admin' => array(
		
		// Sidebar navigation in the form of title => url. Single entries format
		// to non-link section headings.
		'nav' => array(
			'Manage',
			'Users' => 'users',
			'Logout' => 'users/logout'
		),
		
		// Require login for access to admin section. False is useful for dev
		// environments.
		'require-login' => true,
		
		// Form view settings
		'form' => array(
			
			// Format for date inputs
			'date-format' => 'm/d/Y',
			
			// Placeholder date format
			'date-placeholder' => 'mm/dd/yyyy'
		),
		
		// List view settings
		'list' => array(
			
			// Format for dates
			'date-format' => 'M d, Y'
		)
	)
);

?>