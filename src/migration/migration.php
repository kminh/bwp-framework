<?php

/**
 * Copyright (c) 2016 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Framework_Migration
{
	/**
	 * The plugin to migrate.
	 *
	 * @var BWP_Framework_V3
	 */
	protected $plugin;

	/**
	 * The target version to migrate to.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * The previous version to migrate from.
	 *
	 * @var string
	 */
	protected $previous_version;

	/**
	 * Construct new migration class.
	 *
	 * @param BWP_Framework_V3 $plugin
	 * @throws DomainException if previous version is higher than target version.
	 */
	public function __construct(BWP_Framework_V3 $plugin)
	{
		$this->plugin = $plugin;

		// determine version numbers from current full filename
		$filename = pathinfo($this->get_migration_filename(), PATHINFO_FILENAME);

		// if there's an underscore, we look for both a previous version and a
		// target version
		if (strpos($filename, '_') !== false) {
			$versions = explode('_', $filename);
			$this->previous_version = $this->get_version_from_id($versions[0]);
			$this->version = $this->get_version_from_id($versions[1]);

			if (version_compare($this->previous_version, $this->version, '>=')) {
				throw new DomainException('previous version must not be higher than target version');
			}
		} else {
			$this->version = $this->get_version_from_id($filename);
		}
	}

	/**
	 * Migrate to the target version.
	 */
	abstract public function up();

	/**
	 * Get the target version.
	 *
	 * @return string
	 */
	public function get_version()
	{
		return $this->version;
	}

	/**
	 * Get the previous version.
	 *
	 * @return string
	 */
	public function get_previous_version()
	{
		return $this->previous_version;
	}

	/**
	 * Get migration filename.
	 *
	 * @return string
	 */
	protected function get_migration_filename()
	{
		$migration_class = new ReflectionClass($this);
		return $migration_class->getFileName();
	}

	/**
	 * Check if a plugin option exists.
	 *
	 * @param string $name The option's name.
	 * @return bool
	 */
	protected function has_option($name)
	{
		return isset($this->plugin->options[$name]);
	}

	/**
	 * Get plugin option.
	 *
	 * @param string $name The option's name.
	 * @return mixed
	 */
	protected function get_option($name)
	{
		return $this->plugin->options[$name];
	}

	/**
	 * Get version from version id.
	 *
	 * A version id is a representation of a version string in integer form,
	 * for example 10400 represents "1.4.0".
	 *
	 * @param string $version_id
	 * @return string
	 *
	 * @throws DomainException if version id is smaller than 10000.
	 */
	private function get_version_from_id($version_id)
	{
		// save and strip beta string if any
		$beta_string = '';
		if (preg_match('/-((beta|rc)[0-9]+)$/', $version_id, $matches)) {
			// this includes "beta1" for example
			$beta_string = $matches[1];
			$version_id = str_replace($beta_string, '', $version_id);
		}

		$version_id = (int) $version_id;

		// a BWP plugin has a minimum major version of 1
		if ($version_id < 10000) {
			throw new DomainException('versions must be higher than or equal 1.x.x');
		}

		$major_vesion  = (int) floor($version_id / 10000);
		$minor_version = (int) floor(($version_id % 10000) / 100);
		$patch_version = (int) $version_id % 10000 % 100;

		$version = implode('.', array($major_vesion, $minor_version, $patch_version));

		return $beta_string ? $version . '-' . $beta_string : $version;
	}
}
