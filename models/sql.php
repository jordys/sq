<?php

/**
 * SQL model implementation
 *
 * Generic crud implementation of model to work with SQL databases. The only
 * addition is the query method which allows arbitrary SQL queries to be
 * executed. Uses PDO for the database interaction.
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
	
	/**
	 * Add a straight SQL where query
	 * 
	 * Chainable method that allows a straight sql where query to be used for
	 * advanced searches that are too much for the where method.
	 */
	public function whereRaw($query) {
		$this->options['where-raw'] = $query;
		
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
	public function read($values = '*') {
		if (is_array($values)) {
			$values = implode(',', $values);
		}
		
		$query = "SELECT $values FROM ".$this->options['table'];
		
		$query .= $this->parseWhere();
		$query .= $this->parseOrder();
		$query .= $this->parseLimit();
		
		$this->query($query);
		
		if ($this->options['load-relations'] === true) {
			$this->relateModel();
		}
		
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
		return $this->query('SHOW COLUMNS FROM '.$this->options['table']);
	}
	
	/**
	 * Checks if table exists
	 *
	 * exists() checks to see of the referenced table exists for the model and 
	 * returns a boolean.
	 */
	public function exists() {
		try {
			self::$conn->query('SELECT 1 FROM '.$this->options['table'].' LIMIT 1');
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
		if (!$this->exists()) {
			if (!$schema) {
				$schema = $this->options['schema'];
			}
			
			$query = 'CREATE TABLE '.$this->options['table'].' (';
			
			if (!array_key_exists('id', $schema)) {
				$query .= 'id INT(11) NOT NULL AUTO_INCREMENT, ';
			}
			
			foreach ($schema as $key => $val) {
				$schema[$key] = $key.' '.$val;
			}
			
			$query .= implode(',', $schema);
			$query .= ', PRIMARY KEY (id))';
			
			$this->query($query);
		}
		
		return $this;
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
		foreach ($this->data as $key => $val) {
			$values[] = ":$key";
		}
		
		$columns = implode(',', array_keys($this->data));
		$values = implode(',', $values);
		
		$query = 'INSERT INTO '.$this->options['table']." ($columns) 
			VALUES ($values)";
		
		if ($this->checkDuplicate($this->data)) {
			$this->query($query, $this->data);
		}
		
		return $this;
	}
	
	// Update rows in table matching the where statement
	public function update($data = null, $where = null) {
		$this->set($data);
		
		if ($where) {
			$this->where($where);
		}
		
		$this->limit();
		
		// If no where statement is applied assume the record being updated is
		// the current one
		if (empty($this->options['where']) && $this->data['id']) {
			$this->where($this->data['id']);
		}
		
		$this->read(array('id'));
		
		$this->updateDatabase($this->data);
		$this->onRelated('update');
		
		return $this;
	}
	
	// Delete rows in table matching where statement
	public function delete($where = null) {
		if ($where) {
			$this->where($where);
		}
		
		$query = 'DELETE FROM '.$this->options['table'];
		
		$query .= $this->parseWhere();
		$query .= $this->parseLimit();
		
		$this->onRelated('delete');
		$this->query($query);
		
		return $this;
	}
	
	// Execute a straight mySQL query
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
		$this->id = self::$conn->lastInsertId();
		
		// Set the where statement to the id to allow an immediate read
		// following the create
		$this->where($this->id);
	}
	
	// Insert data into the model from the query result. For single queries add
	// the data to the current model otherwise create child models and add them
	// as a list.
	private function selectQuery($handle) {
		if ($this->options['limit'] === true) {
			$row = $handle->fetch();
			
			if ($row) {
				foreach ($row as $key => $val) {
					if (is_string($val)) {
						$this->$key = stripcslashes($val);
					} else {
						$this->$key = $val;
					}
				}
			}
		} else {
			while ($row = $handle->fetch()) {
				
				// Create child model
				$model = sq::model($this->options['table'])->limit();
				
				foreach ($row as $key => $val) {
					if (is_string($val)) {
						$model->$key = stripcslashes($val);
					} else {
						$model->$key = $val;
					}
				}
				
				// Call relation setup if enabled otherwise pass the disabled
				// flag down the line
				if ($this->options['load-relations']) {
					$model->relateModel();
				} else {
					$model->options['load-relations'] = false;
				}
				
				// Mark child model in post read state
				$model->isRead = true;
				
				$this->data[] = $model;
			}
		}
		
		// Mark the model in post read state
		$this->isRead = true;
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
	private function updateDatabase($data) {
		$query = 'UPDATE '.$this->options['table'];
				
		$set = array();
		foreach ($data as $key => $val) {
			if (!in_array($key, array('id', 'created', 'edited')) && !is_object($val)) {
				$set[] = "$key = :$key";
			} else {
				unset($data[$key]);
			}
		}
		
		$query .= ' SET '.implode(',', $set);
		$query .= $this->parseWhere();
		
		if (!empty($set)) {
			$this->query($query, $data);
		}
	}
	
	// Generates SQL order statement
	private function parseOrder() {
		if ($this->options['order'] && $this->options['limit'] !== true) {
			return " ORDER BY {$this->options['order']} {$this->options['order-direction']}, id ASC";
		}
	}
	
	// Generates SQL where statement from array
	private function parseWhere() {
		$query = null;
		
		if ($this->options['user-specific']) {
			$this->options['where'] += array('users_id' => sq::auth()->user->id);
		}
		
		if ($this->options['where']) {
			
			$i = 0;
			foreach ($this->options['where'] as $key => $val) {
				if ($i++) {
					$query .= " {$this->options['where-operation']} ";
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
						
						$query .= "$key = '$param'";
					}
					
					$query .= ')';
				} else {
					$query .= "$key = '$val'";
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
				$query .= " {$this->options['where-operation']} ";
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
			return ' LIMIT '.$this->options['limit'];
		}
	}
}

?>