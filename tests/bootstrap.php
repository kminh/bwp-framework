<?php

$_root_dir = dirname(dirname(__FILE__));

if (version_compare(PHP_VERSION, '5.3.2', '<')) {
	require_once $_root_dir . '/autoload.php';
} else {
	require_once $_root_dir . '/vendor/autoload.php';
}

function _bwp_framework_functional_test_autoloader($class_name)
{
	global $_tests_dir;

	if ($class_name != 'WP_UnitTestCase') {
		return;
	}

	if (!$_tests_dir = getenv('WP_TESTS_DIR')) {
		$_tests_dir = '/tmp/wordpress-latest-tests-lib';

		putenv("WP_TESTS_DIR=$_tests_dir/");
		putenv("WP_CORE_DIR=/tmp/wordpress-latest/");
	}

	define('WP_TESTS_MULTISITE', 1);
	define('WP_TESTS_FORCE_KNOWN_BUGS', false);

	// install WordPress
	$wp_version = getenv('WP_VERSION') ?: 'latest';
	$db_user    = getenv('WP_DB_USER') ?: 'test';
	$db_pass    = getenv('WP_DB_PASS') ?: 'test';
	$script     = dirname(dirname(__FILE__)) . '/bin/install-wp-tests.sh';
	$cmd        = sprintf('%1$s bwp_test_%2$s %3$s %4$s localhost %2$s', $script, $wp_version, $db_user, $db_pass);

	exec($cmd, $output, $status);

	if ($status !== 0) {
		exit($status);
	}

	// load WordPress TestCase
	require_once $_tests_dir . '/includes/testcase.php';
}

spl_autoload_register('_bwp_framework_functional_test_autoloader');
