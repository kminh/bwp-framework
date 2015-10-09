<?php

use \Guzzle\Plugin\Cache\CachePlugin;
use \Symfony\Component\DomCrawler\Crawler;
use \Goutte\Client;

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Framework_PHPUnit_WP_Functional_TestCase extends BWP_Framework_PHPUnit_WP_Base_Functional_TestCase
{
	/**
	 * @var \Goutte\Client
	 */
	protected static $client;

	/**
	 * @var string
	 */
	protected static $cache_dir;

	/**
	 * Prepare the WP environment
	 *
	 * This will prepare the environment for the current session as well as any
	 * following requests made to the current test installation using a client
	 * such as a crawler or a browser
	 */
	public function setUp()
	{
		$this->bootstrap_plugin();

		static::prepare_wp_config();
		static::prepare_htaccess_file();
		static::prepare_cache_directory();
		static::prepare_asset_directories();

		$this->load_plugins();

		parent::setUp();
	}

	/**
	 * Prepares the WP environment, but for current request only
	 *
	 * This means we don't need to prepare config and htaccess stuff
	 */
	public function setUpForCurrentRequest()
	{
		$this->bootstrap_plugin();

		static::prepare_cache_directory();
		static::prepare_asset_directories();

		$this->load_plugins();

		parent::setUp();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	protected static function prepare_wp_config()
	{
		global $_tests_dir, $_core_dir;

		$wp_config_file          = $_core_dir . '/wp-config.php';
		$wp_config_file_original = $_core_dir . '/wp-config-original.php';
		$wp_config_file_content  = file_get_contents($wp_config_file);

		// if config file is missing or its contents are missing
		// wp-settings.php OR the its current contents are for multisite
		// installation add/adjust it so we can browse the test WP installation
		// later on
		if (!file_exists($wp_config_file)
			|| stripos($wp_config_file_content, 'wp-settings.php') === false
			|| stripos($wp_config_file_content, 'WP_ALLOW_MULTISITE') !== false
		) {
			$root_dir  = dirname(dirname(__DIR__));
			$wp_config = file_get_contents($root_dir . '/tests/functional/data/wp-config');

			exec('cp -f ' . escapeshellarg($wp_config_file_original) . ' ' . escapeshellarg($wp_config_file));
			exec('echo ' . escapeshellarg($wp_config) . ' >> ' . escapeshellarg($wp_config_file));
		}
	}

	protected static function prepare_htaccess_file()
	{
		// intentionally left blank
	}

	/**
	 * Prepare a blank cache folder for every test
	 *
	 * @param string $cache_dir
	 */
	protected static function prepare_cache_directory($cache_dir = null)
	{
		global $_core_dir;

		self::$cache_dir = !$cache_dir ? $_core_dir . '/wp-content/cache' : $cache_dir;

		exec('rm -rf ' . self::$cache_dir);
		mkdir(self::$cache_dir);
	}

	/**
	 * Prepare js and css directories if not existed
	 */
	protected static function prepare_asset_directories()
	{
		global $_core_dir;

		$dirs = array(
			'/js', '/css'
		);

		foreach ($dirs as $dir) {
			if (!file_exists($_core_dir . $dir)) {
				mkdir($_core_dir . $dir);
			}
		}
	}

	/**
	 * Set WP options that are used for all tests
	 */
	protected static function set_wp_default_options()
	{
		// to be implemented in child classes
	}

	/**
	 * Set default options that are used for all tests
	 */
	protected static function set_plugin_default_options()
	{
		// to be implemented by child classes
	}

	/**
	 * Set options used with a specific testcase
	 *
	 * @param string $option_key
	 * @param array $options a subset of options to be set
	 */
	protected static function set_options($option_key, array $options)
	{
		$current_options = get_option($option_key);
		$current_options = $current_options ? $current_options : array();

		self::update_option($option_key, array_merge($current_options, $options));
	}

	/**
	 * Activate specific plugins
	 *
	 * Activated plugins are only fully available in next request
	 *
	 * @param array $plugins
	 */
	protected static function activate_plugins(array $plugins)
	{
		self::update_option('active_plugins', $plugins);
	}

	protected static function deactivate_plugins(array $plugins = array())
	{
		// only deactivate some plugins
		if ($plugins) {
			$active_plugins = get_option('active_plugins');
			$active_plugins = array_diff($active_plugins, $plugins);
			self::update_option('active_plugins', $active_plugins);
			return;
		}

		// deactivate all plugins
		self::update_option('active_plugins', array());
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	protected static function update_option($key, $value)
	{
		update_option($key, $value);

		self::commit_transaction();
	}

	/**
	 * Reset users to installed state
	 *
	 * This should not reset the initial 'admin' user
	 */
	protected static function reset_users()
	{
		global $wpdb;
		$wpdb->query("DELETE FROM $wpdb->users WHERE user_login != 'admin'");
		self::commit_transaction();
	}

	protected static function reset_posts()
	{
		global $wpdb;
		$wpdb->query("DELETE FROM $wpdb->posts WHERE 1=1");
		self::commit_transaction();
	}

	protected static function reset_comments()
	{
		global $wpdb;
		$wpdb->query("DELETE FROM $wpdb->comments WHERE 1=1");
		self::commit_transaction();
	}

	/**
	 * @param bool $use_existing whether to create a new client or use existing if any,
	 *                           default to true
	 * @param bool $use_cache whether to use http cache
	 * @return \Goutte\Client
	 */
	protected static function get_client($use_existing = true, $use_cache = false)
	{
		$client = self::$client && $use_existing ? self::$client : new Client();

		// reset the existing client if not using it
		if (!$use_existing) {
			// use http cache if needed
			if ($use_cache) {
				$cache_plugin = new CachePlugin();
				$client->getClient()->addSubscriber($cache_plugin);
			}

			self::$client = $client;
		}

		return $client;
	}

	/**
	 * @return \Goutte\Client
	 */
	protected static function get_client_clone()
	{
		if (!self::$client) {
			throw new Exception('Must have an existing client first before cloning');
		}

		return clone self::$client;
	}

	/**
	 * @param string $url
	 * @param array $headers
	 * @return \Symfony\Component\DomCrawler\Crawler
	 */
	protected static function get_crawler_from_url($url, array $headers = array())
	{
		$client = self::get_client(false);

		foreach ($headers as $name => $value) {
			$client->setHeader($name, $value);
		}

		return $client->request('GET', $url);
	}

	/**
	 * @param WP_Post $post
	 * @return \Symfony\Component\DomCrawler\Crawler
	 */
	protected static function get_crawler_from_post(WP_Post $post)
	{
		return self::get_crawler_from_url(get_permalink($post));
	}

	/**
	 * {@inheritDoc}
	 */
	protected function prepare_default_values()
	{
		static::activate_plugins(array_values($this->get_plugins()));
		static::set_wp_default_options();
		static::set_plugin_default_options();
	}
}
