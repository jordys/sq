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
	
	// Constructor that also database connection
	public function init() {
		
		// Assume the name of the table is the same as the name of the model 
		// unless specified otherwise
		if (!isset($this->options['table'])) {
			$this->options['table'] = $this->options['name'];
		}
		
		// Set up new pdo connection if it doesn't already exist
		if (!self::$conn) {
			self::$conn = new PDO(
				$this->options['dbtype'].':host='.$this->options['host'].';dbname='.$this->options['dbname'],
				$this->options['username'],
				$this->options['password']);
			
			// Turn on error reporting for pdo if framework debug is enabled
			if (sq::config('debug')) {
				self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
		}
	}
	
	// Overrides the standard __get() method in sqComponent to stripslashes on
	// string properties before returning them.
	public function __get($name) {
		if (is_string($this->data[$name])) {
			return stripslashes($this->data[$name]);
		} else {
			return $this->data[$name];
		}
	}
	
	public function read($values = '*') {
		if (is_array($values)) {
			$values = implode(',', $values);
		}
		
		$query = "SELECT $values FROM ".$this->options['table'];
		
		$query .= $this->parseWhere($this->where);
		$query .= $this->parseOrder($this->order, $this->orderDirection);
		$query .= $this->parseLimit($this->limit);
		
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
	
	public function create($data = false) {
		if (!$data) {
			$data = $this->toArray($data);
		}
		
		$this->limit();
		
		if (isset($data['id']) && is_numeric($data['id'])) {
			unset($data['id']);
		}
		
		$values = array();
		foreach ($data as $key => $val) {
			$values[] = ":$key";
		}
		
		$columns = implode(',', array_keys($data));
		$values = implode(',', $values);
		
		$query = 'INSERT INTO '.$this->options['table']." ($columns) 
			VALUES ($values)";
		
		if ($this->checkDuplicate($data)) {
			$this->query($query, $data);
		}
		
		return $this;
	}
	
	public function update($data = false, $where = false) {
		if ($data) {
			$this->set($data);
		}
		
		if ($where) {
			$this->where($where);
		}
		
		unset($this->data['id']);
		
		$this->updateDatabase($this->data);
		$this->updateRelated();
		$this->read('id');
		
		return $this;
	}
	
	public function delete($where = false) {
		if ($where) {
			$this->where($where);
		}
		
		$query = 'DELETE FROM '.$this->options['table'];
		
		$query .= $this->parseWhere($this->where);
		$query .= $this->parseLimit($this->limit);
		
		$this->deleteRelated();
		$this->query($query);
		
		return $this;
	}
	
	public function query($query, $data = array()) {
		try {
			$handle = self::$conn->prepare($query);
			$handle->execute($data);
			
			if ($this->limit === true) {
				$this->id = self::$conn->lastInsertId();
			}
			
			if (strpos($query, 'SELECT') !== false) {
				$handle->setFetchMode(PDO::FETCH_ASSOC);
				
				$data = array();
				if ($this->limit === true) {
					$data = $handle->fetch();
				} else {
					$i = 0;
					while ($row = $handle->fetch()) {
						if ($this->limit > $i || $this->limit == false) {
							$model = sq::model($this->options['table']);
							
							if (isset($row['id'])) {
								$model->where($row['id']);
							}
							
							$model->set($row);
							if ($this->options['load-relations'] === true) {
								$model->relateModel();
							} else {
								$model->options['load-relations'] = false;
							}
							
							$data[] = $model;
							$i++;
						} else {
							break;
						}
					}
				}
			} elseif (strpos($query, 'SHOW COLUMNS') !== false) {
				$handle->setFetchMode(PDO::FETCH_ASSOC);
				
				$columns = array();
				while ($row = $handle->fetch()) {
					$columns[$row['Field']] = null;
				}
				
				$this->set($columns);
			}
			
			$this->set($data);
			
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
		
		if (!empty($this->where)) {
			$query .= $this->parseWhere($this->where);
		} elseif (isset($data['id'])) {
			$query .= $this->parseWhere(array('id' => $data['id']));
		}
		
		if (!empty($set)) {
			$this->query($query, $data);
		}
	}
	
	private function parseOrder($order, $direction) {
		$direction = strtoupper($direction);
		
		if ($this->order && $this->limit !== true) {
			return " ORDER BY $order $direction, id";
		}
	}
	
	private function parseWhere($data) {
		$query = null;
		
		if (is_array($data)) {
			$operation = strtoupper($this->whereOperation);
			
			$i = 0;
			foreach ($data as $key => $val) {
				if ($i++ === 0) {
					$query .= ' WHERE ';
				} else {
					$query .= " $operation ";
				}
				
				if (is_array($val)) {
					$j = 0;
					foreach ($val as $item) {
						if ($j++ !== 0) {
							$query .= " $operation ";
						}
						
						$query .= "$key = '$item'";
					}
				} else {
					$query .= "$key = '$val'";
				}
			}
		}
		
		return $query;
	}
	
	private function parseLimit($data) {
		if ($data) {
			return ' LIMIT '.$data;
		}
	}
}

?>