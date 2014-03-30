<?php

/**
 * Base model class
 *
 * This class forms the base for all sq models. Model classes can be specific 
 * for a single data set or for broad for entire types of databases such as sql.
 *
 * To make a new model extend the model class. To extend the base model, add a
 * class named model to your app's components folder. Models must implement the
 * CRUD methods (create, read, update, delete).
 */

abstract class sqModel extends component {	
	
	// Path to view file for the model. Defaults to form or listing depending on
	// the number of results. Similar to how layout functions in a controller.
	// The view will be rendered automatically when the model is echoed via the
	// __tostring magic method.
	public $view;
	
	// Parameters for the model
	protected $where, $limit, $order, $orderDirection = 'DESC',
		$whereOperation = 'AND';
		
	// Relationships recognized by models
	protected $relationships = array('belongs-to', 'has-one', 'has-many');
	
	// Called by the __tostring method to render a view of the data in the 
	// model. By default the view is a form for a single result and a listing 
	// multiple results. The default listing and form view can also be 
	// overridden in the model options.
	public function render() {
		if ($this->view) {
			$name = explode('/', $this->view);
			$name = array_pop($name);
			
			$rendered = sq::view($this->view, array(
				'model' => $this,
				'fields' => $this->options['fields'][$name]
			));
			
		} elseif ($this->limit) {
			$rendered = sq::view('forms/form', array(
				'model' => $this,
				'fields' => $this->options['fields']['form']
			));
			
		} else {
			$rendered = sq::view('forms/list', array(
				'model' => $this,
				'fields' => $this->options['fields']['list']
			));
		}
		
		if (is_object($this->layout)) {
			$this->layout->content = $rendered;
			$rendered = $this->layout;
		}
		
		return $rendered;
	}
	
	// CRUD methods to be implemented. These four methods must be implemented by 
	// sq models with the optional arguments listed here.
	public function create($data = false) {
		
	}
	
	public function read($values = '*') {
		
	}
	
	public function update($data = false, $where = false) {
		
	}
	
	public function delete($where = false) {
		
	}
	
	// Searches through model list a returns an item
	public function find($where) {
		foreach ($this->data as $item) {
			foreach ($where as $key => $val) {
				if ($item->$key == $val) {
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
			$data[] = $item->$column;
		}
		
		return $data;
	}
	
	// Stores a key value array of where statements. Key = Value. If a single
	// argument is passed it is assumed to be an id and limit is automatically
	// imposed.
	public function where($argument, $operation = 'AND') {
		if (is_array($argument)) {
			$this->where = $argument;
			$this->whereOperation = $operation;
		} else {
			$this->where = array('id' => $argument);
			
			$this->limit();
		}
		
		return $this;
	}
	
	// Sets the number of results that will be returned. If limit is set to 
	// boolean true the model will only contain the model data. Limit 1 will 
	// result in an array of models with only one entry. If limit() is called
	// with no arguments then it will default to true.
	public function limit($count = true) {
		$this->limit = $count;
		
		return $this;
	}
	
	// Sets the key and direction to order results by
	public function order($order, $direction = 'DESC') {
		$this->order = $order;
		$this->orderDirection = $direction;

		return $this;
	}
	
	public function group($field, $field1 = false) {
		if ($field1) {
			foreach ($this->data as $row) {
				$data[$row->$field][$row->$field1][] = $row;
			}
		} else {
			foreach ($this->data as $row) {
				$data[$row->$field][] = $row;
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
	
	// Creates a belongs to model relationship
	public function belongsTo($model, $match = false, $local = 'id', $params = array()) {
		if (!$match) {
			$match = $model.'_id';
		}
		
		if (!isset($params['cascade'])) {
			$params['cascade'] = false;
		}
		
		$this->relateModel($model, $local, $match, $params, true);
		
		return $this;
	}
	
	// Creates a has one model relationship
	public function hasOne($model, $match = false, $local = 'id', $params = array()) {
		if (!$match) {
			$match = $this->options['name'].'_id';
		}
		
		$this->relateModel($model, $match, $local, $params, true);
		
		return $this;
	}
	
	// Creates a has many model relationship
	public function hasMany($model, $match = false, $local = 'id', $params = array()) {
		if (!$match) {
			$match = $this->options['name'].'_id';
		}
		
		$this->relateModel($model, $match, $local, $params, false);
		
		return $this;
	}
	
	// Is called directly after a read to automatically create the relationships
	// as defined in the model defaults.
	public function relate() {
		$data = $this->data;
		
		foreach ($this->relationships as $relationship) {
			foreach ($this->options[$relationship] as $name => $relation) {
				$params = array();
				
				if (isset($relation['params'])) {
					$params = $relation['params'];
					unset($relation['params']);
				}
				
				if ($relationship == 'belongs-to' && !isset($params['cascade'])) {
					$params['cascade'] = false;
				}
				
				foreach ($relation as $local => $match) {
					$limit = false;
					if ($relationship == 'has-one' || $relationship == 'belongs-to') {
						$limit = true;
					}
					
					$this->relateModel($name, $match, $local, $params, $limit);
				}
			}
		}
	}
	
	// Utility function that creates model relationships
	protected function relateModel($name, $match, $local, $params, $limit) {
		if (isset($this->data['id'])) {
			if (isset($this->data[$local])) {
				$model = sq::model($name);
				
				$where = array($match => $this->data[$local]);
				
				if (!$this->options['ignore-params']) {
					if (isset($params['where'])) {
						$where += $params['where'];
					}
					
					if (isset($params['cascade'])) {
						$model->options['cascade'] = $params['cascade'];
					}
					
					if (isset($params['load-relations'])) {
						$model->options['load-relations'] = $params['load-relations'];
					}
					
					if (isset($params['order'])) {
						if (isset($params['order-direction'])) {
							$model->order($params['order'], $params['order-direction']);
						} else {
							$model->order($params['order']);
						}
					}
				}
				
				$model->where($where);
				
				if ($limit) {
					$model->limit();
				}
				
				$model->read();
				
				$this->data[$name] = $model;
			}
		} else {
			foreach ($this->data as $item) {
				$item->relateModel($name, $match, $local, $params, $limit);
			}
		}
	}
	
	// Utility function that loops through related models after a delete is
	// performed and deletes on models where cascade is true
	protected function deleteRelated() {
		$this->read();
		
		if ($this->limit !== true) {
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
		if ($this->limit !== true) {
			foreach ($this->data as $row) {
				foreach ($row as $val) {
					if (is_object($val)) {
						$val->update();
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