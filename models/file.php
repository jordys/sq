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
	public function __construct($options) {
		parent::__construct($options);

		if (!file_exists($this->options['path'])) {
			mkdir($this->options['path'], 0777, true);
		}
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
	public function read($type = null) {

		// If no where statement is applied and data is set assume the record
		// being altered is the current one
		if (empty($this->options['where']) && isset($this->data['id'])) {
			$this->where($this->data['id']);
		}

		// Loop through the items in the directory
		foreach ($this->readDirectory($type) as $item) {

			// If this is a single record break out of the loop and set the
			// data directly to the model
			if ($this->isSingle()) {
				$this->data = $item;
				break;
			}

			// Set the data to a model and place that model into the collection
			$model = sq::model($this->options['name'], [
				'use-layout' => false,
				'load-relations' => $this->options['load-relations']
			])->where($item['id'])->limit()->set($item);

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
			$data = ['content' => $data];
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
			if (filetype($this->id) == 'file') {
				unlink($this->id);
			} else {
				$files = new RecursiveDirectoryIterator($this->id, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveIteratorIterator::CHILD_FIRST);
				foreach($files as $file) {
					if ($file->isDir()) {
						rmdir($file->getRealPath());
					} else {
						unlink($file->getRealPath());
					}
				}
				rmdir($this->id);
			}
		} else {
			foreach ($this->data as $item) {
				$item->delete();
			}
		}

		$this->data = [];

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
		return count($this->readDirectory(null, true));
	}

	/**
	 * Uploads a file into the model directory
	 *
	 * The file can be specified by key to read from the PHP $_FILES superglobal
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
			$name = basename($file['name']);
		}

		move_uploaded_file($file['tmp_name'], $this->options['path'].'/'.$name);

		return $this->find($name);
	}

	/**
	 * Returns an empty model
	 *
	 * The properties of the model are well defined unlike other model
	 * implementations. The properties to set to the model are specified below.
	 */
	public function columns() {
		$this->limit();

		$this->data = [
			'content' => null,
			'file' => null,
			'name' => null,
			'extension' => null,
			'path' => null,
			'url' => null,
			'type' => null,
			'id' => null
		];

		return $this;
	}

	/**
	 * Creates an image variant
	 *
	 * Returns the url of the specified variant. The variant argument can either
	 * be a named variant from the config or an array with w and h keys for
	 * width and height. Variant images are cached in variants subdirectory.
	 *
	 * The optional 'format' key dictates the file format generated, Formats are
	 * gif, png and jpg. By default format will match the master file.
	 *
	 * By default a new variant will only be created if one doesn't already
	 * exist but by passing true to the regenerate argument a new version will
	 * always be created.
	 */
	public function variant($variant, $regenerate = false) {
		if (is_string($variant)) {
			$variant = sq::config('variants/'.$variant);
		}

		// If the variant format isn't specified it will match the master file
		if (empty($variant['format'])) {
			$variant['format'] = $this->extension;
		}

		switch ($variant['format']) {
			case 'gif':
				$format = IMAGETYPE_GIF;
				break;
			case 'png':
				$format = IMAGETYPE_PNG;
				break;
			default:
				$format = IMAGETYPE_JPEG;
				break;
		}

		// Generate the variation if it doesn't already exist
		$variantPath = $this->options['path'].'/variants/'.$variant['w'].'x'.$variant['h'].'/'.$this->data['name'].'.'.$variant['format'];
		if (!file_exists($variantPath) || $regenerate) {
			ini_set('memory_limit', $this->options['memory-limit']);
			$image = new ImageManipulator(sq::root().$this->data['id']);
			$image->resample($variant['w'], $variant['h']);
			$image->save($variantPath, $format);
			unset($image);
		}

		return sq::base().$variantPath;
	}

	// Reads through a directory and returns an array of the file properties
	private function readDirectory($readType = null, $noLimit = null) {
		$data = [];

		$path = $this->options['path'];
		if (!empty($this->options['where']['path'])) {
			$path = $this->options['where']['path'];
		}

		foreach (new DirectoryIterator($path) as $file) {

			// Skip hidden files
			if ($file->getFilename()[0] == '.') {
				continue;
			}

			if (!$readType || $file->getType() == $readType) {
				$extension = $file->getExtension();

				// Use pathinfo here because getExtension isn't in PHP 5.3
				$item = [
					'created' => $file->getCTime(),
					'file' => $file->getFilename(),
					'name' => $file->getBasename('.'.$extension),
					'url' => sq::base().$file->getPathname(),
					'id' => $file->getPathname(),
					'path' => $file->getPath(),
					'type' => $file->getType()
				];

				if ($file->isFile()) {
					if ($this->options['read-content'] == 'always' || ($this->isSingle() && $this->options['read-content'])) {
						$item['content'] = file_get_contents($file->getPathname());
					}
					$item['extension'] = $extension;
				}

				// Skip if where statment isn't a match
				if ($this->checkWhereStatement($item)) {
					$data[] = $item;
				}
			}
		}

		if ($noLimit) {
			return $data;
		}

		return $this->limitItems($data);
	}

	// Utility method to trim the current items in the collection to the
	// correct number
	private function limitItems($items) {
		$limit = $this->options['limit'];

		// Guard against no limit
		if (!$limit) {
			return $items;
		}

		if (is_int($limit)) {
			$limit = [0, $limit];
		}

		return array_slice($items, $limit[0], $limit[1]);
	}

	// Checks if the file read matches the where statument
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
