<?php

if (!class_exists('BWP_VERSION')) :

/**
 * Class BWP_VERSION
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_VERSION
{
	/**
	 * Default version constraints
	 */
	public static $php_ver = '5.3.2';
	public static $wp_ver  = '3.0';

	private function __construct() {}

	public static function warn_required_versions($title, $domain, $php_ver = null, $wp_ver = null)
	{
		$php_ver = $php_ver ?: self::$php_ver;
		$wp_ver  = $wp_ver ?: self::$wp_ver;

		echo '<div class="error"><p>' . sprintf(
			__('%s requires WordPress <strong>%s</strong> or higher '
			. 'and PHP <strong>%s</strong> or higher. '
			. 'The plugin will not function until you update your software. '
			. 'Please deactivate this plugin.', $domain),
			$title, $wp_ver, $php_ver)
		. '</p></div>';
	}
}

endif;
