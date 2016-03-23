<?php

use \Mockery as Mockery;

/**
 * @covers BWP_Framework_Migration
 */
class BWP_Framework_Migration_Test extends PHPUnit_Framework_TestCase
{
	protected $plugin;

    protected $migration;

	protected function setUp()
	{
		$this->plugin = Mockery::mock('BWP_Framework_V3')
			->makePartial();

		$this->migration = Mockery::mock('BWP_Framework_Migration')
			->makePartial()
			->shouldAllowMockingProtectedMethods()
		;
	}

	protected function tearDown()
	{
	}

	/**
	 * @covers BWP_Framework_Migration_Test::__construct
	 * @dataProvider get_migration_filenames
	 */
	public function test_new_migration_should_set_version_correctly(
		$filename,
		$expected_version,
		$expected_previous_version = null
	) {
		$this->migration
			->shouldReceive('get_migration_filename')
			->andReturn($filename)
			->byDefault()
		;

		$this->migration->__construct($this->plugin);

		$this->assertEquals($expected_version, $this->migration->get_version());

		if ($expected_previous_version) {
			$this->assertEquals($expected_previous_version, $this->migration->get_previous_version());
		}
	}

	public function get_migration_filenames()
	{
		$path = dirname(__FILE__);

		return array(
			'version only #1' => array($path . '/10400.php', '1.4.0'),
			'version only #2' => array($path . '/11040.php', '1.10.40'),
			'version only #3' => array($path . '/11104.php', '1.11.4'),
			'version with pre-stable string #1' => array($path . '/10400-beta1.php', '1.4.0-beta1'),
			'version with pre-stable string #2' => array($path . '/10400-rc1.php', '1.4.0-rc1'),
			'version and previous version' => array($path . '/10303_10400.php', '1.4.0', '1.3.3'),
			'version and previous version' => array($path . '/10333_10400.php', '1.4.0', '1.3.33'),
			'version with pre-stable string and previous version #1' => array($path . '/10303_10400-beta1.php', '1.4.0-beta1', '1.3.3'),
			'version with pre-stable string and previous version #2' => array($path . '/10400-beta1_10400-rc1.php', '1.4.0-rc1', '1.4.0-beta1')
		);
	}

	/**
	 * @covers BWP_Framework_Migration_Test::__construct
	 */
	public function test_new_migration_should_throw_exception_if_previous_version_is_higher_than_target_version()
	{
		$this->migration
			->shouldReceive('get_migration_filename')
			->andReturn(dirname(__FILE__) . '/10400_10300.php')
			->byDefault()
		;

		$this->setExpectedException('DomainException', 'previous version must not be higher than target version');

		$this->migration->__construct($this->plugin);
	}

	/**
	 * @covers BWP_Framework_Migration_Test::__construct
	 */
	public function test_new_migration_should_throw_exception_if_any_version_is_smaller_than_10000()
	{
		$this->migration
			->shouldReceive('get_migration_filename')
			->andReturn(dirname(__FILE__) . '/00400_00300.php')
			->byDefault()
		;

		$this->setExpectedException('DomainException', 'versions must be higher than or equal 1.x.x');

		$this->migration->__construct($this->plugin);
	}
}
