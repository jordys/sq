<?php

/**
 * Base model class
 *
 * This class forms the base for all sq models. Model classes can be specific
 * for a single data set or for broad for entire types of databases such as
 * sql.
 *
 * To make a new model extend the model class. To extend the base model, add a
 * class named model to your app's components folder. Models must implement the
 * CRUD methods (create, read, update, delete).
 */

abstract class sqModel extends component {
	
	// Gets set to true after a database read
	public $isRead = false;
	
	// Set to true if the model is valid
	public $isValid = null;
	
	// Cache of many many relations to avoid double reads
	public static $manyManyCache = array();
	
	// Workaround to avoid object content rules when using usort
	public static $usort;
	
	// Setup initial replacer layout so it is available always
	public function __construct($options = array()) {
		parent::__construct($options);
		
		if (!$this->layout && $this->options['use-layout']) {
			$this->layout = sq::view('_sqReplace');
		}
	}
	
	// Called by the __tostring method to render a view of the data in the
	// model. By default the view is a form for a single result and a listing
	// multiple results. The default listing and form view can also be
	// overridden in the model options.
	public function render() {
		$name = explode('/', $this->layout->view);
		$name = array_pop($name);
		
		// If the current layout is generic replace it with a either form or
		// list depending on if we are looking at a single model.
		if ($name == '_sqReplace') {
			$name = $this->isSingle() ? 'form' : 'list';
			
			// Check for a view in forms/{model}/{list|form} that would replace
			// the default if it exists
			$this->layout->view = 'forms/'.$name;
			if (view::exists('forms/'.$this->options['name'].'/'.$name)) {
				$this->layout->view = 'forms/'.$this->options['name'].'/'.$name;
			}
		}
		
		// Check for view specific fields
		if (isset($this->options['fields'][$name])) {
			$fields = $this->options['fields'][$name];
		}
		
		$this->layout->fields = $fields;
		$this->layout->model = $this;
		
		return $this->layout;
	}
	
	// CRUD methods to be implemented. These four methods must be implemented by
	// a driver class with the optional arguments listed here.
	public function create($data = null) {}
	public function read($values = '*') {}
	public function update($data = null, $where = null) {}
	public function delete($where = null) {}
	
	// Returns the number of records. Implemented in driver.
	public function count() {}
	
	// Validate form using passed in rules or options in model. Shorthand for
	// calling sq::validate().
	public function validate($rules = null) {
		if (!$rules) {
			$rules = $this->options['rules'];
		}
		
		$this->isValid = sq::validator($this->data, $rules)->isValid;
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
			$where = array('id' => $where);
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
		
		$results = array();
		
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
		$this->options['where'] = array();
		
		return $this->read();
	}
	
	// Shorthand method to create a new entry or update and existing one based
	// on the existance of an id property or where statment.
	public function save($data = array()) {
		if (!empty($this->options['where']) && !$this->isRead) {
			$this->limit()->read(array('id'));
		}
		
		if (isset($this->id) && $this->id) {
			return $this->update($data);
		} else {
			return $this->create($data);
		}
	}
	
	// Returns all of signle property from a model list as an array
	public function column($column) {
		$data = array();
		
		foreach ($this->data as $item) {
			if (isset($item->data[$column])) {
				$data[] = $item->data[$column];
			}
		}
		
		return $data;
	}
	
	/**
	 * Stores a key value array of where statements
	 *
	 * If a single argument is passed it is assumed to be an id and limit is
	 * automatically imposed.
	 */
	public function where($argument, $operation = 'AND') {
		
		// Allow shorthand for searching by id
		if (!is_array($argument)) {
			$this->limit();
			$argument = array('id' => $argument);
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
			
			// This is required to work around object contexts in php 5.3
			self::$usort = $this;
			
			usort($this->data, function($a, $b) {
				$ref = sqModel::$usort;
				
				$order = $ref->options['order'];
				
				if ($ref->options['order-direction'] != 'DESC') {
					return $a->data[$order] < $b->data[$order];
				} else {
					return $a->data[$order] > $b->data[$order];
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
		
		if ($this->isRead) {
			$this->options['pages'] = ceil(count($this->data) / $perPage);
			$this->data = array_splice($this->data, $offset, $perPage);
		} else {
			$this->options['pages'] = ceil($this->count() / $perPage);
		}
		
		$this->options['limit'] = array($offset, $perPage);
		
		return $this;
	}
	
	// Returns true if the object represents a single data entry. False if the
	// model represents a listing of multiple records.
	public function isSingle() {
		return $this->options['limit'] === true;
	}
	
	// Creates a belongs to model relationship
	public function belongsTo($model, array $options = array()) {
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
	public function hasOne($model, array $options = array()) {
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
	public function hasMany($model, array $options = array()) {
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
			$options = array(
				'bridge' => $options
			);
		}
		
		if (empty($options['to'])) {
			$options['to'] = $this->options['name'].'_id';
		}
		
		if (empty($options['from'])) {
			$options['from'] = 'id';
		}
		
		return $this->relate($model, $options, 'many-many');
	}
	
	// Creates a model relationship. Can be called directly or with the helper
	// hasOne, hasMany, belongsTo and manyMany methods.
	protected function relate($name, $options, $type) {
		
		// Manage calling this function on a list object. Loop through and call
		// the same method on the contained model objects.
		if (!$this->isRead) {
			$this->options[$type][$name] = $options;
		}
		
		if (!$this->isSingle()) {
			foreach ($this->data as $item) {
				$item->relate($name, $options, $type);
			}
			
			return $this;
		}
		
		if (!$this->isRead) {
			return $this;
		}
		
		if ($type == 'many-many') {
			$model = sq::model($options['bridge'], array(
				'class' => $name,
				'user-specific' => false
			));
		} else {
			$model = sq::model($name, $options);
		}
		
		$where = array($options['to'] => $this->data[$options['from']]);
		if (isset($options['where'])) {
			$where += $options['where'];
		}
		
		$read = isset($options['read']) ? $options['read'] : '*';
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
				$relation->set($item->toArray());
				
				$model[$key] = $relation;
			}
		}
		
		if (isset($options['flatten']) && $options['flatten'] && isset($options['limit']) && $options['limit'] === true) {
			unset($model->id);
			$this->set($model->toArray());
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
		foreach (array('belongs-to', 'has-one', 'has-many', 'many-many') as $relation) {
			foreach ($this->options[$relation] as $name => $options) {
				
				// Allows the shorthand relation with just the name of the model
				if (is_numeric($name)) {
					$name = $options;
					$options = array();
				}
				
				$method = str_replace('-', '', $relation);
				
				$this->$method($name, $options);
			}
		}
	}
	
	// Utility method that loops through related models after an action is
	// performed and performs the same action on models where cascade is true
	protected function onRelated($method) {
		if ($this->isSingle()) {
			foreach ($this->data as $val) {
				if (is_object($val) && $val->options['cascade']) {
					$val->$method();
				}
			}
		} else {
			foreach ($this->data as $row) {
				foreach ($row as $val) {
					if (is_object($val) && $val->options['cascade']) {
						$val->$method();
					}
				}
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

?>