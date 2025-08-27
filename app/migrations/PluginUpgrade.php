<?php

namespace Pantheon\ContentPublisher\Migrations;

class PluginUpgrade
{

	/**
	 * Check if upgrade are needed
	 */
	public static function isUpgradeNeeded()
	{
		// is there a version saved in db if not set it to 0.0
		$installer_version = get_option('CONTENT_PUB_VERSION', '0.0');

		// Run if new version is higher than the current
		if (version_compare($installer_version, CONTENT_PUB_VERSION, '<')) {
			// Run if version is 1.3 (pcc metapost in db upgrade)
			// Granted the wp-submission release will be 1.3
			if (version_compare($installer_version, '1.3', '<') &&
				version_compare(CONTENT_PUB_VERSION, '1.3', '>=')) 
			{
				self::upgradeTo13();
			}
			// Update stored version to prevent rerunning
			update_option('CONTENT_PUB_VERSION', CONTENT_PUB_VERSION);
		}
	}

	// Granted the wp-submission release will be 1.3
	private static function upgradeTo13()
	{

		global $wpdb;
		$old_metakey = 'pcc_id';
		$new_metakey = 'content_pub_id';
		$new_option = get_option('pcc_migration');

		if ($new_option === 'no_migration_needed') {
			return;
		}

		// Get post_ids
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.post_id
				FROM {$wpdb->postmeta} AS pm
				INNER JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id
				WHERE p.post_type IN ('post', 'page')
				AND pm.meta_key = %s",
				$old_metakey
			)
		);

		// if none return with message
		if (empty($post_ids)) {
			update_option('pcc_migration', 'no_migration_needed');
		}

		// Update the old metakey for the new one
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} AS pm
				INNER JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id
				SET pm.meta_key = %s
				WHERE p.post_type IN ('post', 'page')
				AND pm.meta_key = %s",
				$new_metakey,
				$old_metakey
			)
		);

		// if nothing gets updated
		if (!$updated) {
			update_option('pcc_migration', 'migration_needed');
			return;
		}

		if ($updated) {
			// Clear cache
			foreach ($post_ids as $pid) {
				clean_post_cache((int) $pid);
			}
			update_option('pcc_migration', 'no_migration_needed');
		}
	}
}
