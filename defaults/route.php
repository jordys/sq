<?php

/**
 * Route component defaults
 */

return [
	'route' => [

		// Route definitions for framework. Values in braces indicate variables
		// to match from the url. Variables with a ? are optional. To do routing
		// of key value pairs use a variable with a pipe '{|}'.
		'definitions' => [
			'{controller?}/{action?}/{id?}'
		]
	]
];
