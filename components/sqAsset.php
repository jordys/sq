<?php

/**
 * Asset management
 *
 * This class contains methods for asset management. It copies assets from the 
 * app module and framework asset folders and copies them to the app built 
 * folder.
 */

abstract class sqAsset {
	
	// Use asset in a project. Makes the asset if it does not exist and returns
	// the url of the asset.	
	public static function load($path) {
		if (!self::check($path)) {
			self::make($path);
		}
		
		return self::path($path);
	}
	
	// Finds the asset file and copies it to the built folder.
	public static function make($path) {
		$assetPath = 'assets/'.$path;
		$buildPath = self::path($path, 'file');
		
		// Top level directory may be a folder or can refer to a module/assets
		// folder.
		$fragments = explode('/', $path);
		$module = $fragments[0];
		array_shift($fragments);
		$path = implode('/', $fragments);
		$modulePath = 'modules/'.$module.'/assets/'.$path;
		
		// Create built directory if it does not exist.
		if (!file_exists(sq::root().'built')) {
			mkdir(sq::root().'built');
		}
		
		// Directories searched for the asset in order: app/assets,
		// app/<module>/assets, sq/assets, sq/<module>/assets.
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
	
	// Check if asset exists and is not expired.
	public static function check($path) {
		if (file_exists(self::path($path, 'file'))) {
			return true;
		} else {
			return false;
		}
	}
	
	// Returns the md5 path of an asset.
	public static function path($path, $type = 'url') {
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		
		if ($ext) {
			$ext = '.'.$ext;
		}
		
		$path = 'built/'.md5($path.sq::config('asset-revision')).$ext;
		
		if ($type == 'file') {
			return sq::root().$path;
		} else {
			return sq::base().$path;
		}
	}
	
	// Utility function to copy and entire folder recursively.
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