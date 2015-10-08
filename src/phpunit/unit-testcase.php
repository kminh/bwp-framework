<?php

use \Mockery as Mockery;
use \Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Framework_PHPUnit_Unit_TestCase extends MockeryTestCase
{
	protected $bridge;

	protected $plugin_slug = 'bwp-plugin';

	protected $plugin;

	protected function setUp()
	{
		$this->bridge = Mockery::mock('BWP_WP_Bridge');

		$plugin_wp_url = 'http://example.com/wp-content/plugins/' . $this->plugin_slug . '/';

		$this->bridge->shouldReceive('plugins_url')->andReturnUsing(function() use ($plugin_wp_url) {
			return $plugin_wp_url;
		})->byDefault();

		$this->bridge->shouldReceive('plugin_dir_url')->andReturnUsing(function() use ($plugin_wp_url) {
			return $plugin_wp_url;
		})->byDefault();

		$this->bridge
			->shouldReceive('plugin_dir_path')
			->andReturnUsing(function() {
				return '/path/to/wordpress/wp-content/plugins/' . $this->plugin_slug . '/';
			})
			->byDefault();

		$this->bridge->shouldReceive('home_url')->andReturn('http://example.com')->byDefault();
		$this->bridge->shouldReceive('site_url')->andReturn('http://example.com')->byDefault();
		$this->bridge->shouldReceive('is_ssl')->andReturn(false)->byDefault();

		$this->bridge->shouldReceive('get_bloginfo')->andReturn('4.3')->byDefault();
		$this->bridge->shouldReceive('get_option')->andReturn(false)->byDefault();
		$this->bridge->shouldReceive('update_option')->byDefault();

		$this->bridge->shouldReceive('do_action')->andReturnNull()->byDefault();
		$this->bridge->shouldReceive('add_action')->byDefault();
		$this->bridge->shouldReceive('apply_filters')->andReturnUsing(function($hook_name, $value) {
			return $value;
		})->byDefault();
		$this->bridge->shouldReceive('apply_filters')->with('/[a-z_]+_default_options/', array())->andReturn(array())->byDefault();
		$this->bridge->shouldReceive('add_filter')->byDefault();

		$this->bridge->shouldReceive('trailingslashit')->andReturnUsing(function($path) {
			return rtrim($path, '/') . '/';
		})->byDefault();

		$this->bridge->shouldReceive('untrailingslashit')->andReturnUsing(function($path) {
			return rtrim($path, '/');
		})->byDefault();

		$this->bridge->shouldReceive('wp_parse_args')->andReturnUsing(function($args, $default_args) {
			return array_merge($default_args, $args);
		})->byDefault();

		$this->bridge->shouldReceive('is_admin')->andReturn(false)->byDefault();

		$this->bridge->shouldReceive('register_activation_hook')->byDefault();
		$this->bridge->shouldReceive('register_deactivation_hook')->byDefault();

		$this->bridge->shouldReceive('load_plugin_textdomain')->byDefault();
		$this->bridge->shouldReceive('t')->andReturn(create_function('$key', 'return $key;'))->byDefault();
		$this->bridge->shouldReceive('te')->andReturn(create_function('$key', 'return $key;'))->byDefault();
	}

	protected function tearDown()
	{
		global $wpdb;

		if (isset($wpdb)) {
			unset($wpdb);
		}
	}

	/**
	 * Call protected method of the plugin under test
	 *
	 * @param string $method_name
	 * @param array $params
	 */
	protected function call_protected_method($method_name, $params = array())
	{
		$reflection = new ReflectionClass(get_class($this->plugin));
		$method = $reflection->getMethod($method_name);
		$method->setAccessible(true);

		$params = (array) $params;
		$method->invokeArgs($this->plugin, $params);
	}
}
