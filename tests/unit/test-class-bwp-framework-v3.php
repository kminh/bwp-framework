<?php

use \Mockery as Mockery;
use \Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @covers BWP_Framework_V3
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Framework_V3_Test extends MockeryTestCase
{
	protected $bridge;

	protected $cache;

	protected $default_options;

	protected $plugin_file;

	protected $plugin_version;

	protected $framework;

	protected function setUp()
	{
		$this->bridge = Mockery::mock('BWP_WP_Bridge');
		$this->bridge->shouldReceive('is_admin')->andReturn(true)->byDefault();
		$this->bridge->shouldReceive('get_option')->andReturn(false)->byDefault();
		$this->bridge->shouldReceive('update_option')->byDefault();
		$this->bridge->shouldReceive('do_action')->andReturnNull()->byDefault();
		$this->bridge->shouldReceive('add_action')->byDefault();
		$this->bridge->shouldReceive('apply_filters')->byDefault();
		$this->bridge->shouldReceive('apply_filters')->with('bwp_plugin_default_options', array())->andReturn(array())->byDefault();
		$this->bridge->shouldReceive('add_filter')->byDefault();
		$this->bridge->shouldReceive('register_activation_hook')->byDefault();
		$this->bridge->shouldReceive('register_deactivation_hook')->byDefault();
		$this->bridge->shouldReceive('load_plugin_textdomain')->byDefault();
		$this->bridge->shouldReceive('wp_unslash')->byDefault();

		$this->cache = Mockery::mock('BWP_Cache');

		$this->plugin_version = '1.2.0';

		$this->framework = Mockery::mock('BWP_Framework_V3', array(
			array(
				'title'       => 'BWP Plugin',
				'version'     => $this->plugin_version,
				'wp_version'  => '4.0',
				'php_version' => '5.4',
				'domain'      => 'bwp-plugin'
			),
			$this->bridge,
			$this->cache
		))
		->makePartial()
		->shouldAllowMockingProtectedMethods();

		// mock all protected methods that are not related to testing
		$this->framework->shouldReceive('pre_init_build_constants')->byDefault();
		$this->framework->shouldReceive('pre_init_properties')->byDefault();
		$this->framework->shouldReceive('load_libraries')->byDefault();
		$this->framework->shouldReceive('pre_init_hooks')->byDefault();
		$this->framework->shouldReceive('build_constants')->byDefault();
		$this->framework->shouldReceive('init_shared_properties')->byDefault();
		$this->framework->shouldReceive('init_properties')->byDefault();
		$this->framework->shouldReceive('init_hooks')->byDefault();
		$this->framework->shouldReceive('register_framework_media')->byDefault();
		$this->framework->shouldReceive('enqueue_media')->byDefault();

		$this->default_options = array(
			'option1' => 'option_value1',
			'option2' => 'option_value2'
		);

		$this->plugin_file = '/path/to/plugin/bwp-plugin.php';
	}

	protected function tearDown()
	{
	}

	/**
	 * @covers BWP_Framework_V3::__construct
	 * @covers BWP_Framework_V3::set_version
	 */
	public function test_plugin_versions_are_set_correctly()
	{
		$this->assertEquals($this->plugin_version, $this->framework->get_version());
		$this->assertEquals('4.0', $this->framework->get_version('wp'));
		$this->assertEquals('5.4', $this->framework->get_version('php'));
	}

	/**
	 * @covers BWP_Framework_V3::__construct
	 */
	public function test_plugin_text_domain_is_set_correctly()
	{
		$this->assertEquals('bwp-plugin', $this->framework->domain);
	}

	/**
	 * @covers BWP_Framework_V3::build_properties
	 */
	public function test_plugin_keys_are_set_correctly()
	{
		$this->build_properties();

		$this->assertEquals('bwp_plugin', PHPUnit_Framework_Assert::readAttribute($this->framework, 'plugin_key'));
		$this->assertEquals('BWP_PLUGIN', PHPUnit_Framework_Assert::readAttribute($this->framework, 'plugin_ckey'));
		$this->assertEquals($this->default_options, PHPUnit_Framework_Assert::readAttribute($this->framework, 'options_default'));
		$this->assertEquals($this->plugin_file, PHPUnit_Framework_Assert::readAttribute($this->framework, 'plugin_file'));
		$this->assertEquals('plugin', PHPUnit_Framework_Assert::readAttribute($this->framework, 'plugin_folder'));
	}

	/**
	 * @covers BWP_Framework_V3::build_constants
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_plugin_asset_constants_are_set_correctly()
	{
		$this->bridge->shouldReceive('trailingslashit')->andReturnUsing(function($path) {
			return $path;
		})->byDefault();

		$plugins_url = 'http://example.com/wp-content/plugins/bwp-plugin/';

		$this->bridge->shouldReceive('plugin_dir_url')->andReturnUsing(function($plugin_file) use ($plugins_url) {
			return $plugins_url;
		})->byDefault();

		$this->build_properties();
		$this->framework->build_wp_properties();
		$this->call_protected_method('build_constants');

		$this->assertEquals($plugins_url . 'assets/images', BWP_PLUGIN_IMAGES);
		$this->assertEquals($plugins_url . 'assets/js', BWP_PLUGIN_JS);
		$this->assertEquals($plugins_url . 'assets/css', BWP_PLUGIN_CSS);
	}

	/**
	 * @covers BWP_Framework_V3::build_properties
	 */
	public function test_default_options_can_be_filtered()
	{
		$this->bridge->shouldReceive('apply_filters')->with('bwp_plugin_default_options', array())->andReturn(array(
			'option1' => 'option_value1_filtered',
			'option3' => 'option_value3'
		))->byDefault();

		$this->build_properties();

		$this->assertEquals(array(
			'option1' => 'option_value1_filtered',
			'option2' => 'option_value2',
			'option3' => 'option_value3'
		), PHPUnit_Framework_Assert::readAttribute($this->framework, 'options_default'));
	}

	/**
	 * @covers BWP_Framework_V3::init_actions
	 */
	public function test_init_priority_should_be_filterable_and_default_to_10()
	{
		$this->bridge->shouldReceive('apply_filters')->with('bwp_plugin_default_options', array())->andReturn(array());
		$this->bridge->shouldReceive('apply_filters')->with('bwp_plugin_init_priority', 10)->once();

		$this->build_properties();
	}

	/**
	 * @covers BWP_Framework_V3::build_properties
	 * @covers BWP_Framework_V3::pre_init_actions
	 */
	public function test_pre_init_actions_should_call_necessary_functions_when_build_properties()
	{
		$this->framework->shouldReceive('pre_init_build_constants')->ordered()->once();
		$this->framework->shouldReceive('build_options')->ordered()->once();
		$this->framework->shouldReceive('update_plugin')->with('pre_init')->ordered()->once();
		$this->framework->shouldReceive('pre_init_properties')->ordered()->once();
		$this->framework->shouldReceive('load_libraries')->ordered()->once();
		$this->framework->shouldReceive('pre_init_hooks')->ordered()->once();

		// mock this to test pre_init_actions() only
		$this->framework
			->shouldReceive('init_actions')
			->byDefault();

		$this->build_properties();
	}

	/**
	 * @covers BWP_Framework_V3::build_properties
	 * @covers BWP_Framework_V3::pre_init_actions
	 */
	public function test_pre_init_actions_should_register_activation_hook_when_build_properties()
	{
		$this->bridge->shouldReceive('register_activation_hook')->with($this->plugin_file, array($this->framework, 'install'))->once();
		$this->bridge->shouldReceive('register_deactivation_hook')->with($this->plugin_file, array($this->framework, 'uninstall'))->once();

		$this->build_properties();
	}

	/**
	 * @covers BWP_Framework_V3::build_properties
	 * @covers BWP_Framework_V3::init_actions
	 */
	public function test_init_actions_correctly_when_build_properties()
	{
		$this->bridge->shouldReceive('apply_filters')->with('bwp_plugin_init_priority', 10)->andReturn(10)->byDefault();

		$this->bridge->shouldReceive('add_action')->with('init', array($this->framework, 'build_wp_properties'), 10)->once();
		$this->bridge->shouldReceive('add_action')->with('init', array($this->framework, 'init'), 10)->once();
		$this->bridge->shouldReceive('add_action')->with('admin_init', array($this->framework, 'init_admin_page'), 1)->once();
		$this->bridge->shouldReceive('add_action')->with('admin_menu', array($this->framework, 'init_admin_menu'), 1)->once();

		// mock this to test init_actions() only
		$this->framework
			->shouldReceive('pre_init_actions')
			->byDefault();

		$this->build_properties();
	}

	/**
	 * @covers BWP_Framework_V3::init
	 * @dataProvider get_init_case
	 */
	public function test_init_cases($options_changed)
	{
		$this->framework->shouldReceive('pre_init_update_plugin')->byDefault();
		$this->framework->shouldReceive('pre_init_build_options')->byDefault();

		$this->bridge->shouldReceive('do_action')->with('bwp_plugin_pre_init')->globally()->ordered()->once();

		$this->framework->shouldReceive('build_constants')->globally()->ordered()->once();
		$this->framework->shouldReceive('update_plugin')->with('init')->globally()->ordered()->once();

		$this->framework->shouldReceive('init_shared_properties')->globally()->ordered()->once();
		$this->framework->shouldReceive('init_properties')->globally()->ordered()->once();
		$this->framework->shouldReceive('init_hooks')->globally()->ordered()->once();
		$this->framework->shouldReceive('enqueue_media')->globally()->ordered()->once();

		$this->bridge->shouldReceive('do_action')->with('bwp_plugin_loaded')->globally()->ordered()->once();

		// options have changed
		if ($options_changed) {
			$this->framework->options = array('option1' => 'value1');
			$this->framework->current_options = array('option1' => 'value2');
		}

		$this->build_properties();
		$this->framework->init();

		$this->assertTrue(
			$this->framework->options == $this->framework->current_options,
			'current options and options must always be the same after the init phase'
		);
	}

	public function get_init_case()
	{
		return array(
			array(false),
			array(true)
		);
	}

	/**
	 * @covers BWP_Framework_V3::update_plugin
	 * @dataProvider get_update_plugin_data
	 */
	public function test_update_plugin($when, $db_version, $do_update)
	{
		$this->bridge->shouldReceive('is_admin')->andReturn(true)->byDefault();
		$this->bridge->shouldReceive('get_option')->with('bwp_plugin_version')->andReturn($db_version)->byDefault();

		if ($do_update) {
			if ('pre_init' == $when) {
				$action_hook = 'bwp_plugin_upgrade';

				$this->framework
					->shouldReceive('upgrade_plugin')
					->with($db_version, $this->plugin_version)
					->once()
					->globally()
					->ordered();
			} else {
				$action_hook = 'bwp_plugin_init_upgrade';

				$this->framework
					->shouldReceive('init_upgrade_plugin')
					->with($db_version, $this->plugin_version)
					->once()
					->globally()
					->ordered();
			}

			$this->bridge
				->shouldReceive('do_action')
				->with($action_hook, $db_version, $this->plugin_version)
				->once();

			// update the version in db if it's 'init_upgrade'
			if ('init' == $when) {
				$this->bridge->shouldReceive('update_option')->with('bwp_plugin_version', $this->plugin_version)->once();
			}
		}
		else
		{
			$this->bridge->shouldNotReceive('do_action')->with('bwp_plugin_upgrade', $db_version, $this->plugin_version);
		}

		// mock those below so they don't call BWP_Framework_V3::update_plugin
		$this->framework->shouldReceive('pre_init_update_plugin')->byDefault();
		$this->framework->shouldReceive('init_update_plugin')->byDefault();

		$this->build_properties();
		$this->call_protected_method('update_plugin', $when);
	}

	public function get_update_plugin_data()
	{
		return array(
			array('pre_init', false, true), // no db version yet
			array('pre_init', '1.2.0', false),
			array('pre_init', '1.2.0-beta1', true), // support beta, rc, etc.
			array('pre_init', '1.2.0-rc1', true), // support beta, rc, etc.
			array('pre_init', '1.1.9', true),
			array('init', '1.1.9', true)
		);
	}

	/**
	 * @covers BWP_Framework_V3::build_options
	 * @dataProvider get_plugin_options
	 */
	public function test_build_options($options_default, $db_options, $merged_options, $obsolete)
	{
		$this->bridge->shouldReceive('get_option')->with('bwp_plugin_general')->andReturn($db_options)->byDefault();

		if ($obsolete) {
			foreach (array_diff_key($db_options, $options_default) as $obsolete_key => $value) {
				unset($db_options[$obsolete_key]);
			}

			$this->bridge->shouldReceive('update_option')->with('bwp_plugin_general', $db_options)->once();
		}

		$this->build_properties($options_default);
		$this->call_protected_method('build_options');

		$this->assertEquals($merged_options, $this->framework->options);
		$this->assertEquals($merged_options, $this->framework->current_options);
	}

	public function get_plugin_options()
	{
		return array(
			'no obsolete options' => array(
				array(
					'option1' => 'value1',
					'option2' => 'value2',
					'option3' => 'value3'
				),
				array(
					'option1' => 'db_value1',
					'option2' => 'db_value2'
				),
				array(
					'option1' => 'db_value1',
					'option2' => 'db_value2',
					'option3' => 'value3'
				),
				false
			),
			'with obsolete options' => array(
				array(
					'option1' => 'value1',
					'option3' => 'value3'
				),
				array(
					'option1' => 'db_value1',
					'option2' => 'db_value2'
				),
				array(
					'option1' => 'db_value1',
					'option3' => 'value3'
				),
				true
			)
		);
	}

	/**
	 * @covers BWP_Framework_V3::build_options
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider get_plugin_site_options
	 */
	public function test_build_multisite_options($options, $site_option_keys, $db_site_options, $merged_options, $obsolete)
	{
		$util = Mockery::mock('alias:BWP_Framework_Util');
		$util->shouldReceive('is_multisite')->andReturn(true)->byDefault();

		$this->bridge->shouldReceive('get_option')->with('bwp_plugin_general')->andReturn($options)->byDefault();
		$this->bridge->shouldReceive('get_site_option')->with('bwp_plugin_general')->andReturn($db_site_options)->byDefault();
		$this->framework->site_options = $site_option_keys;

		if ($obsolete) {
			$temp = array();
			foreach ($db_site_options as $k => $o) {
				if (in_array($k, $site_option_keys))
					$temp[$k] = $o;
			}

			$this->bridge->shouldReceive('update_site_option')->with('bwp_plugin_general', $temp)->once();
		}

		$this->build_properties($options);
		$this->call_protected_method('build_options');

		$this->assertEquals($merged_options, $this->framework->options);
		$this->assertEquals($merged_options, $this->framework->current_options);
	}

	public function get_plugin_site_options()
	{
		return array(
			array(
				array(
					'option1' => 'value1',
					'option2' => 'value2',
					'option3' => 'value3'
				),
				array(
					'option1',
					'option2'
				),
				array(
					'option1' => 'db_site_value1',
					'option2' => 'db_site_value2'
				),
				array(
					'option1' => 'db_site_value1',
					'option2' => 'db_site_value2',
					'option3' => 'value3'
				),
				false
			),
			array(
				array(
					'option1' => 'value1',
					'option3' => 'value3',
					'option4' => 'value4'
				),
				array(
					'option1',
					'option4'
				),
				array(
					'option1' => 'db_site_value1',
					'option2' => 'db_site_value2'
				),
				array(
					'option1' => 'db_site_value1',
					'option3' => 'value3',
					'option4' => 'value4'
				),
				true
			)
		);
	}

	/**
	 * @covers BWP_Framework_V3::init_admin_page
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider get_wp_admin_pages
	 */
	public function test_init_admin_page($page, $should_init)
	{
		$_GET['page'] = $page;

		$option_page = Mockery::mock('overload:BWP_Option_Page_V3');

		if ($should_init) {
			$this->framework->shouldReceive('build_option_page')->globally()->ordered()->once();
			$option_page->shouldReceive('handle_form_actions')->globally()->ordered()->once();
		} else {
			$this->framework->shouldNotReceive('build_option_page');
			$option_page->shouldNotReceive('handle_form_actions');
		}

		$this->build_properties();
		$this->framework->init_admin_page();

		if ($should_init) {
			$this->assertInstanceOf('BWP_Option_Page_V3', $this->framework->current_option_page, 'BWP_Framework_V3::current_option_page should be created');
			$this->assertTrue(isset($_SESSION), 'session should be init');
		} else {
			$this->assertNull($this->framework->current_option_page, 'BWP_Framework_V3::current_option_page should NOT be created');
			$this->assertFalse(isset($_SESSION), 'session should NOT be init');
		}

		unset($_GET['page']);
	}

	public function get_wp_admin_pages()
	{
		return array(
			array('bwp_plugin_general', true),
			array('not_plugin_page', false)
		);
	}

	/**
	 * @covers BWP_Framework_V3::update_some_options
	 * @dataProvider get_update_some_options_data
	 */
	public function test_update_some_options($db_options, $new_options)
	{
		$option_key = 'bwp_plugin_general';

		$this->bridge
			->shouldReceive('get_option')
			->with($option_key)
			->andReturn($db_options)
			->byDefault();

		$db_options = !$db_options || !is_array($db_options)
			? array() : $db_options;

		$updated_options = array_merge($db_options, $new_options);

		$this->framework
			->shouldReceive('update_options')
			->with($option_key, $updated_options)
			->once();

		$this->framework
			->shouldReceive('update_site_options')
			->with($option_key, $updated_options)
			->once();

		$this->call_protected_method('update_some_options', array($option_key, $new_options));
	}

	public function get_update_some_options_data()
	{
		return array(
			'invalid db options' => array(
				false,
				array(
					'option1' => 'value1_new'
				),
			),

			'update both blog and site options' => array(
				array(
					'option1'      => 'value1',
					'option2'      => 'value2',
					'site_option1' => 'site_value1',
					'site_option2' => 'site_value2'
				),
				array(
					'option1'      => 'value1_new',
					'site_option1' => 'site_value1_new'
				),
			)
		);
	}

	/**
	 * @covers BWP_Framework_V3::update_options
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider get_test_update_options_cases
	 */
	public function test_update_options(array $options, $is_multisite, $expected)
	{
		$option_key = 'bwp_plugin_general';

		$util = Mockery::mock('alias:BWP_Framework_Util');
		$util->shouldReceive('is_multisite')->andReturn($is_multisite)->byDefault();

		$this->framework->options = array(
			'option1'      => 'value1',
			'option2'      => 'value2',
			'site_option1' => 'site_value1',
			'site_option2' => 'site_value2'
		);

		$this->framework->site_options = array(
			'site_option1',
			'site_option2'
		);

		$this->bridge
			->shouldReceive('update_option')
			->with($option_key, $options)
			->once();

		$this->framework->update_options($option_key, $options);

		$this->assertEquals($expected, $this->framework->options, 'should update $options property');
		$this->assertEquals(array(), $this->framework->current_options, 'should NOT update $current_options property');
	}

	public function get_test_update_options_cases()
	{
		return array(
			'not multisite, update all options' => array(
				array(
					'option1'      => 'value1_new',
					'option2'      => 'value2',
					'site_option1' => 'site_value1_new',
					'site_option2' => 'site_value2'
				),
				false,
				array(
					'option1'      => 'value1_new',
					'option2'      => 'value2',
					'site_option1' => 'site_value1_new',
					'site_option2' => 'site_value2'
				)
			),

			'multisite, update blog options only' => array(
				array(
					'option1'      => 'value1_new',
					'option2'      => 'value2',
					'site_option1' => 'site_value1_new',
					'site_option2' => 'site_value2'
				),
				true,
				array(
					'option1'      => 'value1_new',
					'option2'      => 'value2',
					'site_option1' => 'site_value1',
					'site_option2' => 'site_value2'
				)
			)
		);
	}

	/**
	 * @covers BWP_Framework_V3::update_site_options
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider get_update_site_options_cases
	 */
	public function test_update_site_options($is_multisite_admin, $is_on_main_blog, array $options, array $site_option_keys, $expected)
	{
		$util = Mockery::mock('alias:BWP_Framework_Util');
		$util->shouldReceive('is_multisite_admin')->andReturn($is_multisite_admin)->byDefault();
		$util->shouldReceive('is_on_main_blog')->andReturn($is_on_main_blog)->byDefault();

		$option_key = 'bwp_plugin_general';
		$old_options = array(
			'option1'      => 'value1',
			'option2'      => 'value2',
			'site_option1' => 'site_value1',
			'site_option2' => 'site_value2'
		);

		$this->framework->options = $old_options;

		$this->framework->site_options = $site_option_keys;

		if ($expected) {
			$this->bridge
				->shouldReceive('update_site_option')
				->with($option_key, $expected)
				->once();
		} else {
			// should not update any site options
			$this->bridge
				->shouldNotReceive('update_site_option');
		}

		$this->framework->update_site_options($option_key, $options);

		if ($expected) {
			$this->assertEquals(array_merge($old_options, $expected), $this->framework->options, 'should update $options property with site options');
		}
	}

	public function get_update_site_options_cases()
	{
		return array(
			array(false, false, array(), array(), false),
			array(true, false, array(), array(), false),
			array(false, true, array(), array(), false),

			'no options to update' => array(true, true, array(), array(), false),

			'no site options to update' => array(true, true, array(
				'option1' => 'value1_new',
				'option2' => 'value2',
			), array('site_option1'), false),

			'site options to update' => array(true, true, array(
				'option1'      => 'value1_new',
				'option2'      => 'value2',
				'site_option1' => 'site_value1_new',
				'site_option2' => 'site_value2'
			), array('site_option1', 'site_option2'), array(
				'site_option1' => 'site_value1_new',
				'site_option2' => 'site_value2'
			))
		);
	}

	/**
	 * @covers BWP_Framework_V3::get_current_timezone
	 * @dataProvider get_wp_timezone_settings
	 */
	public function test_get_current_timezone_correctly($timezone_string, $gmt_offset, $is_greater_than_php_50200, $expected)
	{
		$this->cache
			->shouldReceive('get')
			->andReturn(false)
			->byDefault();

		$this->bridge
			->shouldReceive('get_option')
			->with('timezone_string')
			->andReturn($timezone_string)
			->byDefault();

		$this->framework
			->shouldReceive('get_current_php_version')
			->with('5.2.0')
			->andReturn($is_greater_than_php_50200)
			->byDefault();

		$this->bridge
			->shouldReceive('get_option')
			->with('gmt_offset')
			->andReturn($gmt_offset)
			->byDefault();

		$this->assertEquals($expected, $this->framework->get_current_timezone());
	}

	public function get_wp_timezone_settings()
	{
		return array(
			array('Asia/Bangkok', false, true, new DateTimeZone('Asia/Bangkok')),
			array('bad timezone string', false, true, new DateTimeZone('UTC')),
			array('bad timezone string', false, false, new DateTimeZone('UTC')),

			'integer offset' => array(false, 7, true,   new DateTimeZone('Asia/Almaty')),
			'float offset'   => array(false, 7.5, true, new DateTimeZone('Asia/Brunei')),
			'invalid offset' => array(false, 7.7, true, new DateTimeZone('UTC')),
			'php < 5.2.0'    => array(false, 7, false,  new DateTimeZone('UTC')),
		);
	}

	/**
	 * @covers BWP_Framework_V3::get_current_timezone
	 */
	public function test_get_current_timezone_should_return_cached_timezone_when_possible()
	{
		$timezone = new DateTimeZone('UTC');

		$this->cache
			->shouldReceive('get')
			->andReturn($timezone)
			->byDefault();

		$this->assertEquals($timezone, $this->framework->get_current_timezone());
	}

	/**
	 * @covers BWP_Framework_V3::is_option_key_valid
	 * @dataProvider get_test_is_option_key_valid_cases
	 */
	public function test_is_option_key_valid($options, $valid)
	{
		$this->bridge
			->shouldReceive('get_option')
			->andReturn($options)
			->byDefault();

		$this->assertEquals($valid, $this->framework->is_option_key_valid('key'));
	}

	public function get_test_is_option_key_valid_cases()
	{
		return array(
			array(false, false),
			array(null, false),
			array(array(), false),
			array(array('option1' => 'value1', 'option2' => 'value2'), true)
		);
	}

	protected function build_properties(array $options = array())
	{
		$reflection = new ReflectionClass('BWP_Framework_V3');
		$method = $reflection->getMethod('build_properties');
		$method->setAccessible(true);

		$default_options = $options ?: $this->default_options;

		$method->invokeArgs($this->framework, array(
			'BWP_PLUGIN', $default_options, $this->plugin_file, '', false
		));

		$method = $reflection->getMethod('add_option_key');
		$method->setAccessible(true);

		$method->invokeArgs($this->framework, array(
			'bwp_plugin_general', 'bwp_plugin_general', 'General Options'
		));
	}

	protected function call_protected_method($method_name, $params = array())
	{
		$reflection = new ReflectionClass('BWP_Framework_V3');
		$method = $reflection->getMethod($method_name);
		$method->setAccessible(true);

		$params = (array) $params;
		$method->invokeArgs($this->framework, $params);
	}
}
