<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Framework_PHPUnit_WP_Functional_TestListener extends PHPUnit_Framework_BaseTestListener
{
	public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
	{
		global $_tests_dir;

		$suite_name = $suite->getName();

		// we only care about suite name that is an actual test class
		if (empty($suite_name) || !class_exists($suite_name, false)) {
			return;
		}

		if (file_exists($_tests_dir . '/installed.lock')) {
			unlink($_tests_dir . '/installed.lock');
		}
	}
}
