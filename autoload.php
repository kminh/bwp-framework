<?php

// not autoloading anything actually
if (!class_exists('BWP_Framework_V3'))
{
	require_once __DIR__ . '/src/class-bwp-version.php';
	require_once __DIR__ . '/src/class-bwp-wp-bridge.php';
	require_once __DIR__ . '/src/class-bwp-option-page-v3.php';
	require_once __DIR__ . '/src/class-bwp-framework-util.php';
	require_once __DIR__ . '/src/class-bwp-framework-v3.php';
}
