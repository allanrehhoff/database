<?php
	spl_autoload_register(function($class) {
		$filename = __DIR__.DIRECTORY_SEPARATOR.str_replace("\\", DIRECTORY_SEPARATOR, $class).".class.php";
		if(is_readable($filename) && !class_exists($class)) {
			require_once $filename;
		}
	}, true, true);