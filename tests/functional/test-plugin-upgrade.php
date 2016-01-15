<?php

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class BWP_Framework_Plugin_Upgrade_Functional_Test extends BWP_Framework_PHPUnit_WP_Legacy_Functional_TestCase
{
	protected $is_admin = true;

	public function setUp()
	{
		$this->bootstrap_plugin();

		global $bwp_plugin_with_upgrades;
		$this->plugin = $bwp_plugin_with_upgrades;
	}

	public function get_plugin_under_test()
	{
		return array(
			dirname(__FILE__) . '/data/fixtures/plugin-with-upgrades.php' => 'bwp-plugin/plugin-with-upgrades.php'
		);
	}

	public function test_plugin_should_upgrade_ok()
	{
		$this->assertEquals(get_option('bwp_plugin_version'), '1.1.0');
	}
}
