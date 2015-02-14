<?php

/**
 * SQL model implementation
 *
 * Generic crud implementation of model to work with sql databases. The only
 * addition is the query method which allows arbitrary sql queries to be
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
	 * Add a straight sql where query
	 * 
	 * Chainable method that allows a straight sql where query to be used for
	 * advanced searches that are too much for the where method.
	 */
	public function whereRaw($query) {
		$this->options['where-raw'] = $query;
		
		return $this;
	}
	
	/**
	 * Reads values from database and sets them to the model
	 *
	 * Accepts an optional array of columns to read from the database. Reads
	 * records matching the where statement from the database. Results are set
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
		
		$this->isRead = true;
		
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
			$result = self::$conn->query('SELECT 1 FROM '.$this->options['table'].' LIMIT 1');
		} catch (Exception $e) {
			return false;
		}
		
		return true;
	}
	
	public function make($schema) {
		if (!$this->exists()) {
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
	
	public function create($data = null) {
		if (is_array($data)) {
			$this->set($data);
		}
		
		if (empty($this->data['id']) || is_numeric($this->data['id'])) {
			unset($this->data['id']);
		}
		
		if ($this->options['user-specific'] && !isset($this->data['users_id'])) {
			$this->data['users_id'] = auth::user()->id;
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
	
	public function update($data = null, $where = null) {
		if ($data) {
			$this->set($data);
		
			if ($where) {
				$this->where($where);
			}
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
	
	public function query($query, $data = array()) {
		if ($this->options['debug']) {
			echo $query."\n";
			print_r($data);
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
				$this->insertQuery($handle);
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
	
	private function insertQuery($handle) {
		
		// When inserting always stick the last inserted id into the model
		$this->id = self::$conn->lastInsertId();
		
		// Set the where statement to the id to allow an immediate read
		// following the create
		$this->where($this->id);
	}
	
	private function selectQuery($handle) {
		if ($this->options['limit'] === true) {
			$row = $handle->fetch();
			
			if ($row) {
				array_map('stripslashes', $row);
				$this->set($row);
			}
		} else {
			while ($row = $handle->fetch()) {
				$model = sq::model($this->options['table']);
				
				array_map('stripslashes', $row);
				$model->set($row);
				
				if ($this->options['load-relations']) {
					$model->relateModel();
				} else {
					$model->options['load-relations'] = false;
				}
				
				$this->data[] = $model;
			}
		}
	}
	
	private function showColumnsQuery($handle) {
		$columns = array();
		while ($row = $handle->fetch()) {
			$columns[$row['Field']] = null;
		}
		
		$this->set($columns);
	}
	
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
	
	private function parseOrder() {
		if ($this->options['order'] && $this->options['limit'] !== true) {
			return " ORDER BY {$this->options['order']} {$this->options['order-direction']}, id ASC";
		}
	}
	
	private function parseWhere() {
		$query = null;
				
		if ($this->options['user-specific']) {
			$this->options['where'] += array('users_id' => auth::user()->id);
		}
		
		if ($this->options['where']) {
			
			$i = 0;
			foreach ($this->options['where'] as $key => $val) {
				if ($i++) {
					$query .= " {$this->options['where-operation']} ";
				} else {
					$query .= ' WHERE ';
				}
				
				$query .= "$key = '$val'";
			}
		}
		
		$query .= $this->parseWhereRaw();
		
		return $query;
	}
	
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
	
	private function parseLimit() {
		if ($this->options['limit']) {
			return ' LIMIT '.$this->options['limit'];
		}
	}
}

?>