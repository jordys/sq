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
	// sq models with the optional arguments listed here.
	public function create($data = null) {
		
	}
	
	public function read($values = '*') {
		
	}
	
	public function update($data = null, $where = null) {
		
	}
	
	public function delete($where = null) {
		
	}
	
	// Makes a data store. For instance a folder to store files or a table to
	// store mySQL data.
	public function make($schema) {
		
	}
	
	// Returns true if the database record exists. Must be implemented in driver
	// classes such as sql.
	public function exists() {
		
	}
	
	// Returns row count ignoring limit statement. Implemented by the model
	// driver class.
	public function count() {
		
	}
	
	// Searches through model list a returns an item
	public function find($where) {
		if (is_string($where)) {
			$where = array('id' => $where);
		}
		
		foreach ($this->data as $item) {
			foreach ($where as $key => $val) {
				if (is_object($item) && $item->$key == $val) {
					return $item;
				}
			}
		}
		
		return false;
	}
	
	// Searches through a model list and returns all matching items
	public function search($where) {
		$results = array();
		
		foreach ($this->data as $item) {
			foreach ($where as $key => $val) {
				if ($item->$key == $val) {
					$results[] = $item;
				}
			}
		}
		
		return $results;
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
	
	// Stores a key value array of where statements. Key = Value. If a single
	// argument is passed it is assumed to be an id and limit is automatically
	// imposed.
	public function where($argument, $operation = 'AND') {
		$this->options['where-operation'] = $operation;
		$this->options['where'] = $argument;
		
		return $this;
	}
	
	// Sets the number of results that will be returned. If limit is set to
	// boolean true the model will only contain the model data. Limit 1 will
	// result in an array of models with only one entry. If limit() is called
	// with no arguments then it will default to true.
	public function limit($limit = true) {
		$this->options['limit'] = $limit;
		
		return $this;
	}
	
	// Sets the key and direction to order results by
	public function order($order, $direction = 'DESC') {
		$this->options['order'] = $order;
		$this->options['order-direction'] = strtoupper($direction);
		
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
			if (url::get('page')) {
				$page = url::get('page');
			} else {
				$page = 1;
			}
		}
		
		if ($this->options['limit'] !== true) {
			$this->options['limit'] = $perPage * $page - $perPage.','.$perPage;
		}
		
		return $this;
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
		
		// Allow a shorthand of just passing in string instead of options to set
		// the bridge table.
		if (is_string($options)) {
			$options = array(
				'bridge' => $options
			);
		}
		
		if (isset($this->data['id'])) {
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
			
			$bridge = sq::model($options['bridge'])
				->where($where)
				->read();
			
			foreach ($bridge as $key => $item) {
				$relation = sq::model($model, array('load-relations' => false))
					->where(array('id' => $item->{$model.'_id'}))
					->limit()
					->read();
				
				// Flatten bridge with the related model
				unset($item->id);
				$relation->set($item->toArray());
				
				$bridge[$key] = $relation;
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
		if (isset($this->data['id'])) {
			$model = sq::model($name);
			
			$where = array($options['to'] => $this->data[$options['from']]);
			
			if (isset($options['where'])) {
				$where += $options['where'];
			}
			
			if (isset($options['where-raw'])) {
				$model->options['where-raw'] = $options['where-raw'];
			}
			
			if (isset($options['cascade'])) {
				$model->options['cascade'] = $options['cascade'];
			}
			
			if (isset($options['load-relations'])) {
				$model->options['load-relations'] = $options['load-relations'];
			}
			
			if (isset($options['order'])) {
				if (isset($options['order-direction'])) {
					$model->order($options['order'], $options['order-direction']);
				} else {
					$model->order($options['order']);
				}
			}
			
			if (isset($options['mount'])) {
				$name = $options['mount'];
			}
			
			$model->where($where);
			
			if (isset($options['limit'])) {
				$model->limit($options['limit']);
			}
			
			$read = null;
			if (isset($options['read'])) {
				$read = $options['read'];
			}
			
			$model->read($read);
			
			if (isset($options['flatten']) && $options['flatten'] && isset($options['limit']) && $options['limit'] === true) {
				unset($model->id);
				$this->set($model->toArray());
			} else {
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
		$data = $this->data;
		
		foreach (array('belongs-to', 'has-one', 'has-many') as $relation) {
			foreach ($this->options[$relation] as $name => $options) {
				if (is_numeric($name)) {
					$name = $options;
					$options = array();
				}
				
				$method = str_replace('-', '', $relation);
				
				$this->$method($name, $options);
			}
		}
	}
	
	// Utility method that loops through related models after a delete is
	// performed and deletes on models where cascade is true
	protected function deleteRelated() {
		$this->read();
		
		if ($this->options['limit'] !== true) {
			foreach ($this->data as $row) {
				foreach ($row as $val) {
					if (is_object($val) && $val->options['cascade']) {
						$val->delete();
					}
				}
			}
		} else {
			foreach ($this->data as $val) {
				if (is_object($val) && $val->options['cascade']) {
					$val->delete();
				}
			}
		}
	}
	
	// Utility function that loops through related records and calls an update
	// on each one of them after a main record is updated.
	protected function updateRelated() {
		if ($this->options['limit'] !== true) {
			foreach ($this->data as $row) {
				if (is_array($row)) {
					foreach ($row as $val) {
						if (is_object($val)) {
							$val->update();
						}
					}
				}
			}
		} else {
			foreach ($this->data as $val) {
				if (is_object($val)) {
					$val->update();
				}
			}
		}
	}
	
	// Utility function that uses a session to prevent duplicate data from being
	// created. Prevents form double submits.
	protected function checkDuplicate($data) {
		$status = false;
		
		if (!isset($_SESSION)) {
			session_start();
		}
		
		if (!$this->options['prevent-duplicates']
			|| !isset($_SESSION['sq-last-'.$this->options['name']])
			|| $_SESSION['sq-last-'.$this->options['name']] !== md5(implode(',', $data))
		) {
			$status = true;
		}
		
		$_SESSION['sq-last-'.$this->options['name']] = md5(implode(',', $data));
		
		return $status;
	}
}

?>