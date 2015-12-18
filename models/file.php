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
	
	public function create($data = null) {
		$this->set($data);
		
		file_put_contents($this->options['path'].'/'.$this->data['file'], $this->data['content']);
		
		return $this->where($this->data['file'])->read();
	}
	
	public function read($values = null) {
		
		// If no where statement is applied and data is set assume the record
		// being altered is the current one
		if (empty($this->options['where']) && isset($this->data['id'])) {
			$this->where($this->data['id']);
		}
		
		foreach (new DirectoryIterator($this->options['path']) as $file) {
			
			// Skip against directories
			if (!$file->isFile()) {
				continue;
			}
			
			$data = array(
				'file' => $file->getFilename(),
				'name' => $file->getBasename('.'.$file->getExtension()),
				'extension' => $file->getExtension(),
				'path' => $file->getPath(),
				'url' => sq::base().$file->getPathname(),
				'id' => $file->getFilename()
			);
			
			// Skip if where statment isn't a match
			if (!$this->checkWhereStatement($data)) {
				continue;
			}
			
			if ($this->options['read-content'] == 'always' || ($this->isSingle() && $this->options['read-content'])) {
				$data['content'] = file_get_contents($this->options['path'].'/'.$data['file']);
			}
			
			if (is_array($values)) {
				$data = array_intersect_key($data, array_flip($values));
			}
			
			// If this is a single record break out of the loop and set the data
			// directly to the model
			if ($this->isSingle()) {
				$this->data = $data;
				break;
			}
			
			$model = sq::model($this->options['name'], array(
				'use-layout' => false,
				'load-relations' => $this->options['load-relations']
			))->where($file)->limit()->set($data);
			
			$model->isRead = true;
			
			$this->data[] = $model;
		}
		
		$this->isRead = true;
		
		if (!$this->isSingle()) {
			$this->limitItems();
			
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
	
	public function delete($where = null) {
		if ($where) {
			$this->where($where);
		}
		
		// If no where statement is applied assume the record being updated is
		// the current one
		if (empty($this->options['where']) && $this->data['id']) {
			$this->where($this->data['id']);
		}
		
		if (!$this->isRead) {
			$this->read();
		}
		
		if ($this->isSingle()) {
			unlink($this->options['path'].'/'.$this->data['file']);
			$this->onRelated('delete');
		} else {
			foreach ($this->data as $item) {
				$item->delete();
			}
		}
		
		$this->data = array();
		
		return $this;
	}
	
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
	}
	
	private function limitItems() {
		$limit = $this->options['limit'];
		
		// Guard against no limit
		if (!$limit) {
			return;
		}
		
		if (is_int($limit)) {
			$limit = array(0, $limit);
		}
		
		$this->data = array_slice($this->data, $limit[0], $limit[1]);
	}
	
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
	
	public function count() {
		$fileIterator = new FilesystemIterator($this->options['path'], FilesystemIterator::SKIP_DOTS);
		return iterator_count($fileIterator);
	}
	
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
}

?>