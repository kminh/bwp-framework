<?php

use \Mockery as Mockery;

/**
 * @covers BWP_Option_Page_V3_Test
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Option_Page_V3_Test extends \PHPUnit_Framework_TestCase
{
	protected $bridge;

	protected $plugin;

	protected $form_name;

	protected $op;

	protected function setUp()
	{
		$this->form_name = 'bwp_op';

		$this->bridge = Mockery::mock('BWP_WP_Bridge');
		$this->bridge->shouldReceive('t')->andReturnUsing(function($key) {
			return $key;
		})->byDefault();
		$this->bridge->shouldReceive('apply_filters')->with('bwp_option_page_submit_form_name', $this->form_name)->andReturn($this->form_name)->byDefault();

		$this->plugin = Mockery::mock('BWP_Framework_V3');
		$this->plugin->shouldReceive('get_bridge')->andReturn($this->bridge)->byDefault();
		$this->plugin->shouldReceive('get_options_by_keys')->andReturn(array())->byDefault();

		$this->plugin->site_options = array(
			'input_site_option',
			'input_site_option2'
		);

		$this->op = new BWP_Option_Page_V3($this->form_name, $this->plugin);
	}

	protected function tearDown()
	{
	}

	/**
	 * @covers BWP_Option_Page_V3::add_form_container
	 */
	public function test_add_form_container()
	{
		$this->op->init();
		$this->op->add_form_container('h2', 'some text');

		$this->assertEquals(array(
			'container' => array('h2' => 'some text'),
			'formats' => array()
		), $this->op->get_form());
	}

	/**
	 * @covers BWP_Option_Page_V3::register_custom_submit_action
	 */
	public function test_register_custom_action()
	{
		$this->assertEmpty(PHPUnit_Framework_Assert::readAttribute($this->op, 'form_actions'));

		$this->op->register_custom_submit_action('flush');
		$this->op->register_custom_submit_action('save');

		$this->assertEquals(array(
			'flush', 'save'
		), PHPUnit_Framework_Assert::readAttribute($this->op, 'form_actions'));
	}

	/**
	 * @covers BWP_Option_Page_V3::submit_html_form
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_submit_html_form_can_use_custom_form_name()
	{
		$this->bridge->shouldReceive('check_admin_referer')->with($this->form_name)->once();
		$this->plugin->shouldReceive('get_options_by_keys')->with(array())->andReturn(array(
			'input3'             => 'input3_value',
			'input4'             => 'input4_value',
			'input_site_option'  => 'input_site_option_value'
		))->byDefault();

		// use a custom form name to save options
		$this->bridge->shouldReceive('apply_filters')->with('bwp_option_page_submit_form_name', $this->form_name)->andReturn('bwp_op_general')->byDefault();
		$this->bridge->shouldReceive('update_option')->with('bwp_op_general', Mockery::type('array'))->once();

		$util = Mockery::mock('alias:BWP_Framework_Util');
		$util->shouldReceive('is_site_admin')->andReturn(true)->byDefault();
		$util->shouldReceive('is_on_main_blog')->andReturn(true)->byDefault();
		$util->shouldReceive('can_update_site_option')->andReturn(true)->byDefault();

		$this->bridge->shouldReceive('update_site_option')->with('bwp_op_general', Mockery::type('array'))->once();

		$this->op->init();
		$this->op->submit_html_form();
	}

	/**
	 * @covers BWP_Option_Page_V3::submit_html_form
	 * @dataProvider get_form_data
	 */
	public function test_submit_html_form_should_update_options_correctly($form, $form_option_keys, $form_options, $post_options, $merged_options)
	{
		$this->bridge->shouldReceive('check_admin_referer')->with($this->form_name)->once();

		$this->plugin->shouldReceive('get_options_by_keys')->with($form_option_keys)->andReturn($form_options)->byDefault();
		$this->plugin->options_default = array(
			'input3_integer_blank_string' => 1,
			'input3_integer_negative'     => 2,
			'input3_integer_string'       => 3,
			'input3_integer'              => 4,
		);

		$this->bridge->shouldReceive('wp_filter_post_kses')->andReturnUsing(function($value) {
			return $value;
		});
		$this->bridge->shouldReceive('update_option')->with($this->form_name, $merged_options)->once();

		foreach ($post_options as $key => $option) {
			$_POST[$key] = $option;
		}

		$this->op->init($form, $form_option_keys);
		$this->op->submit_html_form();

		$this->assertEquals($merged_options, PHPUnit_Framework_Assert::readAttribute($this->op, 'form_options'), 'should update form options');
		$this->assertEquals($merged_options, $this->plugin->options, 'should update plugin options');

		$_POST = array();
	}

	public function get_form_data()
	{
		$form = array(
			'items' => array(
			),
			'item_labels' => array(
			),
			'item_names' => array(
			)
		);

		$form1 = array_merge($form, array(
			'checkbox' => array(
				'checkbox1' => array('' => '')
			),
			'input' => array(
				'input2_disabled' => array(
					'disabled' => true
				)
			),
			'formats' => array(
				'input3_integer_blank_string' => 'int',
				'input3_integer_negative'     => 'int',
				'input3_integer_string'       => 'int',
				'input3_integer'              => 'int',
				'input4_float'                => 'float',
				'textarea1'                   => 'html'
			)
		));

		return array(
			array(
				$form1,
				array(
					'input1',
					'input2_disabled',
					'input3_integer_blank_string',
					'input3_integer_negative',
					'input3_integer_string',
					'input3_integer',
					'input4_float',
					'textarea1',
					'checkbox1',
					'select1'
				),
				array(
					'input1'                      => 'input1_value',
					'input2_disabled'             => 'input2_value',
					'input3_integer_blank_string' => 10,
					'input3_integer_negative'     => 20,
					'input3_integer_string'       => 30,
					'input3_integer'              => 40,
					'input4_float'                => 1.0,
					'textarea1'                   => 'textarea1_value',
					'checkbox1'                   => 'checkbox1_value',
					'select1'                     => 'select1_value'
				),
				array(
					'input1'                      => '<span>input1_updated_value</span>', // tags should be stripped
					'input3_integer_blank_string' => '',
					'input3_integer_negative'     => -1,
					'input3_integer_string'       => '3',
					'input3_integer'              => 5,
					'input4_float'                => 2,
					'textarea1'                   => '<p>textarea1_updated_value</p>', // tags should be preserved
					'select1'                     => 'select1_updated_value'
				),
				array(
					'input1'                      => 'input1_updated_value',
					'input2_disabled'             => 'input2_value', // disabled input should retain its current value
					'input3_integer_blank_string' => 1,
					'input3_integer_negative'     => 2,
					'input3_integer_string'       => 3,
					'input3_integer'              => 5,
					'input4_float'                => 2.0,
					'textarea1'                   => '<p>textarea1_updated_value</p>',
					'checkbox1'                   => '', // unchecked checkbox should be empty
					'select1'                     => 'select1_updated_value'
				)
			)
		);
	}

	/**
	 * @covers BWP_Option_Page_V3::submit_html_form
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider get_multisite_form_data
	 */
	public function test_submit_html_form_in_multisite_should_not_process_hidden_fields($form, $form_option_keys, $form_options, $flags)
	{
		$this->bridge->shouldReceive('check_admin_referer')->with($this->form_name)->once();

		foreach ($form_options as $key => $value) {
			$_POST[$key] = $value . '_updated';
		}

		$this->plugin->shouldReceive('get_options_by_keys')->with($form_option_keys)->andReturn($form_options)->byDefault();

		// all fields are hidden so the options used to update are the same
		// ones that are feed to the form from the beginning
		$this->bridge->shouldReceive('update_option')->with($this->form_name, $form_options)->once();

		$util = Mockery::mock('alias:BWP_Framework_Util');
		$util->shouldReceive('is_multisite')->andReturn($flags['is_multisite'])->byDefault();
		$util->shouldReceive('is_site_admin')->andReturn($flags['is_site_admin'])->byDefault();
		$util->shouldReceive('is_on_main_blog')->andReturn($flags['is_on_main_blog'])->byDefault();
		$util->shouldReceive('can_update_site_option')->andReturn(false)->byDefault();

		$version = Mockery::mock('alias:BWP_Version');
		$version->shouldReceive('get_current_php_version_id')->andReturn($flags['is_php_version'])->byDefault();

		$this->op->init($form, $form_option_keys);
		$this->op->submit_html_form();

		$this->assertEquals($form_options, PHPUnit_Framework_Assert::readAttribute($this->op, 'form_options'), 'should update form options');
		$this->assertEquals($form_options, $this->plugin->options, 'should update plugin options');

		$_POST = array();
	}

	public function get_multisite_form_data()
	{
		$form = array(
			'items' => array(
			),
			'item_labels' => array(
			),
			'item_names' => array(
			),
			'env' => array(
				'input_multisite' => 'multisite'
			),
			'php' => array(
				'input_php_version' => '50302' // require PHP version 5.3.2
			),
			'role' => array(
				'input_superadmin' => 'superadmin'
			),
			'blog' => array(
				'input_main_blog' => 'main',
				'input_sub_blog'  => 'sub'
			)
		);

		return array(
			array(
				$form,
				array(
					'input_multisite',
					'input_superadmin',
					'input_site_option',
					'input_php_version'
				),
				array(
					'input_multisite'   => 'input_multisite_value',
					'input_superadmin'  => 'input_superadmin_value',
					'input_site_option' => 'input_site_option_value',
					'input_php_version' => 'input_php_version_value'
				),
				array(
					'is_multisite'    => false,
					'is_site_admin'   => false,
					'is_on_main_blog' => true,
					'is_php_version'  => false
				)
			),
			array(
				$form,
				array(
					'input_main_blog',
				),
				array(
					'input_main_blog'   => 'input_main_blog_value',
				),
				array(
					'is_multisite'    => true,
					'is_site_admin'   => false,
					'is_on_main_blog' => false,
					'is_php_version'  => false
				)
			),
			array(
				$form,
				array(
					'input_sub_blog'
				),
				array(
					'input_sub_blog' => 'input_sub_blog_value'
				),
				array(
					'is_multisite'    => true,
					'is_site_admin'   => false,
					'is_on_main_blog' => true,
					'is_php_version'  => false
				)
			)
		);
	}

	/**
	 * @covers BWP_Option_Page_V3::submit_html_form
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider get_submit_html_form_multisite_flags
	 */
	public function test_submit_html_form_in_multisite_should_update_options_correctly($can_update)
	{
		$this->bridge->shouldReceive('check_admin_referer')->with($this->form_name)->once();

		$form = array(
			'items' => array(
			),
			'item_labels' => array(
			),
			'item_names' => array(
			)
		);

		$form_option_keys = array(
			'input_option',
			'input_site_option',
			'input_site_option2'
		);

		$form_options = array(
			'input_option'       => 'option_value',
			'input_site_option'  => 'site_option_value1',
			'input_site_option2' => 'site_option_value2'
		);

		$post_options = array(
			'input_option'       => 'option_value_updated',
			'input_site_option'  => 'site_option_value1_updated',
			'input_site_option2' => 'site_option_value2_updated'
		);

		foreach ($post_options as $key => $option) {
			$_POST[$key] = $option;
		}

		$this->plugin->shouldReceive('get_options_by_keys')->with($form_option_keys)->andReturn($form_options)->byDefault();

		$this->bridge->shouldReceive('update_option')->with($this->form_name, $post_options)->once();

		$util = Mockery::mock('alias:BWP_Framework_Util');
		$util->shouldReceive('is_multisite')->andReturn(true)->byDefault();
		$util->shouldReceive('is_site_admin')->andReturn(true)->byDefault();
		$util->shouldReceive('is_on_main_blog')->andReturn(true)->byDefault();
		$util->shouldReceive('can_update_site_option')->andReturn($can_update)->byDefault();

		array_shift($post_options);
		if ($can_update) {
			$this->bridge->shouldReceive('update_site_option')->with($this->form_name, $post_options)->once();
		} else {
			$this->bridge->shouldNotReceive('update_site_option')->with($this->form_name, $post_options);
		}

		$this->op->init($form, $form_option_keys);
		$this->op->submit_html_form();

		$_POST = array();
	}

	public function get_submit_html_form_multisite_flags()
	{
		return array(
			array(false),
			array(true),
		);
	}

	/**
	 * @covers BWP_Option_Page_V3::handle_form_actions
	 * @dataProvider get_form_actions
	 */
	public function test_handle_form_actions($actions, $post_action, $redirect)
	{
		foreach ($actions as $action) {
			$this->op->register_custom_submit_action($action);
		}

		$_POST[$post_action] = 1;

		$this->bridge->shouldReceive('check_admin_referer')->with($this->form_name)->once();

		if ('submit_bwp_op' == $post_action) {
			$this->bridge->shouldReceive('update_option')->once();
			$this->bridge->shouldReceive('apply_filters')->with('bwp_option_page_action_submitted', true)->andReturn($redirect)->byDefault();
			$this->plugin->shouldReceive('add_notice_flash')->with('All options have been saved.')->once();
		} else {
			$this->bridge->shouldReceive('apply_filters')->with('bwp_option_page_custom_action_' . $post_action, true)->andReturn($redirect)->byDefault();
		}

		if ($redirect) {
			$this->plugin->shouldReceive('safe_redirect')->once();
		} else {
			$this->plugin->shouldNotReceive('safe_redirect');
		}

		$this->op->handle_form_actions();

		$_POST = array();
	}

	public function get_form_actions()
	{
		return array(
			array(array(), 'submit_bwp_op', true),
			array(array(), 'submit_bwp_op', false),
			array(array('save1', 'save2'), 'save1', true),
			array(array('save1', 'save2'), 'save2', false),
		);
	}
}
