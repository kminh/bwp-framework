<?php

/**
 * Copyright (c) 2016 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
final class BWP_Framework_Migration_Factory
{
	private function __construct() {}

	/**
	 * Create a list of migrations to execute for a particular plugin.
	 *
	 * @param BWP_Framework_V3 $plugin
	 * @return BWP_Framework_Migration[]
	 */
	public static function create_migrations(BWP_Framework_V3 $plugin)
	{
		$finder = new ehough_finder_Finder();
		$migration_directory = dirname($plugin->plugin_file) . '/migrations';

		// no migrations, nothing to do
		if (!file_exists($migration_directory)) {
			return array();
		}

		$iterator = $finder
			->files()
			->name('/^[0-9]{5,}(-(beta|rc)[0-9]+)?(_[0-9]{5,}(-(beta|rc)[0-9]+)?)?\.php$/')
			->depth(0)
			->in($migration_directory);

		$migrations = array();
		/* @var $file SplFileInfo */
		foreach ($iterator as $file) {
			$filepath = $migration_directory . '/' . $file->getFilename();
			if (! $migration = self::create_migration($plugin, $filepath)) {
				continue;
			}

			$migrations[] = $migration;
		}

		usort($migrations, array('BWP_Framework_Migration_Factory', 'compare_migrations'));

		return $migrations;
	}

	/**
	 * Compare migration by target version.
	 *
	 * @param BWP_Framework_Migration $a
	 * @param BWP_Framework_Migration $b
	 * @return int
	 */
	public static function compare_migrations(BWP_Framework_Migration $a, BWP_Framework_Migration $b)
	{
		return version_compare($a->get_version(), $b->get_version());
	}

	/**
	 * Create a new migration class from a migration file for a particular
	 * plugin.
	 *
	 * @param BWP_Framework_V3 $plugin
	 * @param string $filepath
	 *
	 * @return self|null Return null if migration file does not exist or not a
	 *                   valid migration class.
	 */
	private static function create_migration(BWP_Framework_V3 $plugin, $filepath)
	{
		if (!file_exists($filepath)) {
			return null;
		}

		$version_id = pathinfo($filepath, PATHINFO_FILENAME);
		$classname  = get_class($plugin) . '_Migration_' . str_replace('-', '_', $version_id);

		if (!class_exists($classname)) {
			include_once $filepath;
		}

		try {
			$migration = new $classname($plugin);
			if (! $migration instanceof BWP_Framework_Migration) {
				return null;
			}
		} catch (Exception $e) {
			return null;
		}

		return $migration;
	}
}
