<?php

/**
 * SQL model implementation
 *
 * Provides an eloquent object oriented interface for managing mySQL records.
 * Handles basic CRUD operations as well as straight SQL queries.
 */

class sql extends model {
	
	// Static pdo database connection
	protected static $conn = false;
	
	// Override component constructor to add database connection code
	public function __construct($options = array()) {
		parent::__construct($options);
		
		// Assume the name of the table is the same as the name of the model
		// unless specified otherwise
		if (!isset($this->options['table'])) {
			$this->options['table'] = $this->options['name'];
		}
		
		// Set up new pdo connection if it doesn't already exist
		if (!self::$conn) {
			self::$conn = new PDO(
				$this->options['dbtype'].':host='.$this->options['host'].';dbname='.$this->options['dbname'].';port='.$this->options['port'],
				$this->options['username'],
				$this->options['password']);
			
			self::$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			
			// Turn on error reporting for pdo if framework debug is enabled
			if (sq::config('debug')) {
				self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
		}
	}
	
	// Returns only alphanumeric characters and underscores. A whitelist of
	// values to not sanitize can be passed in as a second argument.
	public function sanitize($value, $whitelist = array()) {
		
		// If an array is passed in sanitize every key
		if (is_array($value)) {
			foreach ($value as $key => $item) {
				$value[$key] = $this->sanitize($item, $whitelist);
			}
			
			return $value;
		}
		
		// If value is in whitelist return whitelisted value
		if (in_array($value, $whitelist)) {
			return $value;
		}
		
		// Remove all characters except alphnumeric and underscores
		return preg_replace('/[^A-Za-z0-9_]/', '', $value);
	}
	
	// Create a new record in the model
	public function create($data = null) {
		
		// Unset a numberic id key if one exists
		if (empty($this->data['id']) || is_numeric($this->data['id'])) {
			unset($this->data['id']);
		}
		
		$this->set($data);
		
		// Mark record as belonging to the current user if marked as a user
		// specific model
		if ($this->options['user-specific'] && !isset($this->data['users_id'])) {
			$this->data['users_id'] = sq::auth()->user->id;
		}
		
		$values = array();
		foreach ($this->sanitize(array_keys($this->data)) as $key) {
			$values[] = ":$key";
		}
		
		$columns = implode(',', $this->sanitize(array_keys($this->data)));
		$values = implode(',', $values);
		
		$query = 'INSERT INTO '.$this->sanitize($this->options['table'])."
			($columns) VALUES ($values)";
		
		if ($this->checkDuplicate($this->data)) {
			$this->query($query, $this->data);
		}
		
		return $this;
	}
	
	/**
	 * Reads values from table and sets them to the model
	 *
	 * Accepts an optional array of columns to read from the table. Reads
	 * records matching the where statement from the table. Results are set
	 * directly to the model if limit is true or set as a list of model objects
	 * if limit is false.
	 */
	public function read($values = null) {
		$values = $this->sanitize($values);
		
		if (is_array($values)) {
			$values = implode(',', $values);
		} else {
			$values = '*';
		}
		
		$query = "SELECT $values FROM ".$this->sanitize($this->options['table']);
		
		$query .= $this->parseWhere();
		$query .= $this->parseOrder();
		$query .= $this->parseLimit();
		
		return $this->query($query);
	}
	
	// Update rows in table matching the where statement
	public function update($data = null, $where = null) {
		$this->set($data);
		
		if ($where) {
			$this->where($where);
		}
		
		$this->updateDatabase();
		$this->onRelated('update');
		
		return $this;
	}
	
	// Delete rows in table matching where statement
	public function delete($where = null) {
		if ($where) {
			$this->where($where);
		}
		
		$query = 'DELETE FROM '.$this->sanitize($this->options['table']);
		
		$query .= $this->parseWhere();
		$query .= $this->parseLimit();
		
		$this->limit()->onRelated('delete');
		$this->query($query);
		$this->data = array();
		
		return $this;
	}
	
	/**
	 * Add a straight SQL where query
	 * 
	 * Chainable method that allows a straight sql where query to be used for
	 * advanced searches that are too much for the where method.
	 *
	 * WARNING: raw where statements are executed 'as is' without any safety
	 * checking.
	 */
	public function whereRaw($query) {
		$this->options['where-raw'] = $query;
		
		return $this;
	}
	
	/**
	 * Returns an empty model
	 *
	 * schema() sets the model to empty state. It sets all the values in the
	 * model to null. Useful for making create forms or other actions where
	 * having null data is necessary.
	 */
	public function schema() {
		return $this->query('SHOW COLUMNS FROM '.$this->sanitize($this->options['table']));
	}
	
	/**
	 * Checks if table exists
	 *
	 * exists() checks to see of the referenced table exists for the model and 
	 * returns a boolean.
	 */
	public function exists() {
		try {
			self::$conn->query('SELECT 1 FROM '.$this->sanitize($this->options['table']).' LIMIT 1');
		} catch (Exception $e) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Basic create table functionality
	 *
	 * Makes a table with the passed in schema or schema from the model options.
	 * Schema is an associative array column names as keys and SQL definition as
	 * values. An auto incrementing id column is added if none is specified.
	 */
	public function make($schema = null) {
		
		// Skip if the table already exists
		if ($this->exists()) {
			return $this;
		}
		
		if (!$schema) {
			$schema = $this->options['schema'];
		} elseif (is_string($schema)) {
			$schema = sq::config($schema);
		}
		
		$query = 'CREATE TABLE '.$this->sanitize($this->options['table']).' (';
		
		if (!array_key_exists('id', $schema)) {
			$query .= 'id INT(11) NOT NULL AUTO_INCREMENT, ';
		}
		
		foreach ($schema as $key => $val) {
			$schema[$key] = $key.' '.$val;
		}
		
		$query .= implode(',', $schema);
		$query .= ', PRIMARY KEY (id))';
		
		return $this->query($query);
	}
	
	// Returns count of records matched by the where query
	public function count() {
		$query = 'SELECT COUNT(*) FROM '.$this->sanitize($this->options['table']);
		$query .= $this->parseWhere();
		
		return self::$conn->query($query)->fetchColumn();
	}
	
	// Execute a straight mySQL query. Used behind the scenes by all the CRUD
	// interactions.
	public function query($query, $data = array()) {
		if ($this->options['debug']) {
			view::debug($query);
			view::debug($data);
		}
		
		try {
			$handle = self::$conn->prepare($query);
			$handle->setFetchMode(PDO::FETCH_ASSOC);
			
			foreach ($data as $key => $val) {
				if ($val === null) {
					$handle->bindValue(":$key", null, PDO::PARAM_NULL);
				} else {
					$handle->bindValue(":$key", $val);
				}
			}
			
			$handle->execute();
			
			if (strpos($query, 'SELECT') !== false) {
				$this->selectQuery($handle);
			} elseif (strpos($query, 'INSERT') !== false) {
				$this->insertQuery();
			} elseif (strpos($query, 'SHOW COLUMNS') !== false) {
				$this->showColumnsQuery($handle);
			}
			
			return $this;
		} catch (Exception $e) {
			if (sq::config('debug')) {
				echo $e;
				echo 'DIED!';
				echo $query;
				print_r($data);
			} else {
				sq::error('404');
			}
		}
	}
	
	// Update some basic data to the model after inserting into SQL
	private function insertQuery() {
		
		// When inserting always stick the last inserted id into the model
		$this->data['id'] = self::$conn->lastInsertId();
	}
	
	// Insert data into the model from the query result. For single queries add
	// the data to the current model otherwise create child models and add them
	// as a list.
	private function selectQuery($handle) {
		if ($this->isSingle()) {
			$row = $handle->fetch();
			
			if ($row) {
				$this->data = $row;
			}
		} else {
			while ($row = $handle->fetch()) {
				
				// Create child model
				$model = sq::model($this->options['name'], array(
					'use-layout' => false,
					'load-relations' => $this->options['load-relations']
				))->where($row['id']);
				
				$model->data = $row;
				
				// Mark child model in post read state
				$model->isRead = true;
				
				$this->data[] = $model;
			}
		}
		
		// Mark the model in post read state
		$this->isRead = true;
		
		// Call relation setup if enabled
		if ($this->options['load-relations']) {
			$this->relateModel();
		}
	}
	
	// Sets the model to the equivelent of an empty record with the columns as
	// keys but no values
	private function showColumnsQuery($handle) {
		$columns = array();
		while ($row = $handle->fetch()) {
			$columns[$row['Field']] = null;
		}
		
		$this->set($columns);
	}
	
	// Utility function to update data in the database from what is in the model
	private function updateDatabase() {
		$data = array_diff_key($this->data, array_flip(array('id', 'created', 'edited')));
		$query = 'UPDATE '.$this->sanitize($this->options['table']);
				
		$set = array();
		foreach ($data as $key => $val) {
			$key = $this->sanitize($key);
			$set[] = "$key = :$key";
		}
		
		$query .= ' SET '.implode(',', $set);
		$query .= $this->parseWhere();
		
		if (!empty($set)) {
			$this->query($query, $data);
		}
	}
	
	// Generates SQL order statement
	private function parseOrder() {
		if ($this->options['order'] && !$this->isSingle()) {
			return ' ORDER BY '.$this->sanitize($this->options['order']).'
				'.$this->sanitize($this->options['order-direction']).', id ASC';
		}
	}
	
	// Generates SQL where statement from array
	private function parseWhere() {
		
		// If model represents a record and no where statement is applied assume
		// where is for the current model
		if (empty($this->options['where']) && isset($this->data['id'])) {
			$this->where($this->data['id']);
		}
		
		$query = null;
		
		if ($this->options['user-specific']) {
			$this->options['where'] += array('users_id' => sq::auth()->user->id);
		}
		
		if ($this->options['where']) {
			
			$i = 0;
			foreach ($this->options['where'] as $key => $val) {
				if ($i++) {
					$query .= ' '.$this->sanitize($this->options['where-operation']).' ';
				} else {
					$query .= ' WHERE ';
				}
				
				if (is_array($val)) {
					$query .= '(';
					$j = 0;
					
					foreach ($val as $param) {
						if ($j++) {
							$query .= ' OR ';
						}
						
						$query .= $this->sanitize($key).' = '.self::$conn->quote($param);
					}
					
					$query .= ')';
				} else {
					$query .= $this->sanitize($key).' = '.self::$conn->quote($val);
				}
			}
		}
		
		$query .= $this->parseWhereRaw();
		
		return $query;
	}
	
	// Adds straight SQL where statement to the model
	private function parseWhereRaw() {
		$query = null;
		
		if ($this->options['where-raw']) {
			if ($this->options['where']) {
				$query .= ' '.$this->sanitize($this->options['where-operation']).' ';
			} else {
				$query .= ' WHERE ';
			}
			
			$query .= $this->options['where-raw'];
		}
		
		return $query;
	}
	
	// Generates SQL limit statement
	private function parseLimit() {
		if ($this->options['limit']) {
			$limit = $this->sanitize($this->options['limit']);
			
			if (is_array($limit)) {
				$limit = implode(',', $limit);
			}
			
			return ' LIMIT '.$limit;
		}
	}
}

?>