<?php

use \Mockery as Mockery;

/**
 * @covers BWP_Cache
 */
class BWP_Cache_Test extends PHPUnit_Framework_TestCase
{
	protected $bridge;

	protected $cache;

	protected function setUp()
	{
		$this->bridge = Mockery::mock('BWP_WP_Bridge');

		$this->cache = new BWP_Cache($this->bridge, 'bwp_plugin');
	}

	protected function tearDown()
	{
	}

	/**
	 * @covers BWP_Cache::set
	 * @dataProvider get_test_cases
	 */
	public function test_set($shared, $expected_group)
	{
		$this->bridge
			->shouldReceive('wp_cache_set')
			->with('key', 'value', $expected_group)
			->once();

		$this->cache->set('key', 'value', $shared);
	}

	/**
	 * @covers BWP_Cache::get
	 * @dataProvider get_test_cases
	 */
	public function test_get($shared, $expected_group)
	{
		$this->bridge
			->shouldReceive('wp_cache_get')
			->with('key', $expected_group)
			->once();

		$this->cache->get('key', $shared);
	}

	public function get_test_cases()
	{
		return array(
			array(false, 'bwp_plugin'),
			array(true, 'bwp_plugins')
		);
	}
}
