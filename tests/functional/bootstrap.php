<?php

if (!$_tests_dir = getenv('WP_TESTS_DIR')) {
	throw new \Exception('Environment variable "WP_TESTS_DIR" must be set');
}

$_tests_dir = realpath($_tests_dir);

require_once $_tests_dir . '/includes/functions.php';

require $_tests_dir . '/includes/bootstrap.php';
