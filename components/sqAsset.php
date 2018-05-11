<?php

/**
 * Asset component
 *
 * This component handles managing css, javascript and other frontend assets in
 * sq applications. Assets are stores in the framework, module and application
 * assets/ directories and are built into a directory in the application. When
 * in debug model the assets are rebuilt every time.
 *
 * When calling the component it takes the path of the asset to load as the
 * first argument. When the component is used as a string a link to the asset
 * is returned.
 */

abstract class sqAsset extends component {

	// Stores the asset path
	private $path = null;

	// Checks to see if the asset is already built and builds it if necessary
	public function __construct($path, $options) {
		$this->path = $path;

		parent::__construct($options);

		if (!$this->check() || sq::config('debug')) {
			$this->build();
		}
	}

	// Asset returns the file url when treated like a string
	public function render() {
		return sq::base().$this->getFilePath();
	}

	/**
	 * Checks if asset exists
	 *
	 * Returns true if the asset exists.
	 */
	public function check() {
		return file_exists($this->getFilePath());
	}

	/**
	 * Builds asset files
	 *
	 * Searches site directory, module directories and framework directory for
	 * assets. Found assets will be built in that precedence.
	 */
	public function build() {
		$assetPath = 'assets/'.$this->path;
		$buildPath = sq::root().$this->getFilePath();

		$fragments = explode('/', $this->path);
		$module = $fragments[0];
		array_shift($fragments);
		$modulePath = 'modules/'.$module.'/assets/'.implode('/', $fragments);

		// Create built asset directory if it doesn't exist
		$dir = dirname($buildPath);
		if (!is_dir($dir)) {
			mkdir($dir, $this->options['permissions'], true);
		}

		// Directories searched for the asset in order: app/assets,
		// app/<module>/assets, sq/assets, sq/<module>/assets
		if (file_exists(sq::root().$assetPath)) {
			self::recursiveCopy(sq::root().$assetPath, $buildPath);
		} elseif (file_exists(sq::root().$modulePath)) {
			self::recursiveCopy(sq::root().$modulePath, $buildPath);
		} elseif (file_exists(sq::path().$assetPath)) {
			self::recursiveCopy(sq::path().$assetPath, $buildPath);
		} elseif (file_exists(sq::path().$modulePath)) {
			self::recursiveCopy(sq::path().$modulePath, $buildPath);
		}

		return $this;
	}

	// Returns the md5 path of an asset
	private function getFilePath() {
		return $this->options['path'].'/'.md5($this->options['revision']).'/'.$this->path;
	}

	// Utility function to copy directories recursively
	private static function recursiveCopy($path, $destination) {
		if (is_dir($path)) {
			if (!file_exists($destination)) {
				mkdir($destination);
			}

			$handle = opendir($path);
			while (false !== ($file = readdir($handle))) {
				if ($file != '..' && $file[0] != '.') {
					self::recursiveCopy($path.'/'.$file, $destination.'/'.$file);
				}
			}

			closedir($handle);
		} else {
			copy($path, $destination);
		}
	}
}
