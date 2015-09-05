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
	protected $isRead = false;
	
	// Cache of many many relations to avoid double reads
	public static $manyManyCache = array();
	
	// Workaround to avoid object content rules when using usort
	public static $usort;
	
	// Called by the __tostring method to render a view of the data in the
	// model. By default the view is a form for a single result and a listing
	// multiple results. The default listing and form view can also be
	// overridden in the model options.
	public function render() {
		if ($this->layout) {
			$name = explode('/', $this->layout);
			$name = array_pop($name);
			
			$this->layout->model = $this;
			if (isset($this->options['fields'][$name])) {
				$this->layout->fields = $this->options['fields'][$name];
			}
			
			return $this->layout;
		} elseif ($this->options['limit']) {
			return sq::view('forms/form', array(
				'model' => $this,
				'fields' => $this->options['fields']['form']
			));
		} else {
			return sq::view('forms/list', array(
				'model' => $this,
				'fields' => $this->options['fields']['list']
			));
		}
	}
	
	// CRUD methods to be implemented. These four methods must be implemented by
	// a driver class with the optional arguments listed here.
	public function create($data = null) {}
	public function read($values = '*') {}
	public function update($data = null, $where = null) {}
	public function delete($where = null) {}
	
	// Makes a data store. For instance a folder to store files or a table to
	// store sql data.
	public function make($schema) {}
	
	// Returns true if the database record exists. Must be implemented in driver
	// classes such as sql.
	public function exists() {}
	
	// Validate form using passed in rules or options in model. Shorthand for
	// calling sq::validate().
	public function validate($rules = null) {
		return sq::validator($this->data, $rules)->isValid;
	}
	
	/**
	 * Searches through model records and returns a single item
	 *
	 * If called prior to a read it searches the database otherwise it searches
	 * through the existing model objects.
	 */
	public function find($where, $operation = 'AND') {
		if ($this->isRead) {
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
			
			return false;
		} else {
			return $this->where($where, $operation)->limit()->read();
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
		if ($this->isRead) {
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
		} else {
			return $this->where($where, $operation)->limit(false)->read();
		}
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
			if (isset($item->$column)) {
				$data[] = $item->$column;
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
				
				if ($ref->options['order-direction'] == 'DESC') {
					return $a[$order] < $b[$order];
				} else {
					return $a[$order] > $b[$order];
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
	public function paginate($perPage = 10, $page = null) {
		if (!$page) {
			if (sq::request()->get('page')) {
				$page = sq::request()->get('page');
			} else {
				$page = 1;
			}
		}
		
		if ($this->options['limit'] !== true) {
			$this->options['limit'] = $perPage * $page - $perPage.','.$perPage;
		}
		
		return $this;
	}
	
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
		
		$this->relate($model, $options);
		
		return $this;
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
		
		$this->relate($model, $options);
		
		return $this;
	}
	
	// Creates a has many model relationship
	public function hasMany($model, array $options = array()) {
		if (empty($options['to'])) {
			$options['to'] = $this->options['name'].'_id';
		}
		
		if (empty($options['from'])) {
			$options['from'] = 'id';
		}
		
		$this->relate($model, $options);
		
		return $this;
	}
	
	public function manyMany($model, $options = array()) {
		if ($this->options['limit'] === true) {
			
			// Allow a shorthand of just passing in string instead of options to
			// set the bridge table.
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
			
			$where = array($options['to'] => $this->data[$options['from']]);
			
			if (isset($options['where'])) {
				$where += $options['where'];
			}
			
			$bridge = sq::model($options['bridge'], array(
				'class' => $model,
				'user-specific' => false
			));
			
			if ($this->data[$options['from']] !== null) {
				$bridge->search($where);
				
				foreach ($bridge as $key => $item) {
					unset($item->id);
					
					if (isset(self::$manyManyCache[$item->{$model.'_id'}]) && false) {
						$relation = self::$manyManyCache[$item->{$model.'_id'}];
					} else {
						$relation = sq::model($model);
						
						if (isset($options['user-specific'])) {
							$relation->options['user-specific'] = $options['user-specific'];
						}
						
						$relation->find($item->{$model.'_id'});
						
						self::$manyManyCache[$item->{$model.'_id'}] = $relation;
					}
					
					// Flatten bridge with the related model
					$relation->set($item->toArray());
					
					$bridge[$key] = $relation;
				}
			}
			
			$this->$model = $bridge;
		} else {
			foreach ($this->data as $item) {
				$item->manyMany($model, $options);
			}
		}
		
		return $this;
	}
	
	// Creates a model relationship. Can be called directly or with the helper
	// hasOne, hasMany, belongsTo and manyMany methods.
	protected function relate($name, $options) {
		if ($this->options['limit'] === true) {
			$model = sq::model($name, $options);
			
			$model->options['where'][$options['to']] = $this->data[$options['from']];
			
			$read = isset($options['read']) ? $options['read'] : '*';
			
			if ($this->data[$options['from']] !== null) {
				$model->read($read);
			}
			
			if (isset($options['flatten']) && $options['flatten'] && isset($options['limit']) && $options['limit'] === true) {
				unset($model->id);
				$this->set($model->toArray());
			} else {
				if (isset($options['mount'])) {
					$name = $options['mount'];
				}
				
				$this->data[$name] = $model;
			}
		} else {
			foreach ($this->data as $item) {
				$item->relate($name, $options);
			}
		}
	}
	
	// Utility method that creates model relationships from config after a read
	protected function relateModel() {
		foreach (array('belongs-to', 'has-one', 'has-many') as $relation) {
			foreach ($this->options[$relation] as $name => $options) {
				
				// Allows the shorthand has-many with just the name of the model
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
		if ($this->options['limit'] !== true) {
			foreach ($this->data as $row) {
				foreach ($row as $val) {
					if (is_object($val) && $val->options['cascade']) {
						$val->$method();
					}
				}
			}
		} else {
			foreach ($this->data as $val) {
				if (is_object($val) && $val->options['cascade']) {
					$val->$method();
				}
			}
		}
	}
	
	// Utility function that uses a session to prevent duplicate data from being
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