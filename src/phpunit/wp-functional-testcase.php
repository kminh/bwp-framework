<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Framework_PHPUnit_WP_Functional_TestCase extends WP_UnitTestCase
{
	public function setUp()
	{
		// do not call parent setUp here because WordPress has not been init
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	abstract public function load_plugins();

	public function bootstrap()
	{
		global $_tests_dir;

		require_once $_tests_dir . '/includes/functions.php';

		tests_add_filter('muplugins_loaded', array($this, 'load_plugins'));

		require $_tests_dir . '/includes/bootstrap.php';

		parent::setUp();
	}
}
