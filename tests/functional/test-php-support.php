<?php

/**
 * @author Khang Minh <kminh@kdmlabs.com>
 */
class BWP_Framework_Functional_Test extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
	}

	protected function tearDown()
	{
	}

	public function test_can_initiate_all_classes()
	{
		$classes = array();
		$class_maps = include dirname(dirname(dirname(__FILE__))) . '/vendor/composer/autoload_classmap.php';

		foreach ($class_maps as $class_name => $class_file) {
			if (strpos($class_name, 'BWP') === false) {
				continue;
			}

			$not_php_52 = array(
				'BWP_Framework_PHPUnit_Unit_TestCase',
				'BWP_Framework_PHPUnit_WP_Functional_TestCase',
				'BWP_Framework_PHPUnit_WP_Multisite_Functional_TestCase'
			);

			// do not load certain testcase classes if PHP version is less than 5.3
			if (in_array($class_name, $not_php_52) && version_compare(PHP_VERSION, '5.3', '<')) {
				continue;
			}

			require_once $class_file;
		}

		$this->assertTrue(true);
	}
}
