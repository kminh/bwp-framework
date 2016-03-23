<?php

use \Mockery as Mockery;

use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamDirectory;

/**
 * @covers BWP_Framework_Migration_Factory
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class BWP_Framework_Migration_Factory_Test extends \PHPUnit_Framework_TestCase
{
	protected $plugin;

	protected function setUp()
	{
		$this->plugin = Mockery::namedMock('BWP_Framework_V3');
	}

	protected function tearDown()
	{
	}

	/**
	 * @covers BWP_Framework_Migration_Factory::create_migrations
	 */
	public function test_create_migrations_should_return_an_empty_array_if_migration_directory_does_not_exist()
	{
		$this->plugin->plugin_file = 'non-existent/path/to/plugin';

		$this->assertSame(
			array(),
			BWP_Framework_Migration_Factory::create_migrations($this->plugin)
		);
	}

	/**
	 * @covers BWP_Framework_Migration_Factory::create_migrations
	 */
	public function test_create_migrations_correctly_and_in_correct_order()
	{
		$fixture_path = dirname(__FILE__) . '/fixtures/migrations';
		$migration_filenames = array(
			'invalid.php',
			'10400.php', // #5
			'110400.php', // #6
			'10100_10400.php', // #3
			'10100_10400-beta1.php', // #1
			'10100-beta1_10400.php', #4
			'10100-beta1_10400-rc1.php', // #2
		);

		$migration_files = array();
		foreach ($migration_filenames as $filename) {
			$migration_files[$filename] = file_get_contents($fixture_path . '/' . $filename);
		}

		$migration_path = 'path/to/plugin/migrations';
		$fs_root = vfsStream::setup($migration_path);
		vfsStream::create($migration_files, $fs_root->getChild($migration_path));

		$this->plugin->plugin_file = vfsStream::url('path/to/plugin/bwp-plugin.php');

		$migrations = array();
		foreach (BWP_Framework_Migration_Factory::create_migrations($this->plugin) as $index => $migration) {
			$migrations[] = array(
				$migration->get_previous_version(),
				$migration->get_version()
			);
		}

		$this->assertEquals(
			array(
				array('1.1.0', '1.4.0-beta1'),
				array('1.1.0-beta1', '1.4.0-rc1'),
				array('1.1.0-beta1', '1.4.0'),
				array('1.1.0', '1.4.0'),
				array(null, '1.4.0'),
				array(null, '11.4.0')
			),
			$migrations
		);
	}
}
