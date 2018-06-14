<?php

/**
 * Asset component defaults
 */

return [
	'asset' => [

		// Revision marker coded into the asset md5 urls. Can be any format
		// that interprets to a string. Changing the revision number changes
		// the asset urls hard breaking the browser cache.
		'revision' => 1,

		// File permissions for the directory assets are built to
		'permissions' => 0777,

		// Location where built assets are placed
		'path' => 'built'
	]
];
