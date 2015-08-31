<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Framework_PHPUnit_Test_Listener extends \PHPUnit_Framework_BaseTestListener
{
	public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
	{
		$suiteName = strtolower($suite->getName());

		if (strpos($suiteName, 'functional') !== false) {
			// is testing multisite?
			if (getenv('WP_TESTS_MULTISITE') && !defined('WP_TESTS_MULTISITE')) {
				define('WP_TESTS_MULTISITE', true);
			}

			// functional test needs WordPress
			require_once dirname(dirname(__DIR__)) . '/tests/functional/bootstrap.php';
		} else {
			// unit test
		}
	}
}
