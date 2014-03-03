<?php

abstract class sqAsset extends component {
	public static function load($path) {
		echo md5($path);
	}
	
	public static function rebuild() {
		
	}
	
	public static function make() {
		
	}
	
	public static function check() {
		
	}
}

?>