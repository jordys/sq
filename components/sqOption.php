<?php

/**
 * Option component
 *
 * Options are user controlled preferences that may be used to allow user
 * controlled configuration of your app. Options are stored in the sq_options
 * table and may be managed via the admin module or with a custom controller.
 *
 * Init this component in a view and the corresponding database record will
 * automagically be created. The component invocation most include the id and
 * title of the option. The initialization may also contain extra information
 * to improve the display in the admin system:
 * format  - controls what field format will be used in the admin editing page
 * help    - adds help text to field
 * default - specifies a default value
 *
 * @example
 * sq::option('my-preference', 'Cool Option', ['default' => 'default value'])
 *
 * @category component
 */

abstract class sqOption extends component {

	// Model of the individual option
	private $option;

	// Cache of the options model
	protected static $model;

	public function __construct($id, $title, $options) {
		parent::__construct($options);

		// Model is cached so it won't be needlessly reloaded
		if (!self::$model) {
			self::$model = sq::model('sq_options')->make()->all();
		}

		// Find or create the requested option
		$this->option = self::$model->find($id);
		if (!$this->option) {
			$this->option = sq::model('sq_options')->create([
				'id'     => $id,
				'title'  => $title,
				'format' => $this->options['format'],
				'help'   => $this->options['help'],
				'value'  => $this->options['default']
			]);
		}
	}

	/**
	 * Gets the value of the current option. Used for chaining.
	 *
	 * @return any The value of the preference
	 */
	public function value() {
		return $this->option->value;
	}
}
