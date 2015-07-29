<?php

return array(
	'route' => array(
		'definitions' => array(
			'app/{controller=classes}/{action?}/{id?}/student/{student}',
			'app/{controller=classes}/{action?}/{id?}',
			'admin/{action?}/{id?}' => array(
				'controller' => 'admin'),
			'group/{action?}/{id?}' => array(
				'controller' => 'group'),
			'redeem/{code?}' => array(
				'action' => 'redeem'),
			'reset-password/{hash?}' => array(
				'action' => 'reset-password'),
			'{action?}',
			'{controller?}/{action?}/{id?}'
		)
	)	
)

?>