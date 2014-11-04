<?php

/**
 * Asset management
 *
 * This class contains methods for asset management. It copies assets from the 
 * app module and framework asset folders and copies them to the app's built 
 * folder.
 */

abstract class sqAsset {
	
	// Use asset in a project. Makes the asset if it does not exist and returns
	// the url of the asset.
	public static function load($path) {
		if (!self::check($path) || sq::config('debug')) {
			self::make($path);
		}
		
		return self::path($path);
	}
	
	// Finds the asset file and copies it to the built folder
	public static function make($path) {
		$assetPath = 'assets/'.$path;
		$buildPath = self::path($path, 'file');
		
		// Top level directory may be a folder or can refer to a module/assets
		// folder.
		$fragments = explode('/', $path);
		$module = $fragments[0];
		array_shift($fragments);
		$modulePath = 'modules/'.$module.'/assets/'.implode('/', $fragments);
		
		// Create built asset directory if it does not exist
		$dir = dirname($buildPath);
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
		
		// Directories searched for the asset in order: app/assets,
		// app/<module>/assets, sq/assets, sq/<module>/assets
		if (file_exists(sq::root().$assetPath)) {
			self::recursiveCopy(sq::root().$assetPath, $buildPath);
		} elseif (file_exists(sq::root().$modulePath)) {
			self::recursiveCopy(sq::root().$modulePath, $buildPath);
		} elseif (file_exists(sq::path().sq::path().$assetPath)) {
			self::recursiveCopy(sq::path().$assetPath, $buildPath);
		} elseif (file_exists(sq::path().$modulePath)) {
			self::recursiveCopy(sq::path().$modulePath, $buildPath);
		}
	}
	
	// Check if asset exists and is not expired
	public static function check($path) {
		if (file_exists(self::path($path, 'file'))) {
			return true;
		} else {
			return false;
		}
	}
	
	// Returns the md5 path of an asset
	public static function path($path, $type = 'url') {
		$path = 'built/'.md5(sq::config('asset-revision')).'/'.$path;
		
		if ($type == 'file') {
			return sq::root().$path;
		} else {
			return sq::base().$path;
		}
	}
	
	// Utility function to copy and entire folder recursively
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

?>