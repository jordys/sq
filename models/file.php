<?php

/**
 * File model implementation
 *
 * Provides an eloquent object oriented interface for managing files as objects.
 * Each file within the directory specified in the 'path' option is treated as a
 * record and can be manipulated with the CRUD method.
 *
 * The contents of the files can be read by enabling the 'read-content' option.
 * The id of a file record is the same as it's filename.
 */

class file extends model {
	
	// Set memory limit to the value in model config and make sure the model
	// directory exists
	public function __construct($options = array()) {
		parent::__construct($options);
		
		if (!file_exists($this->options['path'])) {
			mkdir($this->options['path'], true);
		}
		
		ini_set('memory_limit', $this->options['memory-limit']);
	}
	
	/**
	 * Create a file
	 *
	 * Adds a file to the model directory. Only file and content properties are
	 * needed.
	 */
	public function create($data = null) {
		$this->set($data);
		
		file_put_contents($this->options['path'].'/'.$this->data['file'], $this->data['content']);
		
		return $this->where($this->data['file'])->read();
	}
	
	/**
	 * Read files from the directory that match the where statment
	 *
	 * The directory is iteratoed through looking for matches to the where
	 * option and then the number of records specified by the limit option are
	 * returned. The values argument accepts an optional array to specify what
	 * file properties to add to the record object.
	 */
	public function read($values = null) {
		
		// If no where statement is applied and data is set assume the record
		// being altered is the current one
		if (empty($this->options['where']) && isset($this->data['id'])) {
			$this->where($this->data['id']);
		}
		
		// Loop through the items in the directory
		foreach ($this->readDirectory() as $item) {
			if ($this->options['read-content'] == 'always' || ($this->isSingle() && $this->options['read-content'])) {
				$item['content'] = file_get_contents($this->options['path'].'/'.$item['file']);
			}
			
			if (is_array($values)) {
				$item = array_intersect_key($item, array_flip($values));
			}
			
			// If this is a single record break out of the loop and set the data
			// directly to the model
			if ($this->isSingle()) {
				$this->data = $item;
				break;
			}
			
			// Set the data to a model and place that model into the collection
			$model = sq::model($this->options['name'], array(
				'use-layout' => false,
				'load-relations' => $this->options['load-relations']
			))->where($item['id'])->limit()->set($item);
			
			$model->isRead = true;
			
			$this->data[] = $model;
		}
		
		$this->isRead = true;
		
		if (!$this->isSingle()) {
			if ($this->options['order']) {
				$this->order($this->options['order'], $this->options['order-direction']);
			}
		}
		
		// Call relation setup if enabled
		if ($this->options['load-relations']) {
			$this->relateModel();
		}
		
		return $this;
	}
	
	/**
	 * Update an existing file
	 *
	 * The can be used to change a file's name, it's content or both. It is
	 * implemented by calling a delete then a read. Accepts two optional
	 * 'shorthand' parameters to pass in the data and a where statement.
	 */
	public function update($data = null, $where = null) {
		
		// Handle shorthand to update only file content
		if (is_string($data)) {
			$data = array('content' => $data);
		}
		
		if ($where) {
			$this->where($where);
		}
		
		if (!$this->isRead) {
			$this->read();
		}
		
		$this->set($data);
		
		$data = $this->data;
		
		$this->delete()->create($data);
		$this->onRelated('update');
		
		return $this;
	}
	
	/**
	 * Delete file(s) from directory
	 *
	 * Deletes all files matching the current where statement. Accepts a where
	 * array option as a 'shorthand' argument.
	 */
	public function delete($where = null) {
		if ($where) {
			$this->where($where);
		}
		
		if (!$this->isRead) {
			$this->read();
		}
		
		$this->onRelated('delete');
		
		if ($this->isSingle()) {
			unlink($this->options['path'].'/'.$this->data['file']);
		} else {
			foreach ($this->data as $item) {
				$item->delete();
			}
		}
		
		$this->data = array();
		
		return $this;
	}
	
	/**
	 * Counts records
	 *
	 * Returns the number of items in the collection matched by the where 
	 * statement. Returns the full number counted not just the first page when
	 * using pagination.
	 */
	public function count() {
		return count($this->readDirectory());
	}
	
	/**
	 * Uploads a file into the model directory
	 *
	 * The file can be specified by key to read from the php $_FILES superglobal
	 * or as a file object. A name can optionally be specified for the uploaded
	 * file. If a name isn't specified the existing file name will be used.
	 */
	public function upload($file = null, $name = null) {
		
		// File can either be a file array or a string to look for in the files
		// array
		if (is_string($file)) {
			$file = $_FILES[$file];
		}
		
		// Get name from upload file if it isn't specified
		if (!$name) {
			$name .= basename($file['name']);
		}
		
		move_uploaded_file($file['tmp_name'], $this->options['path'].'/'.$name);
		
		$this->read($name);
	}
	
	/**
	 * Returns an empty model
	 *
	 * The properties of the model are well defined unlike other model
	 * implementations. The properties to set to the model are specified below.
	 */
	public function schema() {
		$this->data = array(
			'content' => null,
			'file' => null,
			'name' => null,
			'extension' => null,
			'path' => null,
			'url' => null,
			'id' => null
		);
		
		return $this;
	}
	
	// Reads through a directory and returns an array of the file properties
	private function readDirectory() {
		$data = array();
		foreach (new DirectoryIterator($this->options['path']) as $file) {
			
			// Skip directories
			if (!$file->isFile()) {
				continue;
			}
			
			$item = array(
				'file' => $file->getFilename(),
				'name' => $file->getBasename('.'.$file->getExtension()),
				'extension' => $file->getExtension(),
				'path' => $file->getPath(),
				'url' => sq::base().$file->getPathname(),
				'id' => $file->getFilename()
			);
			
			// Skip if where statment isn't a match
			if ($this->checkWhereStatement($item)) {
				$data[] = $item;
			}
		}
		
		return $this->limitItems($data);
	}
	
	// Utility method to trim the current items in the collection to the currect
	// number
	private function limitItems($items) {
		$limit = $this->options['limit'];
		
		// Guard against no limit
		if (!$limit) {
			return $items;
		}
		
		if (is_int($limit)) {
			$limit = array(0, $limit);
		}
		
		return array_slice($items, $limit[0], $limit[1]);
	}
	
	// Checks if the file read matches the where option
	private function checkWhereStatement($fileData) {
		if (empty($this->options['where'])) {
			return true;
		}
		
		foreach ($this->options['where'] as $key => $val) {
			if ($fileData[$key] == $val) {
				$status = true;
				
				if ($this->options['where-operation'] == 'OR') {
					return true;
				}
			} else {
				$status = false;
			}
		}
		
		return $status;
	}
}

?>