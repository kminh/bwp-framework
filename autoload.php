<?php

// not autoloading anything actually
if (!class_exists('BWP_Framework_V3'))
{
	$class_maps = include dirname(__FILE__) . '/vendor/composer/autoload_classmap.php';

	foreach ($class_maps as $class_name => $class_file) {
		if (strpos($class_name, 'BWP') === false || strpos($class_name, 'PHPUnit') !== false) {
			continue;
		}

		require_once $class_file;
	}
}
