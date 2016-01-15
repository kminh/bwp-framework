<?php

class BWP_Plugin_With_Upgrades extends BWP_Framework_V3
{
	public function __construct(array $meta)
	{
		parent::__construct($meta);

		// reset version to prior to upgrades
		update_option('bwp_plugin_version', '1.0.0');

		$this->build_properties('BWP_Plugin', array(), __FILE__, 'http://betterwp.net', false);
	}

	public function upgrade_plugin($from, $to)
	{
		// it is not actualy safe to call self::update_some_options here
		// because it uses functions like wp_get_current_user(), which is not
		// safe to be used before the `init` action hook anyway, but at least
		// we make sure that even when we use this function here it won't
		// cause a fatal error.
		$this->update_some_options('bwp_plugin_general', array(
			'option1' => 'new value1',
			'option2' => 'new value2'
		));
	}

	public function init_upgrade_plugin($from, $to)
	{
		// self::update_some_options should really be called here
		$this->update_some_options('bwp_plugin_general', array(
			'option1' => 'new value1',
			'option2' => 'new value2'
		));
	}
}

global $bwp_plugin_with_upgrades;
$bwp_plugin_with_upgrades = new BWP_Plugin_With_Upgrades(array(
	'title'   => 'BWP Plugin',
	// set current version to 1.1.0 to always trigger an upgrade
	'version' => '1.1.0',
	'domain'  => 'bwp-plugin'
));
