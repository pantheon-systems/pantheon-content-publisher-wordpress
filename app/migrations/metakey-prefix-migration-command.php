<?php

namespace Pantheon\ContentPublisher;

use WP_CLI;

class MetakeyPrefixMigrationCommand
{
	public function __invoke($args, $assoc_args)
	{
		// Ignore $args and $assoc_args
		global $wpdb;

		$old_metakey = 'pcc_id';
		$new_metakey = 'content_pub_id';

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
			WP_CLI::success("No old metakey found, nothing to update.");
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

		// Clear cache
		if ($updated) {
			foreach ($post_ids as $pid) {
				clean_post_cache((int) $pid);
			}
			WP_CLI::success("Old metakeys updated for the new one. Nothing else to do.");
		} 
		else {
			WP_CLI::error("Something went wrong. Please try again.");
		}
	}
}
