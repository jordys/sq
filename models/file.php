<?php

class file extends model {
	protected $fileProperties = array('id', 'file', 'image', 'name',
		'extension', 'base', 'path', 'content', 'directory');
	
	// Set memory limit to the value in model config
	public function __construct($options = array()) {
		parent::__construct($options);
		
		ini_set('memory_limit', $this->options['memory-limit']);
	}
	
	public function create($data = null) {
		$this->set($data);
		
		$dir = null;
		if ($this->data['directory']) {
			$dir = $this->data['directory'].'/';
		}
		
		$this->options['id'] = $this->upload($_FILES['file'], $dir);
		
		return $this;
	}
	
	public function read($values = '*') {
		$data = array();
		
		$path = $this->options['path'];
		
		if (isset($this->options['where']['path'])) {
			$path = $this->options['where']['path'];
		}
		
		if (isset($this->options['where']['id'])) {
			$path = $this->options['where']['id'];
		}
		
		if (is_dir($path)) {
			$data = $this->readDirectory($path, $values);
		} else {
			$data = $this->readFile($path, $values);
		}
		
		if ($this->isSingle() && isset($data[0])) {
			$data = $data[0];
		}
		
		$this->set($data);
		
		if ($this->options['load-relations'] === true) {
			$this->relateModel();
		}
		
		return $this;
	}
	
	public function update($data = null, $where = null) {
		if (is_array($data)) {
			$this->set($data);
			
			if ($where) {
				$this->where($where);
			}
		}
		
		$dir = null;
		if (isset($this->data['directory']) && $this->data['directory'] != null) {
			$dir = $this->data['directory'].'/';
		}
		
		if (isset($_FILES['file']) && $_FILES['file']['size'] > 0) {
			$this->delete();
			$this->id = $this->upload($_FILES['file'], $dir);
		}
	}
	
	protected function readDirectory($dir, $values = '*') {
		$data = array();
		
		$handle = opendir($dir);
		while (false !== ($file = readdir($handle))) {
			if ($file != '..' && $file[0] != '.') {
				if (is_dir($dir.$file)) {
					if ($this->options['recursive']) {
						$sub = sq::model($this->options['name']);
						$sub->options['recursive'] = true;
						$sub->where(array('path' => $dir.$file.'/'));
						$sub->read();
						
						$model->{$this->options['name']} = $sub;
					}
				} else {
					$array = $this->readFile($dir.$file, $values);
					
					$model = sq::model($this->options['name']);
					$model->where($array['id']);
					$model->limit();
					
					$model->set($array);
				}
				
				$data[] = $model;
			}
		}
		
		return $data;
	}
	
	protected function checkMatch($array) {
		if ($this->where == null) {
			return true;
		}
		
		foreach ($this->where as $key => $val) {
			if (isset($array[$key]) && $array[$key] == $val) {
				return true;
			}
		}
		
		return false;
	}
	
	public function schema() {
		$this->data = array(
			'id' => '',
			'file' => '',
			'extension' => '',
			'name' => '',
			'path' => '',
			'base' => '',
			'directory' => '',
			'content' => ''
		);
	}
	
	protected function readFile($file, $values = '*') {
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		
		if ($values == '*') {
			$values = $this->fileProperties;
		}
		
		foreach ($values as $val) {
			switch ($val) {
				case 'id':
				case 'image':
				case 'file':
					$property = $file;
					break;
				case 'extension':
					$property = $ext;
					break;
				case 'name':
					$property = basename($file);
					break;
				case 'path':
					$property = $file;
					break;
				case 'base':
					$property = dirname($file);
					break;
				case 'directory':
					$directory = explode('/', $file);
					$property = $directory[count($directory) - 2];
					break;
			}
			
			$properties[$val] = $property;
		}
		
		if ($this->options['read-content']) {
			$content = file_get_contents($file);
			$properties = array_merge($properties, $this->parse($content));
		}
		
		return $properties;
	}
	
	public function upload($file, $path = null, $name = false) {
		if ($name) {
			$path .= $name;
		} else {
			$path .= basename($file['name']);
		}
		
		if ($this->options['resize-x'] && $this->options['resize-y']) {
			$image = new ImageManipulator($file['tmp_name']);
			$image->resample($this->options['resize-x'], $this->options['resize-y']);
			$image->save($this->options['path'].$path, IMAGETYPE_JPEG);
		} else {
			move_uploaded_file($file['tmp_name'], $this->options['path'].$path);
		}
		
		foreach ($this->options['variations'] as $variant => $options) {
			$image = new ImageManipulator($this->options['path'].$path);
			$image->resample($options['width'], $options['height']);
			$image->save($this->options['path'].$variant.'/'.$path, IMAGETYPE_JPEG);
		}
		
		return $this->options['path'].$path;
	}
	
	public function makeVariants() {
		foreach ($this->options['variations'] as $variant => $options) {
			$image = new ImageManipulator($this->id);
			$image->resample($options['width'], $options['height']);
			$image->save($this->options['path'].$variant.'/'.$this->name, IMAGETYPE_JPEG);
		}
	}
	
	public function delete($where = null) {
		if ($where) {
			$this->where($where);
		}
		
		if ($this->options['where']['id'] != null && file_exists(sq::root().$this->options['where']['id'])) {
			unlink(sq::root().$this->options['where']['id']);
		}
	}
	
	public function parse($content) {
		return array('content' => $content);
	}
}

?>