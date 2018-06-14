<?php

/**
 * Model component base class
 *
 * Models represent either a list of records or a single record. Records can be
 * related to one another in code using the has one, has many, belongs to and
 * many many relationship types. Different types of data require type classes
 * that implement the empty crud methods in this class.
 *
 * Searching model data is implemented using an eloquent style interface using
 * chainable methods to query the data. A model may be configured to have
 * validation rules built in. Rendering a model yields either a list or a form
 * of the records.
 */

abstract class sqModel extends component {

	// Set to true if the model is validated
	public $isValid = null;

	// Set to true after a database read
	protected $isRead = false;

	// Cache of many many relations to avoid double reads
	protected static $manyManyCache = [];

	// Setup initial replacer layout so it is available always
	public function __construct($options) {
		parent::__construct($options);

		if (!$this->layout && $this->options['use-layout']) {
			$this->layout = sq::view('_sqReplace');
		}
	}

	// Overridable method for setting up the schema of the model. The schema is
	// used for creating the database record.
	public function schema() {
		return $this->options['schema'];
	}

	// Called by the __tostring method to render a view of the data in the
	// model. By default the view is a form for a single result and a listing
	// multiple results. The default listing and form view may be overridden in
	// the model options.
	public function render() {

		// If the current layout is generic replace it with a either form or
		// list depending on if we are looking at a single model.
		if ($this->layout->view == '_sqReplace') {
			$this->layout->view = $this->options['list-view'];
			if ($this->isSingle()) {
				$this->layout->view = $this->options['item-view'];
			}
		}

		$this->layout->fields = $this->options['fields']['list'];
		if ($this->isSingle()) {
			$this->layout->fields = $this->options['fields']['form'];
		}

		$this->layout->model = $this;
		return $this->layout;
	}

	// CRUD methods to be implemented. These four methods must be implemented by
	// a driver class with the optional arguments listed here.
	public function create($data = null) {}
	public function read($values = null) {}
	public function update($data = null, $where = null) {}
	public function delete($where = null) {}

	// Validate form using passed in rules or options in model. Shorthand for
	// calling sq::validate().
	public function validate($rules = null) {
		if (!$rules) {
			$rules = $this->options['rules'];
		}

		$validator = sq::validator($this->data, $rules);
		$this->isValid = $validator->isValid;
		$this->set($validator->data);
		return $this->isValid;
	}

	/**
	 * Searches through model records and returns a single item
	 *
	 * If called prior to a read it searches the database otherwise it searches
	 * through the existing model objects.
	 */
	public function find($where, $operation = 'AND') {
		if (!$this->isRead) {
			return $this->where($where, $operation)->limit()->read();
		}

		if (!is_array($where)) {
			$where = ['id' => $where];
		}

		foreach ($this->data as $item) {
			foreach ($where as $key => $val) {
				if (is_array($val) && in_array($item->$key, $val)) {
					return $item;
				} elseif ($item->$key == $val) {
					return $item;
				} elseif ($operation != 'OR') {
					continue 2;
				}
			}
		}
	}

	/**
	 * Searches through model and returns a multiple items
	 *
	 * If called prior to a read it searches the database otherwise it searches
	 * through the existing model list of model objects. Returns a model object
	 * containing the found records.
	 */
	public function search($where, $operation = 'AND') {
		if (!$this->isRead) {
			return $this->where($where, $operation)->limit(false)->read();
		}

		$results = [];

		foreach ($this->data as $item) {
			foreach ($where as $key => $val) {
				if (is_array($val) && in_array($item->$key, $val)) {
					$results[] = $item;
				} elseif ($item->$key == $val) {
					$results[] = $item;
				} elseif ($operation != 'OR') {
					continue 2;
				}
			}
		}

		$model = clone($this);
		$model->data = $results;

		return $model;
	}

	// Shorthand for read with no where statement
	public function all() {
		$this->options['where'] = [];

		return $this->read();
	}

	// Shorthand method to create a new entry or update and existing one based
	// on the existance of an id property or where statment.
	public function save($data = []) {
		if (!empty($this->options['where']) && !$this->isRead) {
			$this->limit()->read(['id']);
		}

		if (empty($this->id)) {
			unset($this->id);

			return $this->create($data);
		} else {
			return $this->update($data);
		}
	}

	// Returns all of signle property from a model list as an array
	public function column($column) {
		$data = [];

		foreach ($this->data as $item) {
			if (isset($item->data[$column])) {
				$data[] = $item->data[$column];
			}
		}

		return $data;
	}

	/**
	 * Stores a key / value array of where statements
	 *
	 * If a single argument is passed it is assumed to be an id and limit is
	 * automatically imposed.
	 */
	public function where($argument, $operation = 'AND') {

		// Allow shorthand for searching by id
		if (!is_array($argument)) {

			// Set the id so its available after in the model data even if it
			// wasn't explictly set
			if (empty($this->data['id'])) {
				$this->data['id'] = $argument;
			}

			$this->limit();
			$argument = ['id' => $argument];
		}

		$this->options['where-operation'] = $operation;
		$this->options['where'] = $argument;

		return $this;
	}

	/**
	 * Sets the number of results that will be returned
	 *
	 * If limit is set to boolean true the model will only contain the model
	 * data. Limit 1 will result in an array of models with only one entry. If
	 * limit() is called with no arguments then it will default to true.
	 */
	public function limit($limit = true) {
		$this->options['limit'] = $limit;

		return $this;
	}

	// Sets the key and direction to order results by
	public function order($order, $direction = 'DESC') {
		$this->options['order'] = $order;
		$this->options['order-direction'] = strtoupper($direction);

		if ($this->isRead) {

			// $this can't be passed directly
			$ref = $this;

			usort($this->data, function($a, $b) use ($ref) {
				$order = $ref->options['order'];

				if ($ref->options['order-direction'] == 'DESC') {
					return $a->$order < $b->$order;
				} else {
					return $a->$order > $b->$order;
				}
			});
		}

		return $this;
	}

	/**
	 * Groups entries by value
	 *
	 * Groups matching column values into arrays with entries for each value.
	 * Optionally can group by two columns.
	 */
	public function group($col, $col2 = null) {
		if ($col2) {
			foreach ($this->data as $row) {
				$data[$row->$col][$row->$col2][] = $row;
			}
		} else {
			foreach ($this->data as $row) {
				$data[$row->$col][] = $row;
			}
		}

		foreach ($this->data as $key => $val) {
			unset($this->$key);
		}

		if (isset($data)) {
			$this->set($data);
		}

		return $this;
	}

	/**
	 * Requests data in pages
	 *
	 * Chainable method that groups database entries into pages. Can explictly
	 * read a certain page number or rely on the page get parameter.
	 */
	public function paginate($perPage = null, $page = null) {
		if (!$page) {
			$page = sq::request()->get('page', 1);
		}

		if (!$perPage) {
			$perPage = $this->options['items-per-page'];
		}

		$offset = $perPage * $page - $perPage;

		$this->options['pages'] = ceil($this->count() / $perPage);
		$this->options['limit'] = [$offset, $perPage];
		$this->data = array_splice($this->data, $offset, $perPage);

		return $this;
	}

	/**
	 * Returns the correct user friendly title of the model
	 *
	 * The name option may either be passed in as a single value or an array of
	 * two values. When an array is used the first value will be the plural
	 * form of the title and the second will be the singular form.
	 *
	 * If only a single value is in the name option the singular form will be
	 * assumed to be the plural form without the 's' at the end.
	 *
	 * @param string $plurality Return singular or plural form of the title. If
	 *  left blank it will be inferred from the model.
	 * @param string $type Return the title of the specified type not of the
	 *  overall model
	 * @return string The correct title for the model single|plural
	 */
	public function getTitle($plurality = null, $type = null) {
		$title = isset($this->options['title'])
			? $this->options['title']
			: ucwords($this->options['name']);

		if ($type) {
			$title = $this->options['types'][$type];
		}

		// @TODO Unify this with the code in the view
		if (is_string($title)) {
			if (substr($title, -1) == 's') {
				$singular = substr($title, 0, -1);
			} else {
				$singular = $title;
				$title = $title.'s';
			}

			$title = [$title, $singular];
		}

		if ($plurality == 'singular' || (!$plurality && $this->isSingle())) {
			return $title[1];
		}

		return $title[0];
	}

	/**
	 * Generates a preview link
	 *
	 * Creates a preview link using the values declared in options. The preview
	 * option array contains a route parameter map except the values will be
	 * replaced with the keys from the currently selected model record.
	 *
	 * @return sqRoute The URL object of the preview page
	 */
	public function getPreviewURL() {
		$preview = $this->options['preview'];

		// Preview URLs may be unique for different types of content. If the
		// model contains multiple types then different urls are required for
		// each type.
		if (isset($this->type)) {
			$preview = $this->options['preview'][$this->type];
		}

		foreach ($preview as $key => $val) {
			if (isset($this->$val)) {
				$preview[$key] = $this->$val;
			} else {
				throw new Error('The preview URL parameter "'.$val.'" does not exist on this model record. No URL can be generated.');
			}
		}

		return sq::route()->to($preview);
	}

	// Returns true if the object represents a single data entry. False if the
	// model represents a listing of multiple records.
	public function isSingle() {
		return $this->options['limit'] === true;
	}

	// Creates a belongs to model relationship
	public function belongsTo($model, array $options = []) {
		if (empty($options['from'])) {
			$options['from'] = $model.'_id';
		}

		if (empty($options['to'])) {
			$options['to'] = 'id';
		}

		if (empty($options['cascade'])) {
			$options['cascade'] = false;
		}

		$options['limit'] = true;

		return $this->relate($model, $options, 'belongs-to');
	}

	// Creates a has one model relationship
	public function hasOne($model, array $options = []) {
		if (empty($options['from'])) {
			$options['from'] = 'id';
		}

		if (empty($options['to'])) {
			$options['to'] = $this->options['name'].'_id';
		}

		$options['limit'] = true;

		return $this->relate($model, $options, 'has-one');
	}

	// Creates a has many model relationship
	public function hasMany($model, array $options = []) {
		if (empty($options['to'])) {
			$options['to'] = $this->options['name'].'_id';
		}

		if (empty($options['from'])) {
			$options['from'] = 'id';
		}

		return $this->relate($model, $options, 'has-many');
	}

	// Creates a many to many model relationship
	public function manyMany($model, $options) {

		// Allow a shorthand of just passing a string instead of options to
		// set the bridge table
		if (is_string($options)) {
			$options = [
				'bridge' => $options
			];
		}

		if (empty($options['to'])) {
			$options['to'] = $this->options['name'].'_id';
		}

		if (empty($options['from'])) {
			$options['from'] = 'id';
		}

		return $this->relate($model, $options, 'many-many');
	}

	/**
	 * Returns the highest value in the specified column in the current set.
	 *
	 * @param string $column Column to get the max value of
	 */
	public function max($column) {
		return max(array_column($this->data, $column));
	}

	/**
	 * Returns the lowest value in the specified column in the current set.
	 *
	 * @param string $column Column to get the min value of
	 */
	public function min($column) {
		return min(array_column($this->data, $column));
	}

	// Creates a model relationship. Can be called directly or with the helper
	// hasOne, hasMany, belongsTo and manyMany methods.
	protected function relate($name, $options, $type) {

		// Set the relation options to the model for later use
		if (!$this->isRead) {
			$this->options[$type][$name] = $options;
		}

		// Manage calling this function on a list object. Loop through and call
		// the same method on the contained model objects.
		if (!$this->isSingle()) {
			foreach ($this->data as $item) {
				$item->relate($name, $options, $type);
			}

			return $this;
		}

		// Skip execution for unread models. Relations will be triggered again
		// after a read.
		if (!$this->isRead) {
			return $this;
		}

		if ($type == 'many-many') {
			$model = sq::model($options['bridge'], [
				'class' => $name,
				'user-specific' => false
			]);
		} else {
			$model = sq::model($name, $options);
		}

		$where = [$options['to'] => $this->data[$options['from']]];
		if (isset($options['where'])) {
			$where += $options['where'];
		}

		$read = isset($options['read']) ? $options['read'] : null;
		$model->where($where)->read($read);

		if ($type == 'many-many') {
			foreach ($model as $key => $item) {
				unset($item->id);

				if (isset(self::$manyManyCache[$item->{$name.'_id'}])) {
					$relation = clone(self::$manyManyCache[$item->{$name.'_id'}]);
				} else {
					$relation = sq::model($name);

					if (isset($options['user-specific'])) {
						$relation->options['user-specific'] = $options['user-specific'];
					}

					$relation->find($item->{$name.'_id'});

					self::$manyManyCache[$item->{$name.'_id'}] = $relation;
				}

				// Flatten bridge with the related model
				$relation->set($item->to[]);

				$model[$key] = $relation;
			}
		}

		if (isset($options['flatten']) && $options['flatten'] && isset($options['limit']) && $options['limit'] === true) {
			unset($model->id);
			$this->set($model->to[]);
		} else {
			if (isset($options['mount'])) {
				$name = $options['mount'];
			}

			$model->options['name'] = $name;
			$this->data[$name] = $model;
		}

		return $this;
	}

	// Utility method that creates model relationships from config after a read
	protected function relateModel() {
		foreach (['belongs-to', 'has-one', 'has-many', 'many-many'] as $relation) {
			foreach ($this->options[$relation] as $name => $options) {

				// Allows the shorthand relation with just the name of the model
				if (is_numeric($name)) {
					$name = $options;
					$options = [];
				}

				$method = str_replace('-', '', $relation);

				$this->$method($name, $options);
			}
		}
	}

	// Utility method that loops through related models after an action is
	// performed and performs the same action on models where cascade is true
	protected function onRelated($method) {
		foreach ($this->data as $row) {
			if (is_array($row)) {
				foreach ($row as $val) {
					if (is_object($val) && $val->options['cascade']) {
						$val->$method();
					}
				}
			} elseif (is_object($row) && $row->options['cascade']) {
				$row->$method();
			}
		}
	}

	// Utility method that uses a session to prevent duplicate data from being
	// created. Prevents form double submits.
	protected function checkDuplicate() {
		if (!$this->options['prevent-duplicates']
			|| empty($_SESSION['sq-last-'.$this->options['name']])
			|| $_SESSION['sq-last-'.$this->options['name']] !== md5(implode(',', $this->data))
		) {
			return true;
		}

		$_SESSION['sq-last-'.$this->options['name']] = md5(implode(',', $this->data));

		return false;
	}
}
