<?php

/**
 * Validator component defaults
 */

return [
	'validator' => [
		
		// Default error messages. {label} will be replaced with the name of the
		// field and {value} will be replaced with the invalid value.
		'messages' => [
			'required' => '{label} is required',
			'numeric'  => '{label} needs to be a number',
			'integer'  => '{label} needs to be an integer',
			'email'    => '{value} is not a valid email address',
			'url'      => '{value} is not a valid URL',
			'generic'  => '{label} is not valid'
		]
	]
];

?>