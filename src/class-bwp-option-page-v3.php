<?php
/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

class BWP_Option_Page_V3
{
	/**
	 * The form
	 */
	protected $form;

	/**
	 * The form name
	 */
	protected $form_name;

	/**
	 * Tabs to build
	 */
	protected $form_tabs;

	/**
	 * Current tab
	 */
	protected $current_tab;

	/**
	 * This holds the form items, determining the position
	 */
	protected $form_items = array();

	/**
	 * This holds the name for each items (an item can have more than one fields)
	 */
	protected $form_item_names = array();

	/**
	 * This holds the form label
	 */
	protected $form_item_labels = array();

	/**
	 * This holds the form option aka data
	 */
	protected $form_options = array();
	protected $site_options = array();

	/**
	 * Actions associated with this form, in addition to the default submit
	 * action
	 *
	 * @var array
	 * @since rev 144
	 */
	protected $form_actions = array();

	/**
	 * The plugin that initializes this option page instance
	 *
	 * @var BWP_Framework_V3
	 */
	protected $plugin;

	/**
	 * @var BWP_WP_Bridge
	 */
	protected $bridge;

	/**
	 * Text domain
	 */
	protected $domain;

	/**
	 * Constructor
	 *
	 * @param string $form_name
	 * @param BWP_Framework_V3 $plugin
	 */
	public function __construct($form_name, BWP_Framework_V3 $plugin)
	{
		$this->form_name    = $form_name;
		$this->form_tabs    = $plugin->form_tabs;
		$this->site_options = $plugin->site_options;
		$this->domain       = $plugin->domain;

		$this->plugin = $plugin;
		$this->bridge = $plugin->get_bridge();

		if (sizeof($this->form_tabs) == 0)
			$this->form_tabs = array($this->bridge->t('Plugin Configurations', $this->domain));
	}

	/**
	 * Init the class
	 *
	 * @param array $form             form data to build the form for the current option page
	 * @param array $form_option_keys contains all option keys that should be handled by the current option page form
	 * @return $this
	 */
	public function init($form = array(), $form_option_keys = array())
	{
		$this->form             = $form;
		$this->form_items       = isset($form['items']) ? $form['items'] : array();
		$this->form_item_names  = isset($form['item_names']) ? $form['item_names'] : array();
		$this->form_item_labels = isset($form['item_labels']) ? $form['item_labels'] : array();

		// we only support option keys and not key and value pairs
		if (array_values($form_option_keys) !== $form_option_keys)
			throw new LogicException('$form_option_keys must contain keys only and no values');

		$this->form_options = $form_option_keys ? $this->plugin->get_options_by_keys($form_option_keys) : array();

		$this->form['formats'] = isset($this->form['formats'])
			? $this->form['formats']
			: array();

		return $this;
	}

	/**
	 * Set options to be used for the currently active form
	 *
	 * This allows setting arbitrary options that might not be associated with
	 * an option key.
	 *
	 * @param array $options
	 */
	public function set_form_options(array $options)
	{
		$this->form_options = $options;
	}

	/**
	 * Add a container for a specific field of the current form
	 *
	 * @param string $name name of the field
	 * @param string $container_data data of the container
	 */
	public function add_form_container($name, $container_data)
	{
		if (!isset($this->form['container']) || !is_array($this->form['container']))
		{
			$this->form['container'] = array();
		}

		$this->form['container'][$name] = $container_data;
	}

	public function get_form_name()
	{
		return $this->form_name;
	}

	public function get_form()
	{
		return $this->form;
	}

	public function set_current_tab($current_tab = 0)
	{
		$this->current_tab = $current_tab;
	}

	public function kill_html_fields(&$form, $names)
	{
		$ids   = array();
		$names = (array) $names;

		foreach ($form['item_names'] as $key => $name)
		{
			if (in_array($name, $names))
				$ids[] = $key;
		}

		$in_keys = array(
			'items',
			'item_labels',
			'item_names'
		);

		foreach ($ids as $id)
		{
			foreach ($in_keys as $key)
				unset($form[$key][$id]);
		}
	}

	/**
	 * Generate HTML form
	 */
	public function generate_html_form()
	{
		$return_str = '<div class="wrap" style="padding-bottom: 20px;">' . "\n";

		if (sizeof($this->form_tabs) >= 2)
			$return_str .= apply_filters('bwp-admin-form-icon', '<div class="icon32" id="icon-options-general"><br></div>'  . "\n");
		else
			$return_str .= '<div class="icon32" id="icon-options-general"><br></div>';

		if (sizeof($this->form_tabs) >= 2)
		{
			$count = 0;

			$return_str .= '<h2 class="bwp-option-page-tabs">' . "\n";
			$return_str .= apply_filters('bwp-admin-plugin-version', '') . "\n";

			foreach ($this->form_tabs as $title => $link)
			{
				$count++;

				$active      = $count == $this->current_tab ? ' nav-tab-active' : '';
				$return_str .= '<a class="nav-tab' . $active . '" href="' . $link . '">' . $title . '</a>' . "\n";
			}

			$return_str .= '</h2>' . "\n";
		}
		else if (!isset($this->form_tabs[0]))
		{
			$title       = array_keys($this->form_tabs);
			$return_str .= '<h2>' . $title[0] . '</h2>'  . "\n";
		}
		else
			$return_str .= '<h2>' . $this->form_tabs[0] . '</h2>'  . "\n";

		$return_str .= apply_filters('bwp_option_before_form', '');
		echo $return_str;

		do_action('bwp_option_action_before_form');

		$return_str  = '';
		$return_str .= '<form class="bwp-option-page" name="' . $this->form_name . '" method="post" action="">'  . "\n";

		if (function_exists('wp_nonce_field'))
		{
			echo $return_str;

			wp_nonce_field($this->form_name);

			$return_str = '';
		}

		$return_str .= '<ul>' . "\n";

		// generate filled form
		if (isset($this->form_items) && is_array($this->form_items))
		{
			foreach ($this->form_items as $key => $type)
			{
				$name = !empty($this->form_item_names[$key])
					? $this->form_item_names[$key]
					: '';

				// this form item should not be shown
				if ($this->is_form_item_hidden($name)) {
					continue;
				}

				if (!empty($name) && !empty($this->form_item_labels[$key])
				) {
					$return_str .= '<li class="bwp-clear">'
						. $this->generate_html_fields($type, $name)
						. '</li>'
						. "\n";
				}
			}
		}

		$return_str .= '</ul>' . "\n";
		$return_str .= apply_filters('bwp_option_before_submit_button', '');

		echo $return_str;
		do_action('bwp_option_action_before_submit_button');

		$return_str  = '';
		$return_str .= apply_filters('bwp_option_submit_button',
			'<p class="submit"><input type="submit" class="button-primary" name="submit_'
			. $this->form_name . '" value="' . $this->bridge->t('Save Changes') . '" /></p>') . "\n";

		$return_str .= '</form>' . "\n";
		$return_str .= '</div>' . "\n";

		echo $return_str;
	}

	/**
	 * Register a custom submit action
	 *
	 * @param string $action the POST action
	 * @since rev 144
	 */
	public function register_custom_submit_action($action)
	{
		$this->form_actions[] = $action;
	}

	public function submit_html_form()
	{
		// basic security check
		$this->bridge->check_admin_referer($this->form_name);

		$options = $this->form_options;
		$option_formats = $this->form['formats'];

		foreach ($options as $name => &$option)
		{
			// if this form item is hidden, it should not be handled here
			if ($this->is_form_item_hidden($name))
				continue;

			if (isset($_POST[$name]))
			{
				// make sure options are in expected formats
				$option = $this->format_field($name, $_POST[$name]);
			}

			if (isset($this->form['checkbox'][$name]) && !isset($_POST[$name])
			) {
				// unchecked checkbox
				$option = '';
			}
		}

		// allow the current form to save its submitted data using a different
		// form name
		$form_name = $this->bridge->apply_filters('bwp_option_page_submit_form_name', $this->form_name);

		// allow filtering the options that are going to be updated
		$options = $this->bridge->apply_filters('bwp_option_page_submit_options', $options);

		// allow plugin to return false or non-array to not update any options at all
		if ($options === false || !is_array($options))
			return false;

		// update per-blog options
		$this->plugin->update_options($form_name, $options);

		// update site options
		$this->plugin->update_site_options($form_name, $options);

		// refresh the options for the form
		$this->form_options = array_merge($this->form_options, $options);

		return true;
	}

	/**
	 * Handles all kinds of form actions, including the default submit action
	 *
	 * @since rev 144
	 */
	public function handle_form_actions()
	{
		// handle the default submit action
		if (isset($_POST['submit_' . $this->get_form_name()]))
		{
			// add a notice and allow redirection only when the form is
			// submitted successully
			if ($this->submit_html_form())
			{
				// allow plugin to choose to not redirect
				$redirect = $this->bridge->apply_filters('bwp_option_page_action_submitted', true);

				if ($redirect !== false)
				{
					$this->plugin->add_notice_flash($this->bridge->t('All options have been saved.', $this->domain));
					$this->plugin->safe_redirect();
				}
			}
		}
		else
		{
			foreach ($this->form_actions as $action)
			{
				if (isset($_POST[$action]))
				{
					// basic security check
					$this->bridge->check_admin_referer($this->form_name);

					$redirect = $this->bridge->apply_filters('bwp_option_page_custom_action_' . $action, true);

					if ($redirect !== false)
						$this->plugin->safe_redirect();
				}
			}
		}
	}

	protected function format_field($name, $value)
	{
		$format = isset($this->form['formats'][$name])
			? $this->form['formats'][$name]
			: '';

		$value = trim(stripslashes($value));

		if (!empty($format))
		{
			if ('int' == $format)
			{
				// 'int' is understood as not a blank string and greater than 0
				if ('' === $value || 0 > $value)
					return $this->plugin->options_default[$name];

				return (int) $value;
			}
			else if ('float' == $format)
				return (float) $value;
			else if ('html' == $format)
				return $this->bridge->wp_filter_post_kses($value);
		}
		else
			return strip_tags($value);
	}

	/**
	 * Generate HTML field
	 */
	protected function generate_html_field($type = '', $data = array(), $name = '', $in_section = false)
	{
		$pre_html_field  = '';
		$post_html_field = '';

		$checked  = 'checked="checked" ';
		$selected = 'selected="selected" ';

		$value = isset($this->form_options[$name])
			? $this->form_options[$name]
			: '';

		$value = isset($data['value']) ? $data['value'] : $value;

		if ('checkbox' == $type)
		{
			$value = current(array_values($data));
			$value = $value ? $value : 'yes';
		}

		$value = !empty($this->domain)
			&& ('textarea' == $type || 'input' == $type)
			? $this->bridge->t($value, $this->domain)
			: $value;

		if (is_array($value))
		{
			foreach ($value as &$v)
				$v = is_array($v) ? array_map('esc_attr', $v) : esc_attr($v);
		}
		else
		{
			$value = 'textarea' == $type
				? esc_html($value)
				: esc_attr($value);
		}

		$array_replace = array();
		$array_search  = array(
			'size',
			'name',
			'value',
			'cols',
			'rows',
			'label',
			'disabled',
			'pre',
			'post'
		);

		$return_html   = '';

		$br = isset($this->form['inline_fields'][$name])
			&& is_array($this->form['inline_fields'][$name])
			? ''
			: "<br />\n";

		$pre   = !empty($data['pre']) ? $data['pre'] : '';
		$post  = !empty($data['post']) ? $data['post'] : '';

		$param = empty($this->form['params'][$name])
			? false : $this->form['params'][$name];

		$name_attr = esc_attr($name);

		switch ($type)
		{
			case 'heading':
				$html_field = '%s';
			break;

			case 'input':
				$html_field = !$in_section
					? '%pre%<input%disabled% size="%size%" type="text" '
						. 'id="' . $name_attr . '" '
						. 'name="' . $name_attr . '" '
						. 'value="' . $value . '" /> <em>%label%</em>'
					: '<label for="' . $name_attr . '">%pre%<input%disabled% size="%size%" type="text" '
						. 'id="' . $name_attr . '" '
						. 'name="' . $name_attr . '" '
						. 'value="' . $value . '" /> <em>%label%</em></label>';
			break;

			case 'select':
			case 'select_multi':
				$pre_html_field = 'select_multi' == $type
					? '%pre%<select id="' . $name_attr . '" name="' . $name_attr . '[]" multiple>' . "\n"
					: '%pre%<select id="' . $name_attr . '" name="' . $name_attr . '">' . "\n";

				$html_field = '<option %selected%value="%value%">%option%</option>';

				$post_html_field = '</select>%post%' . $br;
			break;

			case 'checkbox':
				$html_field = '<label for="%name%">'
					. '<input %checked%type="checkbox" id="%name%" name="%name%" value="yes" /> %label%</label>';
			break;

			case 'checkbox_multi':
				$html_field = '<label for="%name%-%value%">'
					. '<input %checked%type="checkbox" id="%name%-%value%" name="%name%[]" value="%value%" /> %label%</label>';
			break;

			case 'radio':
				$html_field = '<label>' . '<input %checked%type="radio" '
					. 'name="' . $name_attr . '" value="%value%" /> %label%</label>';
			break;

			case 'textarea':
				$html_field = '%pre%<textarea%disabled% '
					. 'id="' . $name_attr . '" '
					. 'name="' . $name_attr . '" cols="%cols%" rows="%rows%">'
					. $value . '</textarea>%post%';
			break;
		}

		if (!isset($data))
			return;

		if ($type == 'heading' && !is_array($data))
		{
			$return_html .= sprintf($html_field, $data) . $br;
		}
		else if ($type == 'radio'
			|| $type == 'checkbox' || $type == 'checkbox_multi'
			|| $type == 'select' || $type == 'select_multi'
		) {
			foreach ($data as $key => $value)
			{
				if ($type == 'checkbox')
				{
					// handle checkbox a little bit differently
					if ($this->form_options[$name] == 'yes')
					{
						$return_html .= str_replace(
							array('%value%', '%name%', '%label%', '%checked%'),
							array($value, $name_attr, $key, $checked),
							$html_field
						);

						$return_html .= apply_filters('bwp_option_after_' . $type . '_' . $name . '_checked', '', $value, $param);
						$return_html .= $br;
					}
					else
					{
						$return_html .= str_replace(
							array('%value%', '%name%', '%label%', '%checked%'),
							array($value, $name_attr, $key, ''),
							$html_field
						);

						$return_html .= apply_filters('bwp_option_after_' . $type . '_' . $name, '', $value, $param);
						$return_html .= $br;
					}
				}
				else if ($type == 'checkbox_multi')
				{
					// handle a multi checkbox differently
					if (isset($this->form_options[$name])
						&& is_array($this->form_options[$name])
						&& (in_array($value, $this->form_options[$name])
							|| array_key_exists($value, $this->form_options[$name]))
					) {
						$return_html .= str_replace(
							array('%value%', '%name%', '%label%', '%checked%'),
							array($value, $name_attr, $key, $checked),
							$html_field
						);

						$return_html .= apply_filters('bwp_option_after_' . $type . '_' . $name . '_checked', '', $value, $param);
						$return_html .= $br;
					}
					else
					{
						$return_html .= str_replace(
							array('%value%', '%name%', '%label%', '%checked%'),
							array($value, $name_attr, $key, ''),
							$html_field
						);

						$return_html .= apply_filters('bwp_option_after_' . $type . '_' . $name, '', $value, $param);
						$return_html .= $br;
					}
				}
				else if (isset($this->form_options[$name])
					&& ($this->form_options[$name] == $value
						|| (is_array($this->form_options[$name])
							&& (in_array($value, $this->form_options[$name])
								|| array_key_exists($value, $this->form_options[$name]))))
				) {
					$item_br = $type == 'select' || $type == 'select_multi' ? "\n" : $br;

					$return_html .= str_replace(
						array('%value%', '%name%', '%label%', '%option%', '%checked%', '%selected%', '%pre%', '%post%'),
						array($value, $name_attr, $key, $key, $checked, $selected, $pre, $post),
						$html_field
					) . $item_br;
				}
				else
				{
					$item_br = $type == 'select' || $type == 'select_multi' ? "\n" : $br;

					$return_html .= str_replace(
						array('%value%', '%name%', '%label%', '%option%', '%checked%', '%selected%', '%pre%', '%post%'),
						array($value, $name_attr, $key, $key, '', '', $pre, $post),
						$html_field
					) . $item_br;
				}
			}
		}
		else
		{
			foreach ($array_search as &$keyword)
			{
				$array_replace[$keyword] = '';

				if (!empty($data[$keyword]))
				{
					$array_replace[$keyword] = $data[$keyword];
				}

				$keyword = '%' . $keyword . '%';
			}

			$return_html = str_replace($array_search, $array_replace, $html_field) . $br;
		}

		// inline fields
		$inline_html = '';
		if (isset($this->form['inline_fields'][$name]) && is_array($this->form['inline_fields'][$name]))
		{
			foreach ($this->form['inline_fields'][$name] as $field => $field_type)
			{
				if (isset($this->form[$field_type][$field]))
					$inline_html = ' ' . $this->generate_html_field($field_type, $this->form[$field_type][$field], $field, $in_section);
			}
		}

		// html after field
		$post = !empty($this->form['post'][$name])
			? ' ' . $this->form['post'][$name]
			: $post;

		return str_replace('%pre%', $pre, $pre_html_field) . $return_html . str_replace('%post%', $post, $post_html_field) . $inline_html;
	}

	/**
	 * Generate HTML fields
	 */
	protected function generate_html_fields($type, $name)
	{
		$item_label  = '';
		$return_html = '';

		$item_key = array_keys($this->form_item_names, $name);

		$input_class = $type == 'heading'
			? 'bwp-option-page-heading-desc'
			: 'bwp-option-page-inputs';

		// an inline item can hold any HTML markup, example is to display some
		// kinds of button right be low the label
		$inline = '';

		if (isset($this->form['inline']) && is_array($this->form['inline'])
			&& array_key_exists($name, $this->form['inline'])
		) {
			$inline = empty($this->form['inline'][$name]) ? '' : $this->form['inline'][$name];
		}

		$inline .= "\n";

		switch ($type)
		{
			case 'section':
				if (!isset($this->form[$name]) || !is_array($this->form[$name]))
					return;

				$item_label = '<span class="bwp-opton-page-label">'
					. $this->form_item_labels[$item_key[0]]
					. $inline
					. '</span>';

				foreach ($this->form[$name] as $section_field)
				{
					$type = $section_field[0];
					$name = $section_field['name'];

					if (isset($this->form[$section_field[0]]))
					{
						$return_html .= $this->generate_html_field($section_field[0], $this->form[$type][$name], $name, true);
					}
				}
			break;

			default:
				if (!isset($this->form[$type][$name])
					|| ($type != 'heading' && !is_array($this->form[$type][$name])))
					return;

				$item_label = $type != 'checkbox' && $type != 'checkbox_multi' && $type != 'radio'
					? '<label class="bwp-opton-page-label" for="' . $name . '">'
						. $this->form_item_labels[$item_key[0]] . $inline
						. '</label>'
					: '<span class="bwp-opton-page-label type-' . $type . '">'
						. $this->form_item_labels[$item_key[0]] . $inline
						. '</span>';

				$item_label = $type == 'heading'
					? '<h3>' . $this->form_item_labels[$item_key[0]] . '</h3>' . $inline
					: $item_label;

				if (isset($this->form[$type]))
					$return_html = $this->generate_html_field($type, $this->form[$type][$name], $name);
			break;
		}

		// a container can hold some result executed by customized script,
		// such as displaying something when user press the submit button
		$containers = '';

		if (isset($this->form['container'])
			&& is_array($this->form['container'])
			&& array_key_exists($name, $this->form['container'])
		) {
			$container_array = (array) $this->form['container'][$name];

			foreach ($container_array as $container)
			{
				$containers .= empty($container)
					? '<div style="display: none;"><!-- --></div>'
					: '<div class="bwp-clear">' . $container . '</div>' . "\n";
			}
		}

		$pure_return = trim(strip_tags($return_html));

		if (empty($pure_return) && $type == 'heading')
		{
			return $item_label . $containers;
		}
		else
		{
			return $item_label . '<p class="' . $input_class . '">'
				. $return_html . '</p>'
				. $containers;
		}
	}

	protected function is_form_item_hidden($name)
	{
		if (isset($this->form['env'])
			&& array_key_exists($name, $this->form['env'])
			&& $this->form['env'][$name] == 'multisite'
			&& !BWP_Framework_V3::is_multisite()
		) {
			// hide multisite field if not in multisite environment
			return true;
		}

		if (isset($this->form['php'])
			&& array_key_exists($name, $this->form['php'])
			&& !BWP_Version::get_current_php_version_id($this->form['php'][$name])
		) {
			// hide field if the current PHP version requirement is not satisfied
			return true;
		}

		if (isset($this->form['role'])
			&& array_key_exists($name, $this->form['role'])
			&& $this->form['role'][$name] == 'superadmin'
			&& (!BWP_Framework_V3::is_site_admin() || !BWP_Framework_V3::is_on_main_blog())
		) {
			// hide site-admin-only settings if not a site admin or not on
			// main blog
			return true;
		}

		/* if (isset($this->form['callback']) */
		/* 	&& array_key_exists($name, $this->form['callback']) */
		/* 	&& is_callable($this->form['callback'][$name]) */
		/* 	&& !call_user_func($this->form['callback'][$name], $name) */
		/* ) { */
		/* 	// a condition not satisfied, hide the field */
		/* 	return true; */
		/* } */

		if (in_array($name, $this->site_options)
			&& (!BWP_Framework_V3::is_site_admin() || !BWP_Framework_V3::is_on_main_blog())
		) {
			// hide site-admin-only settings if not a site admin or not on
			// main blog
			return true;
		}

		if (isset($this->form['blog'])
			&& array_key_exists($name, $this->form['blog'])
			&& BWP_Framework_V3::is_multisite()
		) {
			if ($this->form['blog'][$name] == 'main' && !BWP_Framework_V3::is_on_main_blog())
			{
				// this field should be on main blog only
				return true;
			}
			elseif ($this->form['blog'][$name] == 'sub' && BWP_Framework_V3::is_on_main_blog())
			{
				// this field should be on sub blogs only
				return true;
			}
		}

		return false;
	}

	public function get_form_name()
	{
		return $this->form_name;
	}

	public function get_form()
	{
		return $this->form;
	}
}
