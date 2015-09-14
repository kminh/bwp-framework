<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

abstract class BWP_Framework_V3
{
	/**
	 * Hold plugin options
	 *
	 * This should return the most up-to-date options, even after a form submit
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Hold plugin default options
	 *
	 * @var array
	 */
	public $options_default = array();

	/**
	 * Hold plugin site options (applied to whole site)
	 *
	 * @var array
	 */
	public $site_options = array();

	/**
	 * Hold plugin current options
	 *
	 * This should be used to get an option before it is modified by a form
	 * submit
	 *
	 * @var array
	 */
	public $current_options;

	/**
	 * Hold db option keys
	 */
	public $option_keys = array();

	/**
	 * Hold extra option keys
	 */
	public $extra_option_keys = array();

	/**
	 * Hold option pages
	 */
	public $option_pages = array();

	/**
	 * The current option page instance
	 *
	 * @var BWP_Option_Page_V3
	 */
	public $current_option_page;

	/**
	 * Key to identify plugin
	 */
	public $plugin_key;

	/**
	 * Constant Key to identify plugin
	 */
	public $plugin_ckey;

	/**
	 * Domain Key to identify plugin
	 */
	public $plugin_dkey;

	/**
	 * Title of the plugin
	 */
	public $plugin_title;

	/**
	 * Homepage of the plugin
	 */
	public $plugin_url;

	/**
	 * Urls to various parts of homepage or other places
	 *
	 * Expect to have a format of array('relative' => bool, 'url' => url)
	 */
	public $urls = array();

	/**
	 * Plugin file
	 */
	public $plugin_file;

	/**
	 * Plugin folder
	 */
	public $plugin_folder;

	/**
	 * Plugin WP url
	 */
	public $plugin_wp_url;

	/**
	 * Version of the plugin
	 */
	public $plugin_ver = '';

	/**
	 * Message shown to user (Warning, Notes, etc.)
	 */
	public $notices = array();
	public $notice_shown = false;

	/**
	 * Error shown to user
	 */
	public $errors = array();
	public $error_shown = false;

	/**
	 * Capabilities to manage this plugin
	 */
	public $plugin_cap = 'manage_options';

	/**
	 * Whether or not to create filter for media paths
	 */
	public $need_media_filters;

	/**
	 * Form tabs to build
	 */
	public $form_tabs = array();

	/**
	 * Version constraints
	 */
	public $wp_ver;
	public $php_ver;

	/**
	 * Number of framework revisions
	 */
	public $revision = 149;

	/**
	 * Text domain
	 */
	public $domain = '';

	/**
	 * Other special variables
	 */
	protected $_menu_under_settings = false;
	protected $_simple_menu = false;

	/**
	 * The bridge to WP
	 *
	 * @var BWP_WP_Bridge
	 * @since rev 145
	 */
	protected $bridge;

	/**
	 * Construct a new plugin with appropriate meta
	 *
	 * @param array $meta
	 * @param BWP_WP_Bridge $bridge optional, default to null
	 * @since rev 142
	 */
	public function __construct(array $meta, BWP_WP_Bridge $bridge = null)
	{
		$required = array(
			'title', 'version', 'domain'
		);

		foreach ($required as $required_meta)
		{
			if (!array_key_exists($required_meta, $meta))
			{
				throw new InvalidArgumentException(sprintf('Missing required meta (%s) to construct plugin', $required_meta));
			}
		}

		$this->plugin_title = $meta['title'];

		$this->set_version(isset($meta['php_version']) ? $meta['php_version'] : BWP_Version::$php_ver, 'php');
		$this->set_version(isset($meta['wp_version']) ? $meta['wp_version'] : BWP_Version::$wp_ver, 'wp');
		$this->set_version($meta['version']);

		$this->domain = $meta['domain'];

		$this->bridge = $bridge ? $bridge : new BWP_WP_Bridge();
	}

	/**
	 * Build base properties
	 */
	protected function build_properties($key, array $options, $plugin_file = '', $plugin_url = '', $need_media_filters = true)
	{
		$this->plugin_key  = strtolower($key);
		$this->plugin_ckey = strtoupper($key);
		$this->plugin_url  = $plugin_url;

		// @since rev 146 we allow filtering the default options when the
		// plugin is init
		$this->options_default = array_merge($options, $this->bridge->apply_filters($this->plugin_key . '_default_options', array()));

		$this->need_media_filters = (boolean) $need_media_filters;

		$this->plugin_file = $plugin_file;
		$this->plugin_folder = basename(dirname($plugin_file));

		$this->pre_init_actions();
		$this->init_actions();

		// Load locale
		$this->bridge->load_plugin_textdomain($this->domain, false, $this->plugin_folder . '/languages');
	}

	protected function add_option_key($key, $option, $title)
	{
		$this->option_keys[$key] = $option;
		$this->option_pages[$key] = $title;
	}

	protected function add_extra_option_key($key, $option, $title)
	{
		$this->extra_option_keys[$key] = $option;
		$this->option_pages[$key] = $title;
	}

	public function add_icon()
	{
		return '<div class="icon32" id="icon-bwp-plugin" '
			. 'style=\'background-image: url("'
			. constant($this->plugin_ckey . '_IMAGES')
			. '/icon_menu_32.png");\'><br></div>'  . "\n";
	}

	protected function set_version($ver = '', $type = '')
	{
		switch ($type)
		{
			case '': $this->plugin_ver = $ver;
			break;
			case 'php': $this->php_ver = $ver;
			break;
			case 'wp': $this->wp_ver = $ver;
			break;
		}
	}

	public function get_version($type = '')
	{
		switch ($type)
		{
			case '': return $this->plugin_ver;
			break;
			case 'php': return $this->php_ver;
			break;
			case 'wp': return $this->wp_ver;
			break;
		}
	}

	protected function get_current_wp_version()
	{
		return $this->bridge->get_bloginfo('version');
	}

	protected function check_required_versions()
	{
		if (version_compare(PHP_VERSION, $this->php_ver, '<')
			|| version_compare($this->get_current_wp_version(), $this->wp_ver, '<')
		) {
			$this->bridge->add_action('admin_notices', array($this, 'warn_required_versions'));
			$this->bridge->add_action('network_admin_notices', array($this, 'warn_required_versions'));
			return false;
		}
		else
			return true;
	}

	public function warn_required_versions()
	{
		BWP_Version::warn_required_versions($this->plugin_title, $this->domain, $this->php_ver, $this->wp_ver);
	}

	public function show_donation()
	{
		$info_showable     = $this->bridge->apply_filters('bwp_info_showable', true);
		$donation_showable = $this->bridge->apply_filters('bwp_donation_showable', true);
		$ad_showable       = $this->bridge->apply_filters('bwp_ad_showable', true);

		if (true == $info_showable || self::is_multisite_admin())
		{
?>
<div id="bwp-info-place">
<div id="bwp-donation" style="margin-bottom: 0px;">
<a href="<?php echo $this->plugin_url; ?>"><?php echo $this->plugin_title; ?></a> <small>v<?php echo $this->plugin_ver; ?></small><br />
<small>
	<a href="<?php echo str_replace('/wordpress-plugins/', '/topic/', $this->plugin_url); ?>"><?php _e('Development Log', $this->domain); ?></a>
	&ndash;
	<a href="<?php echo $this->plugin_url . 'faq/'; ?>" title="<?php _e('Frequently Asked Questions', $this->domain) ?>"><?php _e('FAQ', $this->domain); ?></a>
	&ndash;
	<a href="http://betterwp.net/contact/" title="<?php _e('Got a problem? Send me a feedback!', $this->domain) ?>"><?php _e('Contact', $this->domain); ?></a>
</small>
<br />
<?php
		if (true == $donation_showable || self::is_multisite_admin())
		{
?>
<small><?php _e('You can buy me some special coffees if you appreciate my work, thank you!', $this->domain); ?></small>
<form class="paypal-form" action="https://www.paypal.com/cgi-bin/webscr" method="post">
<p>
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="NWBB8JUDW5VSY">
<input type="hidden" name="lc" value="VN">
<input type="hidden" name="button_subtype" value="services">
<input type="hidden" name="no_note" value="0">
<input type="hidden" name="cn" value="Would you like to say anything to me?">
<input type="hidden" name="no_shipping" value="1">
<input type="hidden" name="rm" value="1">
<input type="hidden" name="return" value="http://betterwp.net">
<input type="hidden" name="currency_code" value="USD">
<input type="hidden" name="bn" value="PP-BuyNowBF:icon-paypal.gif:NonHosted">
<input type="hidden" name="item_name" value="<?php printf(__('Donate to %s', $this->domain), $this->plugin_title); ?>" />
<select name="amount">
	<option value="5.00"><?php _e('One cup $5.00', $this->domain); ?></option>
	<option value="10.00"><?php _e('Two cups $10.00', $this->domain); ?></option>
	<option value="25.00"><?php _e('Five cups! $25.00', $this->domain); ?></option>
	<option value="50.00"><?php _e('One LL-cup!!! $50.00', $this->domain); ?></option>
	<option value="100.00"><?php _e('... or any amount!', $this->domain); ?></option>
</select>
<span class="paypal-alternate-input" style="display: none;"><!-- --></span>
<input class="paypal-submit" type="image" src="<?php echo $this->plugin_wp_url . 'vendor/kminh/bwp-framework/assets/option-page/images/icon-paypal.gif'; ?>" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</p>
</form>
<?php
		}
?>
</div>
<div class="bwp-separator">
	<div style="height: 10px; width: 5px; background-color: #cccccc; margin: 0 auto;"><!-- --></div>
</div>
<div id="bwp-contact">
	<a class="bwp-rss" href="http://feeds.feedburner.com/BetterWPnet"><?php _e('Latest updates from BetterWP.net!', $this->domain); ?></a>
	<a class="bwp-twitter" href="http://twitter.com/0dd0ne0ut"><?php _e('Follow me on Twitter!', $this->domain); ?></a>
</div>
<?php
		if (true == $ad_showable)
		{
?>
<div class="bwp-separator">
	<div style="height: 10px; width: 5px; background-color: #cccccc; margin: 0 auto;"><!-- --></div>
</div>
<div id="bwp-ads">
	<p><strong><?php _e('This Plugin is Proudly Sponsored By', $this->domain); ?></strong></p>
	<div style="width: 250px; margin: 0 auto;">
		<a href="http://bit.ly/bwp-layer-themes" target="_blank">
			<img src="<?php echo $this->plugin_wp_url . 'vendor/kminh/bwp-framework/assets/option-page/images/ad_lt_250x250.png'; ?>" />
		</a>
	</div>
</div>
<?php
		}
?>
</div>
<?php
		}
	}

	public function show_version()
	{
		if (empty($this->plugin_ver)) return '';

		return '<a class="nav-tab version" title="'
			. sprintf(esc_attr(__('You are using version %s!', $this->domain)), $this->plugin_ver)
			. '">' . $this->plugin_ver . '</a>';
	}

	protected function pre_init_actions()
	{
		$this->pre_init_build_constants();
		$this->pre_init_update_plugin();
		$this->pre_init_build_options();
		$this->pre_init_properties();
		$this->load_libraries();
		$this->pre_init_hooks();

		// Support installation and uninstallation
		$this->bridge->register_activation_hook($this->plugin_file, array($this, 'install'));
		$this->bridge->register_deactivation_hook($this->plugin_file, array($this, 'uninstall'));
	}

	protected function init_actions()
	{
		// @since rev 140, sometimes we need to hook to the 'init' action with
		// a specific priority
		$init_priority = $this->bridge->apply_filters($this->plugin_key . '_init_priority', 10);

		$this->bridge->add_action('init', array($this, 'build_wp_properties'), $init_priority);
		$this->bridge->add_action('init', array($this, 'init'), $init_priority);

		// register backend hooks
		$this->bridge->add_action('admin_init', array($this, 'init_admin_page'), 1);
		$this->bridge->add_action('admin_menu', array($this, 'init_admin_menu'), 1);
	}

	public function init()
	{
		$this->bridge->do_action($this->plugin_key . '_pre_init');

		$this->build_constants();
		$this->init_update_plugin();
		$this->init_build_options();
		$this->init_properties();
		$this->init_hooks();
		$this->enqueue_media();

		$this->bridge->do_action($this->plugin_key . '_loaded');

		// icon 32px
		if ($this->is_admin_page())
		{
			$this->bridge->add_filter('bwp-admin-form-icon', array($this, 'add_icon'));
			$this->bridge->add_filter('bwp-admin-plugin-version', array($this, 'show_version'));
			$this->bridge->add_action('bwp_option_action_before_form', array($this, 'show_donation'), 12);
		}
	}

	public function build_wp_properties()
	{
		// set the plugin WP url here so it can be filtered
		if (defined('BWP_USE_SYMLINKS'))
			// make use of symlinks on development environment
			$this->plugin_wp_url = $this->bridge->trailingslashit($this->bridge->plugins_url($this->plugin_folder));
		else
			// this should allow other package to include BWP plugins while
			// retaining correct URLs pointing to assets
			$this->plugin_wp_url = $this->bridge->trailingslashit($this->bridge->plugin_dir_url($this->plugin_file));
	}

	protected function pre_init_build_constants()
	{
		// only build constants once
		if (defined($this->plugin_ckey . '_PLUGIN_URL'))
			return;

		// url to plugin bwp website
		define($this->plugin_ckey . '_PLUGIN_URL', $this->plugin_url);
		// the capability needed to configure this plugin
		define($this->plugin_ckey . '_CAPABILITY', $this->plugin_cap);

		// define registered option keys, to be used when building option pages
		// and build options
		foreach ($this->option_keys as $key => $option)
		{
			define(strtoupper($key), $option);
		}
		foreach ($this->extra_option_keys as $key => $option)
		{
			define(strtoupper($key), $option);
		}
	}

	protected function build_constants()
	{
		// only build constants once
		if (defined($this->plugin_ckey . '_IMAGES'))
			return;

		// these constants are only available once plugin_wp_url is available
		if (true == $this->need_media_filters)
		{
			define($this->plugin_ckey . '_IMAGES',
				$this->bridge->apply_filters($this->plugin_key . '_image_path',
				$this->plugin_wp_url . 'assets/images'));
			define($this->plugin_ckey . '_CSS',
				$this->bridge->apply_filters($this->plugin_key . '_css_path',
				$this->plugin_wp_url . 'assets/css'));
			define($this->plugin_ckey . '_JS',
				$this->bridge->apply_filters($this->plugin_key . '_js_path',
				$this->plugin_wp_url . 'assets/js'));
		}
		else
		{
			define($this->plugin_ckey . '_IMAGES', $this->plugin_wp_url . 'assets/images');
			define($this->plugin_ckey . '_CSS', $this->plugin_wp_url . 'assets/css');
			define($this->plugin_ckey . '_JS', $this->plugin_wp_url . 'assets/js');
		}
	}

	protected function pre_init_build_options()
	{
		$this->build_options();
	}

	protected function init_build_options()
	{
		// don't rebuild options if there's no different between current
		// options and the actual options
		if ($this->options == $this->current_options)
			return;

		$this->build_options();
	}

	protected function build_options()
	{
		// Get all options and merge them
		$options = $this->options_default;

		foreach ($this->option_keys as $option)
		{
			$db_options = $this->bridge->get_option($option);
			$db_options = self::normalize_options($db_options);
			$option_need_update = false;

			// check for obsolete keys and remove them from db
			if ($obsolete_keys = array_diff_key($db_options, $this->options_default))
			{
				foreach ($obsolete_keys as $obsolete_key => $value) {
					unset($db_options[$obsolete_key]);
				}

				$option_need_update = true;
			}

			$options = array_merge($options, $db_options);
			if ($option_need_update)
				$this->bridge->update_option($option, $options);

			// also check for global options if in Multi-site
			if (self::is_multisite())
			{
				$db_options = $this->bridge->get_site_option($option);
				$db_options = self::normalize_options($db_options);
				$site_option_need_update = false;

				// merge site options into the options array, overwrite
				// any options with same keys
				$temp = array();
				foreach ($db_options as $k => $o)
				{
					if (in_array($k, $this->site_options))
					{
						$temp[$k] = $o;
					}
					else
					{
						// remove obsolete options
						$site_option_need_update = true;
						unset($db_options[$k]);
					}
				}

				$options = array_merge($options, $temp);

				if ($site_option_need_update)
					$this->bridge->update_site_option($option, $temp);
			}
		}

		$this->options         = $options;
		$this->current_options = $options;
	}

	/**
	 * Update options with a specific key
	 *
	 * @param string $option_key
	 * @param array $options
	 */
	protected function update_plugin_options($option_key, array $new_options)
	{
		$db_options = $this->bridge->get_option($option_key);

		if (!$db_options || !is_array($db_options))
			return;

		$db_options = array_merge($db_options, $new_options);

		$this->bridge->update_option($option_key, $db_options);
	}

	/**
	 * Get current options by their keys
	 *
	 * @param array $option_keys
	 */
	public function get_options_by_keys(array $option_keys)
	{
		$options = array();

		foreach ($option_keys as $key) {
			if (array_key_exists($key, $this->options)) {
				$options[$key] = $this->options[$key];
			}
		}

		return $options;
	}

	protected function pre_init_properties()
	{
		/* intentionally left blank */
	}

	protected function init_properties()
	{
		/* intentionally left blank */
	}

	protected function load_libraries()
	{
		/* intentionally left blank */
	}

	protected function update_plugin($when = '')
	{
		if (!$this->bridge->is_admin())
			return;

		$current_version = $this->plugin_ver;
		$db_version = $this->bridge->get_option($this->plugin_key . '_version');

		$action_hook = 'pre_init' == $when
			? $this->plugin_key . '_upgrade'
			: $this->plugin_key . '_init_upgrade';

		if (!$db_version || version_compare($db_version, $current_version, '<'))
		{
			// fire an action to allow plugins to update themselves
			$this->bridge->do_action($action_hook, $db_version, $current_version);

			// only mark as upgraded when this is init update
			if ('init' == $when)
				$this->bridge->update_option($this->plugin_key . '_version', $current_version);
		}
	}

	protected function pre_init_update_plugin()
	{
		$this->update_plugin('pre_init');
	}

	protected function init_update_plugin()
	{
		$this->update_plugin('init');
	}

	protected function pre_init_hooks()
	{
		/* intentionally left blank */
	}

	protected function init_hooks()
	{
		/* intentionally left blank */
	}

	protected function enqueue_media()
	{
		/* intentionally left blank */
	}

	public function install()
	{
		/* intentionally left blank */
	}

	public function uninstall()
	{
		/* intentionally left blank */
	}

	protected function is_admin_page($page = '')
	{
		if ($this->bridge->is_admin() && !empty($_GET['page'])
			&& (in_array($_GET['page'], $this->option_keys)
				|| in_array($_GET['page'], $this->extra_option_keys))
			&& (empty($page)
				|| (!empty($page) && $page == $_GET['page']))
		) {
			return true;
		}
	}

	protected function get_current_admin_page()
	{
		if ($this->is_admin_page()) {
			return $this->bridge->wp_unslash($_GET['page']);
		}

		return '';
	}

	public function get_admin_page_url($page = '')
	{
		$page = $page ? $page : $this->get_current_admin_page();
		$option_script = !$this->_menu_under_settings && !$this->_simple_menu
			? 'admin.php'
			: 'options-general.php';

		return $this->bridge->add_query_arg(array('page' => $page), admin_url($option_script));
	}

	/**
	 * Redirect internally
	 *
	 * @param mixed string|null $url default to current admin page
	 */
	public function safe_redirect($url = null)
	{
		$this->bridge->wp_safe_redirect($this->get_admin_page_url());
		exit;
	}

	public function plugin_action_links($links, $file)
	{
		$option_keys = array_values($this->option_keys);

		if (false !== strpos($this->bridge->plugin_basename($this->plugin_file), $file))
		{
			$links[] = '<a href="' . $this->get_admin_page_url($option_keys[0]) . '">'
				. __('Settings') . '</a>';
		}

		return $links;
	}

	private function init_session()
	{
		if (!isset($_SESSION) || (function_exists('session_status') && session_status() === PHP_SESSION_NONE))
		{
			// do not init a session if headers are already sent
			if (headers_sent())
				return;

			session_start();
		}
	}

	public function init_admin_page()
	{
		if ($this->is_admin_page())
		{
			$this->current_option_page = new BWP_Option_Page_V3(
				$this->get_current_admin_page(), $this
			);

			$this->init_session();
			$this->build_option_page();
			$this->current_option_page->handle_form_actions();

			$notices = $this->get_flash('notice');
			$errors  = $this->get_flash('error');

			foreach ($notices as $notice) {
				$this->add_notice($notice);
			}

			foreach ($errors as $error) {
				$this->add_error($error);
			}
		}
	}

	public function init_admin_menu()
	{
		$this->_menu_under_settings = $this->bridge->apply_filters('bwp_menus_under_settings', false);

		$this->bridge->add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2);

		if ($this->is_admin_page())
		{
			// build tabs
			$this->build_tabs();

			// enqueue style sheets and scripts for the option page
			$this->bridge->wp_enqueue_style(
				'bwp-option-page',
				$this->plugin_wp_url . 'vendor/kminh/bwp-framework/assets/option-page/css/bwp-option-page.css',
				self::is_multisite() || class_exists('JCP_UseGoogleLibraries') ? array('wp-admin') : array(),
				'1.1.0'
			);

			$this->bridge->wp_enqueue_script(
				'bwp-paypal-js',
				$this->plugin_wp_url . 'vendor/kminh/bwp-framework/assets/option-page/js/paypal.js',
				array('jquery')
			);
		}

		$this->build_menus();
	}

	/**
	 * Build the Menus
	 */
	protected function build_menus()
	{
		/* intentionally left blank */
	}

	protected function build_tabs()
	{
		$option_script = !$this->_menu_under_settings
			? 'admin.php'
			: 'options-general.php';

		foreach ($this->option_pages as $key => $page)
		{
			$pagelink = !empty($this->option_keys[$key])
				? $this->option_keys[$key]
				: $this->extra_option_keys[$key];

			$this->form_tabs[$page] = $this->bridge->admin_url($option_script)
				. '?page=' . $pagelink;
		}
	}

	/**
	 * Build the option pages
	 */
	protected function build_option_page()
	{
		/* intentionally left blank */
	}

	public function show_option_page()
	{
		/* filled by plugin */
	}

	/**
	 * Add a flash message that is shown only once
	 *
	 * @since rev 144
	 * @param string $key the key to group this message
	 * @param string $message the message to display
	 * @param bool $append append to the group or replace
	 */
	protected function add_flash($key, $message, $append = true)
	{
		if (!isset($_SESSION))
			return;

		$flash_key = 'bwp_op_flash_' . $key;

		if (!isset($_SESSION[$flash_key]) || !is_array($_SESSION[$flash_key]))
			$_SESSION[$flash_key] = array();

		if ($append)
			$_SESSION[$flash_key][] = $message;
		else
			$_SESSION[$flash_key] = array($message);
	}

	public function add_notice_flash($message, $append = true)
	{
		$this->add_flash('notice', $message, $append);
	}

	public function add_error_flash($message, $append = true)
	{
		$this->add_flash('error', $message, $append);
	}

	/**
	 * Get all flash messages that share a key
	 *
	 * @since rev 144
	 * @return array
	 */
	protected function get_flash($key)
	{
		$flash_key = 'bwp_op_flash_' . $key;

		if (!isset($_SESSION[$flash_key]))
		{
			$flashes = array();
		}
		else
		{
			$flashes =  (array) $_SESSION[$flash_key];
			unset($_SESSION[$flash_key]);
		}

		return $flashes;
	}

	public function add_notice($notice)
	{
		if (!in_array($notice, $this->notices))
		{
			$this->notices[] = $notice;
			$this->bridge->add_action('bwp_option_action_before_form', array($this, 'show_notices'));
		}
	}

	public function show_notices()
	{
		if (false == $this->notice_shown)
		{
			foreach ($this->notices as $notice)
			{
				echo '<div class="updated fade"><p>' . $notice . '</p></div>';
			}
			$this->notice_shown = true;
		}
	}

	public function add_error($error)
	{
		if (!in_array($error, $this->errors))
		{
			$this->errors[] = $error;
			$this->bridge->add_action('bwp_option_action_before_form', array($this, 'show_errors'));
		}
	}

	public function show_errors()
	{
		if (false == $this->error_shown)
		{
			foreach ($this->errors as $error)
			{
				echo '<div class="error"><p>' . $error . '</p></div>';
			}
			$this->error_shown = true;
		}
	}

	public function add_url($key, $url, $relative = true)
	{
		$this->urls[$key] = array(
			'relative' => $relative,
			'url' => $url
		);
	}

	public function get_url($key)
	{
		if (isset($this->urls[$key]))
		{
			$url = $this->urls[$key];
			if ($url['relative'])
				return $this->bridge->trailingslashit($this->plugin_url) . $url['url'];

			return $url['url'];
		}

		return '';
	}


	/**
	 * @return BWP_WP_Bridge
	 */
	public function get_bridge()
	{
		return $this->bridge;
	}

	public static function is_multisite()
	{
		return BWP_Framework_Util::is_multisite();
	}

	public static function is_subdomain_install()
	{
		return BWP_Framework_Util::is_subdomain_install();
	}

	public static function is_super_admin()
	{
		return BWP_Framework_Util::is_super_admin();
	}

	public static function is_site_admin()
	{
		return BWP_Framework_Util::is_site_admin();
	}

	public static function is_multisite_admin()
	{
		return BWP_Framework_Util::is_multisite_admin();
	}

	public static function is_on_main_blog()
	{
		return BWP_Framework_Util::is_on_main_blog();
	}

	public static function can_update_site_option()
	{
		return BWP_Framework_Util::can_update_site_option();
	}

	public static function is_apache()
	{
		return BWP_Framework_Util::is_apache();
	}

	public static function is_nginx()
	{
		return BWP_Framework_Util::is_nginx();
	}

	protected function add_cap($cap)
	{
		$this->plugin_cap = $cap;
	}

	protected static function normalize_options($options)
	{
		return $options && is_array($options) ? $options : array();
	}
}
