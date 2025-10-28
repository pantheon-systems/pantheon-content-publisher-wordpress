<?php

namespace Pantheon\ContentPublisher\Migrations;

class PluginUpgrade
{

	/**
	 * Check if upgrades are needed
	 */
	public static function isUpgradeNeeded()
	{
		// is there a version saved in db if not set it to 0.0
		$installer_version = get_option('cpub_version', '0.0');

		// Run if new version is higher than the current
		if (version_compare($installer_version, CPUB_VERSION, '<')) {
			// Run version-specific migrations
			self::runMigrations($installer_version);

			// Update stored version to prevent rerunning
			update_option('cpub_version', CPUB_VERSION);
		}
	}

	/**
	 * Run all necessary migrations based on current and target version
	 *
	 * @param string $from_version The version we're upgrading from
	 */
	private static function runMigrations($from_version)
	{
		// Run 1.3.1 migration if needed
		if (version_compare($from_version, '1.3.1', '<') &&
			version_compare(CPUB_VERSION, '1.3.1', '>='))
		{
			Upgrade_131::run();
		}
	}
}
