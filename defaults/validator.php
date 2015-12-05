<?php

/**
 * Validator defaults
 */

return array(
	'validator' => array(
		
		// Default error messages. [label] will be replaced with the name of the
		// field and [value] will be replaced with the invalid value.
		'messages' => array(
			'required' => '[label] is required',
			'numeric'  => '[label] needs to be a number',
			'integer'  => '[label] needs to be an integer',
			'generic'  => '[label] is not valid'
		)
	)
);

?>