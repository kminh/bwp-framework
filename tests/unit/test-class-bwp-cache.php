<?php

use \Mockery as Mockery;
use \Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @covers BWP_Cache
 */
class BWP_Cache_Test extends MockeryTestCase
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
			->with('key', $expected_group, false, Mockery::any())
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

	/**
	 * @covers BWP_Cache::get
	 * @dataProvider get_test_get_should_return_correct_not_found_value_cases
	 */
	public function test_get_should_return_correct_not_found_value($found, $not_found_value = null)
	{
		$this->bridge
			->shouldReceive('wp_cache_get')
			->with('key', 'bwp_plugin', false, Mockery::on(function(&$f) use ($found) {
				$f = $found;
				return true;
			}))
			->andReturn($found ? 'found' : $not_found_value)
			->byDefault();

		$this->assertEquals($found ? 'found' : $not_found_value, $this->cache->get('key', false, $not_found_value));
	}

	public function get_test_get_should_return_correct_not_found_value_cases()
	{
		return array(
			array(true),
			array(false, null),
			array(false, false),
			array(false, 1)
		);
	}
}
