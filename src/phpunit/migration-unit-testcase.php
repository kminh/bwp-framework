<?php

use \Mockery as Mockery;

/**
 * Copyright (c) 2016 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Framework_PHPUnit_Migration_Unit_TestCase extends BWP_Framework_PHPUnit_Unit_TestCase
{
	protected $migration_methods;

	protected function setUp()
	{
		parent::setUp();

		$this->migration_methods = array();
	}

	/**
	 * Create a migration that allows us to run only one specific migration
	 * method when testing its BWP_Framework_Migration::up() method.
	 *
	 * @param string $method_to_test The one method to test.
	 *
	 * @return BWP_Framework_Migration
	 */
	protected function create_migration($method_to_test)
	{
		$class = new ReflectionClass($this);
		$classname = $class->getName();

		$migration = Mockery::mock(str_replace('_Test', '', $classname))
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$migration->shouldReceive('get_migration_filename')->andReturn('10000.php')->byDefault();
		$migration->__construct($this->plugin);

		$methods = $this->migration_methods;
		foreach ($methods as $method) {
			if ($method == $method_to_test) {
				continue;
			}

			$migration->shouldReceive($method)->andReturnNull()->byDefault();
		}

		return $migration;
	}
}
