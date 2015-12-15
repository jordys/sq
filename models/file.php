<?php

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
		$this->where($this->data['file']);
		$this->set($this->getFileProperties($this->data['file']));
		
		file_put_contents($this->getPath(), $this->data['content']);
		
		return $this;
	}
	
	public function read($values = '*') {
		if (isset($this->options['where']['id'])) {
			$this->data = $this->getFileProperties($this->options['where']['id'], $values);
			
			if ($this->options['read-content'] && file_exists($this->getPath())) {
				$this->data['content'] = file_get_contents($this->getPath());
			}
			
			if ($this->options['load-relations'] === true) {
				$this->relateModel();
			}
		} else {
			$this->readDirectory();
		}
		
		$this->isRead = true;
		
		if (!$this->isSingle()) {
			$this->order($this->options['order'], $this->options['order-direction']);
		}
		
		return $this;
	}
	
	public function update($data = null, $where = null) {
		
		// Handle shorthand to update only file content
		if (is_string($data)) {
			$this->data['content'] = $data;
		}
		
		if ($where) {
			$this->where($where);
		}
		
		// Get values from the current file if they haven't already been read
		if (!$this->isRead) {
			$this->read();
		}
		
		return $this->delete()->create($data);
	}
	
	public function delete($where = null) {
		if ($where) {
			$this->where($where);
		}
		
		unlink($this->getPath());
		
		return $this;
	}
	
	public function schema() {
		$this->data = array(
			'content' => null,
			'file' => null,
			'name' => null,
			'extension' => null,
			'id' => null
		);
	}
	
	private function getPath() {
		return $this->options['path'].'/'.$this->options['where']['id'];
	}
	
	private function readDirectory() {
		$handle = opendir($this->options['path']);		
		while (($file = readdir($handle)) !== false) {
			
			// Guard against directory files
			if ($file == '..' || $file[0] == '.' || is_dir($this->options['path'].'/'.$file)) {
				continue;
			}
			
			$fileData = $this->getFileProperties($file);
			
			if ($this->checkMatch($fileData)) {
				$model = sq::model($this->options['name'], array('use-layout' => false))
					->limit();
				
				$model->options['load-relations'] = $this->options['load-relations'];
				
				if ($this->options['read-content'] == 'always' || ($this->isSingle() && $this->options['read-content'] == 'single')) {
					$model->where($file)->read();
				} else {
					$model->set($fileData);
					
					// Call relation setup if enabled otherwise pass the
					// disabled flag down the line
					if ($model->options['load-relations']) {
						$model->relateModel();
					}
				}
				
				$this->data[] = $model;
			}
		}
		
		closedir($handle);
		
		$this->limitItems();
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
	
	private function checkMatch($fileData) {
		if (empty($this->options['where'])) {
			return true;
		}
		
		$status = false;
		foreach ($this->options['where'] as $key => $val) {
			if ($fileData[$key] == $val) {
				$status = true;
				
				if ($this->options['where-operation']) {
					return true;
				}
			}
		}
		
		return $status;
	}
	
	private function getFileProperties($file, $values = '*') {
		if ($values == '*') {
			$values = array('file', 'name', 'extension', 'id');
		}
		
		$properties = array();
		foreach ($values as $val) {
			switch ($val) {
				case 'id':
				case 'file':
					$property = $file;
					break;
				case 'name':
					$property = pathinfo($file, PATHINFO_FILENAME);
					break;
				case 'extension':
					$property = strtolower(pathinfo($file, PATHINFO_EXTENSION));
					break;
			}
			
			$properties[$val] = $property;
		}
		
		return $properties;
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
		
		$this->set($this->getFileProperties($this->data['file'], $name));
	}
}

?>