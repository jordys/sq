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
	
	public function __construct($options = null) {
		$this->options = $options;
		
		// Layout can be defined in options as well as in the class
		if (isset($options['layout'])) {
			$this->layout = $options['layout'];
		}
		
		// If a view is defined for layout generate it as a view
		if ($this->layout) {
			$this->layout = sq::view($this->layout);
			$this->layout->layout = false;
		}
		
		$this->connect();
		$this->init();
	}
	
	// Constructor that also database connection
	public function connect() {
		
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
			
			// Turn on error reporting for pdo if framework debug is enabled
			if (sq::config('debug')) {
				self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
		}
	}
	
	// Takes a raw mysql where query
	public function whereRaw($query) {
		$this->options['where-raw'] = $query;
		
		return $this;
	}
	
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
	
	public function exists() {
		try {
			$result = self::$conn->query('SELECT 1 FROM '.$this->options['table'].' LIMIT 1');
		} catch (Exception $e) {
			return false;
		}
		
		return $result !== false;
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
		
		$this->limit();
		
		if (isset($data['id']) && is_numeric($data['id'])) {
			unset($data['id']);
		}
		
		$values = array();
		foreach ($this->data as $key => $val) {
			$values[] = ":$key";
		}
		
		$columns = implode(',', array_keys($data));
		$values = implode(',', $values);
		
		$query = 'INSERT INTO '.$this->options['table']." ($columns) 
			VALUES ($values)";
		
		if ($this->checkDuplicate($data)) {
			$this->query($query, $data);
		}
		
		// Set the where statement to the id to allow an immediate read
		// following the create
		$this->where($this->id);
		
		return $this;
	}
	
	public function update($data = null, $where = null) {
		if ($data) {
			$this->set($data);
		
			if ($where) {
				$this->where($where);
			}
		}
		
		if (empty($this->options['where']) && $data['id']) {
			$this->where($data['id']);
		}
		
		unset($this->data['id']);
		
		$this->updateDatabase($this->data);
		$this->updateRelated();
		
		$loadRelations = $this->options['load-relations'];
		$this->options['load-relations'] = false;
		
		$this->read('id');
		
		$this->options['load-relations'] = $loadRelations;
		
		return $this;
	}
	
	public function delete($where = null) {
		if ($where) {
			$this->where($where);
		}
		
		$query = 'DELETE FROM '.$this->options['table'];
		
		$query .= $this->parseWhere();
		$query .= $this->parseLimit();
		
		$this->deleteRelated();
		$this->query($query);
		
		return $this;
	}
	
	public function query($query, $data = array()) {
		if ($this->options['debug']) {
			echo $query;
		}
		
		try {
			$handle = self::$conn->prepare($query);
			$handle->setFetchMode(PDO::FETCH_ASSOC);
			$handle->execute($data);
			
			if (strpos($query, 'SELECT') !== false) {
				$this->selectQuery($handle);
			} elseif(strpos($query, 'INSERT') && $this->options['limit'] === true) {
				
				// When inserting always stick the last inserted id into the 
				// model
				$this->id = self::$conn->lastInsertId();
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
	
	public function count() {
		$query = "SELECT COUNT(*) FROM ".$this->options['table'];
		$query .= $this->parseWhere();
		
		$handle = self::$conn->query($query);
		return $handle->fetchColumn();
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