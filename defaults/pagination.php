<?php

/**
 * Pagination widget defaults
 */

return [
	'pagination' => [

		// Number of page numbers to show before hiding them
		'visible-numbers' => 9,

		// Text for prev / next links. False will disable the links.
		'prev' => '< Previous',
		'next' => 'Next >',

		// Text for first / last links. False will disable the links. {number}
		// will be replaced with the last page number or first page number.
		'first' => '<< First',
		'last' => 'Last ({number}) >>',

		// Show pagination even if there aren't enough entries to have more than
		// one page
		'show-always' => false,

		// Show rel="next" / rel="prev" links in the header for Google
		'seo-links' => true
	]
];
