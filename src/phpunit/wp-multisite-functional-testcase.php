<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Framework_PHPUnit_WP_Multisite_Functional_TestCase extends BWP_Framework_PHPUnit_WP_Functional_TestCase
{
	public static function tearDownAfterClass()
	{
		global $_tests_dir, $_core_dir;

		$htaccess_file = $_core_dir . '/.htaccess';
		exec('rm -f ' . escapeshellarg($htaccess_file));

		parent::tearDownAfterClass();
	}

	protected static function prepare_wp_config()
	{
		global $_tests_dir, $_core_dir;

		$root_dir = dirname(dirname(__DIR__));

		$wp_config_file          = $_core_dir . '/wp-config.php';
		$wp_config_file_original = $_core_dir . '/wp-config-original.php';

		// multisite needs additional config constants and advanced .htaccess
		if (!file_exists($wp_config_file)
			|| stripos(file_get_contents($wp_config_file), 'WP_ALLOW_MULTISITE') === false
		) {

			$wp_config = file_get_contents($root_dir . '/tests/functional/data/multisite-wp-config');
			$wp_config = sprintf($wp_config, WP_TESTS_DOMAIN);

			$wp_config_file          = $_core_dir . '/wp-config.php';
			$wp_config_file_original = $_core_dir . '/wp-config-original.php';

			exec('cp -f ' . escapeshellarg($wp_config_file_original) . ' ' . escapeshellarg($wp_config_file));
			exec('echo ' . escapeshellarg($wp_config) . ' >> ' . escapeshellarg($wp_config_file));
		}

		$htaccess_file = $_core_dir . '/.htaccess';

		if (!file_exists($htaccess_file)) {
			$htaccess = file_get_contents($root_dir . '/tests/functional/data/multisite-htaccess');
			exec('echo ' . escapeshellarg($htaccess) . ' > ' . escapeshellarg($htaccess_file));
		}
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	protected static function update_site_option($key, $value)
	{
		update_site_option($key, $value);

		self::commit_transaction();
	}
}
