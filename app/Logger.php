<?php

namespace Pantheon\ContentPublisher;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols -- WordPress direct access guard.
if (!defined('ABSPATH')) {
	exit;
}
// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Writes plugin messages to the PHP error log, only when WP_DEBUG is on.
 */
class Logger
{
	public static function log(string $message): void
	{
		if (!defined('WP_DEBUG') || !WP_DEBUG) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated on WP_DEBUG.
		error_log($message);
	}
}
