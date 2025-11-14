<?php

namespace Pantheon\ContentPublisher\Migrations;

class Upgrade131
{
	/**
	 * Run all migrations for version 1.3.1
	 */
	public static function run()
	{
		self::updatePosts();
		self::updateOptions();
	}

	/**
	 * Migrate post meta from pcc_id to cpub_id
	 */
	private static function updatePosts()
	{
		global $wpdb;
		$old_metakey = 'pcc_id';
		$new_metakey = 'cpub_id';

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

		// If no posts need migration, return early
		if (empty($post_ids)) {
			return;
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

		// Clear cache for updated posts
		if ($updated) {
			foreach ($post_ids as $pid) {
				clean_post_cache((int) $pid);
			}
		}
	}

	/**
	 * Migrate wp_options from pcc_ prefix to cpub_ prefix
	 */
	private static function updateOptions()
	{
		global $wpdb;

		// Migrate wp_options from pcc_ prefix to cpub_ prefix
		$options_to_migrate = [
			'pcc_site_id' => 'cpub_site_id',
			'pcc_encoded_site_url' => 'cpub_encoded_site_url',
			'pcc_api_key' => 'cpub_api_key',
		];

		foreach ($options_to_migrate as $old_option => $new_option) {
			// Update option name only if the old option exists
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->options}
					SET option_name = %s
					WHERE option_name = %s",
					$new_option,
					$old_option
				)
			);
		}
	}
}
