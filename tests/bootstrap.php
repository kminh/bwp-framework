<?php

$root_dir = dirname(dirname(__FILE__));

if (version_compare(PHP_VERSION, '5.3.2', '<')) {
	require_once $root_dir . '/autoload.php';
} else {
	require_once $root_dir . '/vendor/autoload.php';
}

function _bwp_framework_test_autoloader($class_name)
{
	global $_tests_dir, $_core_dir;

	if ($class_name != 'WP_UnitTestCase' || class_exists('WP_UnitTestCase', false)) {
		return;
	}

	$tmp_dir = getenv('WP_TMP_DIR') ? getenv('WP_TMP_DIR') : '/tmp';

	if (!$wp_version = getenv('WP_VERSION')) {
		$wp_version = 'latest';
	}

	$_tests_dir = $tmp_dir . '/wordpress-' . $wp_version . '-tests-lib';
	$_core_dir  = $tmp_dir . '/wordpress-' . $wp_version;

	putenv("WP_TESTS_DIR=$_tests_dir");
	putenv("WP_CORE_DIR=$_core_dir/");

	define('WP_RUN_CORE_TESTS', 1);
	define('WP_TESTS_FORCE_KNOWN_BUGS', false);

	// use symlinks for dev
	define('BWP_USE_SYMLINKS', true);

	// install WordPress core files and test lib, only when needed
	if (!file_exists($_tests_dir . '/installed.lock')) {
		$db_user = getenv('WP_DB_USER') ? getenv('WP_DB_USER') : 'test';
		$db_pass = getenv('WP_DB_PASS') ? getenv('WP_DB_PASS') : 'test';
		$script  = dirname(dirname(__FILE__)) . '/bin/install-wp-tests.sh';
		$cmd     = sprintf('%1$s bwp_test_%2$s %3$s %4$s localhost %2$s', $script, $wp_version, $db_user, $db_pass);

		exec($cmd, $output, $status);

		if ($status !== 0) {
			exit($status);
		}
	}

	// each functional test requires a doc root
	$_tests_doc_root = getenv('WP_TESTS_DOCROOT') ? getenv('WP_TESTS_DOCROOT') : '/srv/http/sites/wptest.net';

	// copy WordPress core dir to use it as the test docroot, we need to use
	// actual files to avoid symlink's caching issue, @link
	// http://stackoverflow.com/questions/18450076/capistrano-symlinks-being-cached
	exec('rm -rf ' . escapeshellarg($_tests_doc_root));
	exec('cp -rf ' . escapeshellarg($_core_dir) . ' ' . escapeshellarg($_tests_doc_root));

	// change the core directory to the test docroot so we can later setup the
	// correct environment for each test
	$_core_dir = rtrim($_tests_doc_root, '/') . '/';

	// need to update ABSPATH in BOTH wp-config file (for crawling) and
	// wp-tests-config file (for in-process work)
	$wp_config_file = rtrim($_core_dir, '/') . '/wp-config-original.php';
	$wp_tests_config_file = rtrim($_tests_dir, '/') . '/wp-tests-config.php';
	exec("sed -i \"s:define( 'ABSPATH', '.*':define( 'ABSPATH', '$_core_dir':\" \"$wp_config_file\"");
	exec("sed -i \"s:define( 'ABSPATH', '.*':define( 'ABSPATH', '$_core_dir':\" \"$wp_tests_config_file\"");

	// load WordPress UnitTestCase
	require_once $_tests_dir . '/includes/testcase.php';
}

spl_autoload_register('_bwp_framework_test_autoloader');
