<?php

/**
 * Slot component
 *
 * Slots are small pieces of content that are dropped into a view and managed in
 * a database. Slots are stored in the automatically created sq_slots database.
 * By default slots contain markdown content but they can also contain images
 * and straight text content.
 *
 * More rendering types can be added by extending this class with static methods
 * for the type(s) you want to create.
 *
 * @example
 * sq::slot('my-slot', 'Homepage Slot', ['type' => 'markdown'])
 *
 * @category component
 */

abstract class sqSlot extends component {

	// Model of the individual slot
	public $slot;

	// Cache of the slots model
	protected static $model;

	// Extended controller sets up the slot model
	public function __construct($id, $title, $options) {
		parent::__construct($options);

		// Create model object if one doesn't already exist, read slots and
		// cache them
		if (!self::$model) {
			self::$model = sq::model('sq_slots')->make()->all();
		}

		// Find or create the requested slot
		$this->slot = self::$model->find($id);
		if (!$this->slot) {
			$this->slot = sq::model('sq_slots')->create([
				'id'      => $id,
				'title'   => $title,
				'type'    => $this->options['type'],
				'content' => $this->options['content']
			]);
		}
	}

	// Passes values into in slot variables eg: {variable}. Chainable.
	public function replace(array $replacers) {
		$this->options['replacers'] += $replacers;

		return $this;
	}

	// Replace the slot content variables and return the slot content wrapped
	// in a div. The correct rendering method to use is determined by the slots
	// type.
	public function render() {

		// Get the output from the correct type method
		$type = $this->slot->type;
		$output = self::$type($this->slot);

		// Base is always a variable
		$replacers = ['{base}' => sq::base()];

		// Replace the content variables in the outputted slot content
		foreach ($this->options['replacers'] as $key => $val) {
			$replacers['{'.$key.'}'] = $val;
		}

		$output = strtr($output, $replacers);

		return "<div class=\"sq-slot {$this->slot->id}\">$output</div>";
	}


	/**************************************************************************
	 * Slot type methods
	 *
	 * These methods are called dynamically to generate the formatted slot
	 * content. More can be created by extending this class.
	 *************************************************************************/

	// Render the slot content as markdown (default)
	public static function markdown($slot) {
		sq::load('phpMarkdown');
		return markdown($slot->content);
	}

	// Return the slot content with no manipulation
	public static function text($slot) {
		return $slot->content;
	}

	// Return the slot content as the url of an image
	public static function image($slot) {
		return '<img src="'.sq::base().$slot->content.'" alt="'.$slot->alt_text.'"/>';
	}
}
