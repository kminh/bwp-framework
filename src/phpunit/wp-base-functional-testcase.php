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
	public function setUp()
	{
		parent::setUp();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * Get a list of plugins that should be loaded and activated for testing
	 */
	abstract public function get_plugins();

	/**
	 * Load plugins into their places
	 *
	 * This should include the plugin codes as well as create symlinks to the
	 * plugins' folders from `wp-content/plugins` directory so that they can
	 * be activated and used later on
	 *
	 * This function is safe to be called several times.
	 */
	public function load_plugins()
	{
		global $_core_dir;

		$plugins = $this->get_plugins();

		foreach ($plugins as $plugin_file => $plugin_path) {
			$target  = dirname($plugin_file);
			$symlink = $_core_dir . '/wp-content/plugins/' . dirname($plugin_path);

			if (!file_exists($symlink)) {
				exec('ln -s ' . escapeshellarg($target) . ' ' . escapeshellarg($symlink));
			}

			// dont include fixtures because they are not actually plugins
			if (stripos($plugin_file, 'fixtures') !== false) {
				continue;
			}

			include_once $plugin_file;
		}
	}

	protected function bootstrap_plugin()
	{
		global $_tests_dir;

		if (!function_exists('tests_add_filter')) {
			require_once $_tests_dir . '/includes/functions.php';

			// load plugin to use within this session, NOT for the next request
			tests_add_filter('muplugins_loaded', array($this, 'load_plugins'));

			// bootstrap WordPress itself, this should provides the WP environment and
			// drop/recreate tables
			require $_tests_dir . '/includes/bootstrap.php';
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
}
