<?php

require_once dirname(dirname(__FILE__)) . '/test-plugin-upgrade.php';

define('WP_TESTS_MULTISITE', true);

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class BWP_Framework_Plugin_Upgrade_Multisite_Functional_Test extends BWP_Framework_Plugin_Upgrade_Functional_Test
{
}
