<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Framework_PHPUnit_WP_Base_Functional_TestCase extends WP_UnitTestCase
{
	/**
	 * Should we test in admin environment?
	 *
	 * @since rev 166
	 */
	protected $is_admin = false;

	/**
	 * WordPress version.
	 *
	 * @since rev 173
	 */
	protected static $wp_version;

	public static function setUpBeforeClass()
	{
		// Include the
		global $_core_dir;
		$wp_version_file = $_core_dir . 'wp-includes/version.php';
		if (!file_exists($wp_version_file)) {
			throw new Exception('WordPress\'s version.php file cannot be found, '
				. 'unable to set up properly before any test.');
		}

		// If WordPress's version is 4.4 or higher, we need to treat the setup
		// and tear down function differently to avoid fatal errors. Because
		// this function can be called several times (for tests that are run in
		// separate processes), we use `include` and not `include_once`. Since
		// only some variables are expected in the `version.php` file, it
		// should be safe to use `include`.
		include $wp_version_file;
		self::$wp_version = $wp_version;
		if (version_compare(self::$wp_version, '4.4', '>=')) {
		} else {
			// Otherwise, simply cascade the call.
			parent::setUpBeforeClass();
		}
	}

	public static function tearDownAfterClass()
	{
		// Since WordPress 4.7, the `tearDownAfterClass` method of WordPress's
		// test libs will call some methods such as `_delete_all_data`. If we
		// have tests run in separate processes, the first process might not
		// have bootstrapped plugin, meaning the test libs' utility methods
		// will not exist, which results in a fatal error. Therefore, we can
		// only call WordPress's `tearDownAfterClass` if WordPress version is
		// smaller than 4.7, or the `_delete_all_data` method exists.
		if (version_compare(self::$wp_version, '4.7', '<') || function_exists('_delete_all_data')) {
			// It's OK to call WordPress's `tearDownAfterClass` method.
			parent::tearDownAfterClass();
		} else {
			// Do not call WordPress's `tearDownAfterClass` method because
			// doing so will result in a fatal error.
		}
	}

	public function setUp()
	{
		parent::setUp();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * Get the main plugin under test
	 *
	 * @return array
	 */
	abstract public function get_plugin_under_test();

	/**
	 * Get a list of extra plugins that should be loaded and activated for testing
	 *
	 * This can include fxitures if needed.
	 *
	 * @return array
	 */
	public function get_extra_plugins()
	{
		return array();
	}

	/**
	 * Get a list of all plugins, including the plugin under test and extra ones
	 *
	 * @return array
	 */
	public function get_all_plugins()
	{
		return array_merge(
			$this->get_plugin_under_test(),
			$this->get_extra_plugins()
		);
	}

	/**
	 * Prepare plugin directories
	 *
	 * This should create symlinks to the plugins' folders from
	 * `wp-content/plugins` directory so that they can be activated and used
	 * later on
	 */
	public function prepare_plugin_directories()
	{
		global $_core_dir;

		$plugins = $this->get_all_plugins();

		foreach ($plugins as $plugin_file => $plugin_path) {
			$target  = dirname($plugin_file);
			$symlink = $_core_dir . '/wp-content/plugins/' . dirname($plugin_path);

			exec('ln -sfn ' . escapeshellarg($target) . ' ' . escapeshellarg($symlink));
		}
	}

	/**
	 * Load all required plugins for current process
	 *
	 * This should be called by extending testcases explicitly.
	 * This only loads actual plugins, not fixtures.
	 */
	public function load_plugins_for_current_process()
	{
		$plugins = $this->get_all_plugins();

		foreach ($plugins as $plugin_file => $plugin_path) {
			// dont include fixtures because they are not actually plugins
			if (stripos($plugin_file, 'fixtures') !== false) {
				continue;
			}

			include_once $plugin_file;
		}
	}

	/**
	 * Filter `pre_option_active_plugins` hook.
	 *
	 * @return array
	 * @since rev 166
	 */
	public function pre_option_active_plugins()
	{
		// This is a hack to run tests in admin environment. If we do this in
		// setUp function it will break WordPress's codes in `wp-includes/vars.php`.
		if ($this->is_admin && ! defined('WP_ADMIN')) {
			define('WP_ADMIN', true);
		}

		return $this->get_all_plugins();
	}

	/**
	 * This should be used explicitly by extending testcases
	 */
	protected function load_fixtures($file_name = null)
	{
		$plugins = $this->get_extra_plugins();

		foreach ($plugins as $plugin_file => $plugin_path) {
			// only load fixtures
			if (stripos($plugin_file, 'fixtures') === false) {
				continue;
			}

			// only load correct fixture, if specified
			if ($file_name && stripos($plugin_file, $file_name) === false) {
				continue;
			}

			include_once $plugin_file;
		}
	}

	protected function bootstrap_plugin()
	{
		global $_tests_dir;

		// prepare plugin directories for the current session and any following requests
		$this->prepare_plugin_directories();

		if (!function_exists('tests_add_filter')) {
			require_once $_tests_dir . '/includes/functions.php';

			// we need to do this here to make sure loaded plugins can make
			// use of WordPress's init action. If a testcase needs a different
			// set of plugins it should be run in a separate process because
			// this is called only once.
			tests_add_filter('pre_option_active_plugins', array($this, 'pre_option_active_plugins'));

			// bootstrap WordPress itself, this should provide the WP environment and
			// drop/recreate tables
			require $_tests_dir . '/includes/bootstrap.php';

			// mark as installed
			touch($_tests_dir . '/installed.lock');
		}
	}

	/**
	 * Get current WP version
	 *
	 * If a WP version is provided as the first parameter, check if the
	 * current WP version is greater than or equal to that provided version
	 *
	 * @return mixed
	 */
	protected static function get_wp_version($version = '')
	{
		$current_version = get_bloginfo('version');
		return !$version ? $current_version : version_compare($current_version, $version, '>=');
	}

	/**
	 * @return string
	 */
	protected static function uniqid()
	{
		return md5(uniqid(rand(), true));
	}

	/**
	 * Prepare default values including options and active plugins
	 */
	protected function prepare_default_values()
	{
		// to be implemented in child testcases
	}
}
