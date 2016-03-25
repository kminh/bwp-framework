<?php

use \Mockery as Mockery;
use \Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @covers BWP_Option_Page_V3_Test
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Option_Page_V3_Test extends MockeryTestCase
{
	protected $bridge;

	protected $plugin;

	protected $form_name;

	protected $op;

	public function setUp()
	{
		$this->form_name = 'bwp_op';

		$this->bridge = Mockery::mock('BWP_WP_Bridge');

		$this->bridge
			->shouldReceive('t')
			->andReturnUsing(function($key) {
				return $key;
			})
			->byDefault();

		$this->bridge
			->shouldReceive('apply_filters')
			->with('bwp_option_page_submit_form_name', $this->form_name)
			->andReturn($this->form_name)
			->byDefault();

		$this->bridge
			->shouldReceive('apply_filters')
			->with('bwp_option_page_submit_options', Mockery::type('array'))
			->andReturnUsing(function($hook_name, array $options) {
				return $options;
			})
			->byDefault();

		$this->plugin = Mockery::mock('BWP_Framework_V3');
		$this->plugin->shouldReceive('get_bridge')->andReturn($this->bridge)->byDefault();
		$this->plugin->shouldReceive('get_options_by_keys')->andReturn(array())->byDefault();

		$this->plugin->site_options = array(
			'input_site_option',
			'input_site_option2'
		);

		$this->op = new BWP_Option_Page_V3($this->form_name, $this->plugin);
	}

	/**
	 * @covers BWP_Option_Page_V3::init
	 */
	public function test_init_should_throw_exception_if_form_option_keys_also_have_values()
	{
		$this->setExpectedException('LogicException', '$form_option_keys must contain keys only and no values');

		$this->op->init(array(), array('key' => 'value'));
	}

	/**
	 * @covers BWP_Option_Page_V3::init
	 */
	public function test_init_should_return_self_to_support_chanining()
	{
		$this->assertSame($this->op, $this->op->init());
	}

	/**
	 * @covers BWP_Option_Page_V3::add_form_container
	 * @dataProvider get_test_add_form_container_cases
	 */
	public function test_add_form_container(array $form, array $expected)
	{
		$this->op->init($form);
		$this->op->add_form_container('h2', 'some text');

		$this->assertArraySubset($expected, $this->op->get_form());
	}

	public function get_test_add_form_container_cases()
	{
		return array(
			'form with container set for fields' => array(array(
				'container' => array(
					'h2' => ''
				)
			), array(
				'container' => array('h2' => 'some text')
			)),

			'form without container set for fields' => array(array(
				'container' => array()
			), array(
				'container' => array()
			))
		);
	}

	/**
	 * @covers BWP_Option_Page_V3::add_form_inline
	 * @dataProvider get_test_add_form_inline_cases
	 */
	public function test_add_form_inline(array $form, array $expected)
	{
		$this->op->init($form);
		$this->op->add_form_inline('input', 'some inline text');

		$this->assertArraySubset($expected, $this->op->get_form());
	}

	public function get_test_add_form_inline_cases()
	{
		return array(
			'form with inline set for fields' => array(array(
				'inline' => array(
					'input' => ''
				)
			), array(
				'inline' => array('input' => 'some inline text')
			)),

			'form without inline set for fields' => array(array(
				'inline' => array()
			), array(
				'inline' => array()
			))
		);
	}

	/**
	 * @covers BWP_Option_Page_V3::register_custom_submit_action
	 */
	public function test_register_custom_action_correctly()
	{
		$this->assertEmpty(PHPUnit_Framework_Assert::readAttribute($this->op, 'form_actions'));

		$this->op->register_custom_submit_action('flush');
		$this->op->register_custom_submit_action('save');

		$this->assertEquals(array(
			'flush', 'save'
		), PHPUnit_Framework_Assert::readAttribute($this->op, 'form_actions'));
	}

	/**
	 * @covers BWP_Option_Page_V3::register_custom_submit_action
	 * @dataProvider get_invalid_custom_action_callbacks
	 */
	public function test_register_custom_action_should_throw_exception_when_callback_is_not_callable($callback)
	{
		$this->setExpectedException('InvalidArgumentException', 'callback used for action "action" must be null or callable');

		$this->op->register_custom_submit_action('action', $callback);
	}

	public function get_invalid_custom_action_callbacks()
	{
		return array(
			array(false),
			array('bwp_undefined_function'),
			array(array($this, 'undefined_method'))
		);
	}

	/**
	 * @covers BWP_Option_Page_V3::register_custom_submit_action
	 */
	public function test_register_custom_action_should_register_callback_as_well_if_callback_is_callable()
	{
		$callback = function() {};

		$this->bridge
			->shouldReceive('add_filter')
			->with('bwp_option_page_custom_action_action', $callback, 10, 2)
			->once();

		$this->op->register_custom_submit_action('action', $callback);
	}

	/**
	 * @covers BWP_Option_Page_V3::register_custom_submit_actions
	 */
	public function test_register_custom_actions_correctly()
	{
		$actions = array(
			'action1', 'action2'
		);

		$callback = function() {};

		$this->bridge
			->shouldReceive('add_filter')
			->with('/^bwp_option_page_custom_action_action(1|2)$/', $callback, 10, 2)
			->twice();

		$this->op->register_custom_submit_actions($actions, $callback);

		$this->assertEquals($actions, PHPUnit_Framework_Assert::readAttribute($this->op, 'form_actions'));
	}

	/**
	 * @covers BWP_Option_Page_V3::submit_html_form
	 * @dataProvider get_form_data
	 */
	public function test_submit_html_form_should_update_options_correctly($form, $form_option_keys, $form_options, $post_options, $merged_options)
	{
		$this->bridge->shouldReceive('check_admin_referer')->with($this->form_name)->once();

		$this->plugin
			->shouldReceive('get_options_by_keys')
			->with($form_option_keys)
			->andReturn($form_options)
			->byDefault();

		$this->plugin->options_default = array(
			'input3_integer_blank_string' => 1,
			'input3_integer_negative'     => 2,
			'input3_integer_string'       => 3,
			'input3_integer'              => 4,
		);

		$this->plugin
			->shouldReceive('update_some_options')
			->with($this->form_name, $merged_options)
			->once();

		$this->bridge
			->shouldReceive('wp_filter_post_kses')
			->andReturnUsing(function($value) {
				return addslashes($value);
			})
			->byDefault();

		foreach ($post_options as $key => $option) {
			$_POST[$key] = $option;
		}

		$this->op->init($form, $form_option_keys);
		$this->op->submit_html_form();

		$this->assertEquals($merged_options, PHPUnit_Framework_Assert::readAttribute($this->op, 'form_options'), 'should update form options');

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
			'checkbox_multi' => array(
				'checkbox_multi1' => array('' => array())
			),
			'select_multi' => array(
				'select_multi1' => array('' => array())
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
					'checkbox_multi1',
					'select_multi1',
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
					'checkbox_multi1'             => 'checkbox_multi1_value',
					'select_multi1'               => 'select_multi1_value',
					'select1'                     => 'select1_value'
				),
				array(
					'input1'                      => '<span>input1_updated_value</span>', // tags should be stripped
					'input3_integer_blank_string' => '',
					'input3_integer_negative'     => -1,
					'input3_integer_string'       => '3',
					'input3_integer'              => 5,
					'input4_float'                => 2,
					'textarea1'                   => '<p class="class">textarea1_updated_value</p>', // tags should be preserved
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
					'textarea1'                   => '<p class="class">textarea1_updated_value</p>',
					'checkbox1'                   => '', // unchecked checkbox should be empty
					'checkbox_multi1'             => array(), // unchecked multi-checkbox should be an empty array
					'select_multi1'               => array(), // unchecked multi-select should be an empty array
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
		$this->plugin->shouldReceive('update_some_options')->with($this->form_name, $form_options)->once();

		$util = Mockery::mock('alias:BWP_Framework_Util');
		$util->shouldReceive('is_multisite')->andReturn($flags['is_multisite'])->byDefault();
		$util->shouldReceive('is_site_admin')->andReturn($flags['is_site_admin'])->byDefault();
		$util->shouldReceive('is_on_main_blog')->andReturn($flags['is_on_main_blog'])->byDefault();

		$version = Mockery::mock('alias:BWP_Version');
		$version->shouldReceive('get_current_php_version_id')->andReturn($flags['is_php_version'])->byDefault();

		$this->op->init($form, $form_option_keys);
		$this->op->submit_html_form();

		$this->assertEquals($form_options, PHPUnit_Framework_Assert::readAttribute($this->op, 'form_options'), 'should update form options');

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

			'should hide setting for main blog if on sub blog' => array(
				$form,
				array('input_main_blog',),
				array('input_main_blog'   => 'input_main_blog_value',),
				array(
					'is_multisite'    => true,
					'is_site_admin'   => false,
					'is_on_main_blog' => false,
					'is_php_version'  => false
				)
			),

			'should hide setting for sub blog if not multisite' => array(
				$form,
				array('input_sub_blog'),
				array('input_sub_blog' => 'input_sub_blog_value'),
				array(
					'is_multisite'    => false,
					'is_site_admin'   => false,
					'is_on_main_blog' => true,
					'is_php_version'  => false
				)
			),

			'should hide setting for sub blog if on main blog' => array(
				$form,
				array('input_sub_blog'),
				array('input_sub_blog' => 'input_sub_blog_value'),
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
	 */
	public function test_submit_html_form_in_multisite_should_update_options_correctly()
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

		$util = Mockery::mock('alias:BWP_Framework_Util');
		$util->shouldReceive('is_multisite')->andReturn(true)->byDefault();
		$util->shouldReceive('is_site_admin')->andReturn(true)->byDefault();
		$util->shouldReceive('is_on_main_blog')->andReturn(true)->byDefault();

		$this->plugin->shouldReceive('get_options_by_keys')->with($form_option_keys)->andReturn($form_options)->byDefault();
		$this->plugin->shouldReceive('update_some_options')->with($this->form_name, $post_options)->once();

		$this->op->init($form, $form_option_keys);
		$this->op->submit_html_form();

		$_POST = array();
	}

	/**
	 * @covers BWP_Option_Page_V3::submit_html_form
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

		$this->plugin->shouldReceive('update_some_options')->with('bwp_op_general', Mockery::type('array'))->once();

		$this->op->init();
		$this->op->submit_html_form();
	}

	/**
	 * @covers BWP_Option_Page_V3::submit_html_form
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider get_form_options_with_filtered_options
	 */
	public function test_submit_html_form_should_allow_filtering_submitted_options_before_updating($form_options, $post_options, $filtered_form_options)
	{
		foreach ($post_options as $name => $value) {
			$_POST[$name] = $value;
		}

		$this->bridge->shouldReceive('check_admin_referer')->with($this->form_name)->once();

		$this->plugin
			->shouldReceive('get_options_by_keys')
			->with(Mockery::type('array'))
			->andReturn($form_options)
			->byDefault();

		$merged_options = array_merge($post_options, $filtered_form_options);

		$this->bridge
			->shouldReceive('apply_filters')
			->with('bwp_option_page_submit_options', $post_options)
			->andReturn($merged_options)
			->byDefault();

		$util = Mockery::mock('alias:BWP_Framework_Util');
		$util->shouldReceive('is_on_main_blog')->andReturn(true)->byDefault();
		$util->shouldReceive('is_site_admin')->andReturn(true)->byDefault();

		// should update filtered options
		$this->plugin
			->shouldReceive('update_some_options')
			->with($this->form_name, $merged_options)
			->once();

		$this->op->init(array(), array('input3', 'input4', 'input_site_option'));
		$this->op->submit_html_form();

		$this->assertEquals(
			$merged_options, PHPUnit_Framework_Assert::readAttribute($this->op, 'form_options'), 'should update form options with merged options'
		);

		$_POST = array();
	}

	/**
	 * @covers BWP_Option_Page_V3::submit_html_form
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider get_form_options_with_filtered_options
	 */
	public function test_submit_html_form_should_update_form_options_with_post_options_if_filtered_options_is_invalid($form_options, $post_options)
	{
		foreach ($post_options as $name => $value) {
			$_POST[$name] = $value;
		}

		$this->bridge->shouldReceive('check_admin_referer')->with($this->form_name)->once();

		$this->plugin
			->shouldReceive('get_options_by_keys')
			->with(Mockery::type('array'))
			->andReturn($form_options)
			->byDefault();

		$this->bridge
			->shouldReceive('apply_filters')
			->with('bwp_option_page_submit_options', $post_options)
			->andReturn(false)
			->byDefault();

		$util = Mockery::mock('alias:BWP_Framework_Util');
		$util->shouldReceive('is_on_main_blog')->andReturn(true)->byDefault();
		$util->shouldReceive('is_site_admin')->andReturn(true)->byDefault();

		$this->op->init(array(), array('input3', 'input4', 'input_site_option'));
		$this->op->submit_html_form();

		$this->assertEquals(
			$post_options, PHPUnit_Framework_Assert::readAttribute($this->op, 'form_options'), 'should update form options with post options'
		);

		$_POST = array();
	}

	public function get_form_options_with_filtered_options()
	{
		return array(array(
			array(
				'input3'            => 'input3_value',
				'input4'            => 'input4_value',
				'input_site_option' => 'input_site_option_value'
			),
			array(
				'input3'            => 'input3_value_updated',
				'input4'            => 'input4_value_updated',
				'input_site_option' => 'input_site_option_value_updated'
			),
			array(
				'input4'             => 'input4_value_updated_filtered',
				'input5'             => 'input5_value',
				'input6'             => 'input6_value',
				'input_site_option2' => 'input_site_option2_value'
			)
		));
	}

	/**
	 * @covers BWP_Option_Page_V3::handle_form_actions
	 * @dataProvider get_form_actions
	 */
	public function test_handle_form_actions_main_cases($actions, $post_action, $redirect)
	{
		foreach ($actions as $action) {
			$this->op->register_custom_submit_action($action);
		}

		$_POST[$post_action] = 1;

		$this->bridge
			->shouldReceive('check_admin_referer')
			->with($this->form_name)
			->once();

		if ('submit_bwp_op' == $post_action) {
			$this->plugin->shouldReceive('update_some_options')->once();

			$this->bridge
				->shouldReceive('apply_filters')
				->with('bwp_option_page_action_submitted', true)
				->andReturn($redirect)
				->byDefault();
		} else {
			$this->bridge
				->shouldReceive('apply_filters')
				->with('bwp_option_page_custom_action_' . $post_action, true, $post_action)
				->andReturn($redirect)
				->byDefault();
		}

		if ($redirect) {
			if ('submit_bwp_op' == $post_action) {
				$this->plugin
					->shouldReceive('add_notice_flash')
					->with('All options have been saved.')
					->ordered()
					->once();
			}

			$this->plugin->shouldReceive('safe_redirect')->ordered()->once();
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

	/**
	 * @covers BWP_Option_Page_V3::handle_form_actions
	 */
	public function test_handle_form_actions_should_not_add_notice_flash_or_redirect_when_the_form_was_not_submitted_successfully()
	{
		$_POST['submit_bwp_op'] = 1;

		$this->bridge->shouldReceive('check_admin_referer')->with($this->form_name)->once();

		$this->bridge
			->shouldReceive('apply_filters')
			->with('bwp_option_page_submit_form_name', $this->form_name)
			->andReturn($this->form_name);

		$this->bridge
			->shouldReceive('apply_filters')
			->with('bwp_option_page_submit_options', Mockery::type('array'))
			->andReturn(false);

		$this->plugin->shouldNotReceive('update_some_options');

		$this->plugin
			->shouldNotReceive('add_notice_flash')
			->with('All options have been saved.');

		$this->bridge
			->shouldNotReceive('apply_filters')
			->with('bwp_option_page_action_submitted', true);

		$this->plugin->shouldNotReceive('safe_redirect');

		$this->op->handle_form_actions();

		$_POST = array();
	}
}
