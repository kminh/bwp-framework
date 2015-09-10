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

			// don't test the testcase classes when on PHP < 5.3 as they
			// require namespace
			if (stripos($class_name, 'TestCase') !== false
				&& version_compare(PHP_VERSION, '5.3', '<')
			) {
				continue;
			}

			$classes[] = $this->getMockBuilder($class_name)
				->disableOriginalConstructor()
				->getMock();
		}

		$this->assertTrue(true);
	}
}
