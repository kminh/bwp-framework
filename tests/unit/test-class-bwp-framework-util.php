<?php

/**
 * @covers BWP_Framework_Util
 * @runTestsInSeparateProcesses
 * @author Khang Minh <kminh@kdmlabs.com>
 */
class BWP_Framework_Util_Test extends \PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
	}

	protected function tearDown()
	{
	}

	/**
	 * @covers BWP_Framework_Util::is_site_admin
	 * @dataProvider get_is_site_admin_cases
	 */
	public function test_is_site_admin($is_multisite, $is_admin_user, $is_super_admin, $is_site_admin)
	{
		define('MULTISITE', $is_multisite);
		define('BWP_IS_ADMIN_USER', $is_admin_user);
		define('BWP_IS_SUPER_ADMIN', $is_super_admin);

		if ($is_site_admin) {
			$this->assertTrue(BWP_Framework_Util::is_site_admin());
		} else {
			$this->assertFalse(BWP_Framework_Util::is_site_admin());
		}
	}

	public function get_is_site_admin_cases()
	{
		return array(
			array(false, false, false, false),
			array(false, true, false, true),
			array(true, true, false, false),
			array(true, true, true, true)
		);
	}

	/**
	 * @covers BWP_Framework_Util::is_multisite_admin
	 * @dataProvider get_is_multisite_admin_cases
	 */
	public function test_is_multisite_admin($is_multisite, $is_super_admin, $is_multisite_admin)
	{
		define('MULTISITE', $is_multisite);
		define('BWP_IS_SUPER_ADMIN', $is_super_admin);

		if ($is_multisite_admin) {
			$this->assertTrue(BWP_Framework_Util::is_multisite_admin());
		} else {
			$this->assertFalse(BWP_Framework_Util::is_multisite_admin());
		}
	}

	public function get_is_multisite_admin_cases()
	{
		return array(
			array(false, false, false),
			array(false, true, false),
			array(true, true, true),
		);
	}

	/**
	 * @covers BWP_Framework_Util::can_update_site_option
	 * @dataProvider get_can_update_site_option_cases
	 */
	public function test_can_update_site_option($is_multisite, $is_super_admin, $bid, $can_update)
	{
		global $blog_id;

		$blog_id = $bid;

		define('MULTISITE', $is_multisite);
		define('BWP_IS_SUPER_ADMIN', $is_super_admin);

		if ($can_update) {
			$this->assertTrue(BWP_Framework_Util::can_update_site_option());
		} else {
			$this->assertFalse(BWP_Framework_Util::can_update_site_option());
		}
	}

	public function get_can_update_site_option_cases()
	{
		return array(
			array(false, false, 1, false),
			array(true, false, 1, false),
			array(false, true, 1, false),
			array(true, true, 2, false),
			array(true, true, 1, true),
		);
	}

	/**
	 * @covers BWP_Framework_Util::get_request_var
	 * @dataProvider get_test_request_var_cases
	 */
	public function test_get_request_var($key, $value, $expected, $empty_as_null = true)
	{
		$_REQUEST[$key] = $value;

		$this->assertEquals($expected, BWP_Framework_Util::get_request_var($key, $empty_as_null));
	}

	public function get_test_request_var_cases()
	{
		return array(
			// scalar values
			array('var', 'value', 'value'),
			array('var', " v\'alue <strong>", 'v\'alue'),
			array('var', '<strong>', null),
			array('var', '<strong>', '', false),
			array('var', 1, 1),
			array('var', 0, 0),

			// array values
			array('var', array(
				'value', " v\'alue <strong>", '<strong>'
			), array(
				'value', "v'alue", ''
			)),

			array('var', array(), null),
			array('var', array(), array(), false)
		);
	}
}
