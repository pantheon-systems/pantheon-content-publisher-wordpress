<?php

//phpcs:disable Files.SideEffects.FoundWithSymbols

/**
 * Plugin Name: Pantheon Content Publisher
 * Description: Publish WordPress content from Google Docs with Pantheon Content Cloud.
 * Plugin URI: https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/
 * Author: Pantheon
 * Author URI: https://pantheon.io
 * Version: 1.2.6
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Pantheon\ContentPublisher;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

define('CONTENT_PUB_PLUGIN_FILE', __FILE__);
define('CONTENT_PUB_PLUGIN_DIR', plugin_dir_path(CONTENT_PUB_PLUGIN_FILE));
define('CONTENT_PUB_BASENAME', plugin_basename(CONTENT_PUB_PLUGIN_FILE));
define('CONTENT_PUB_PLUGIN_DIR_URL', plugin_dir_url(CONTENT_PUB_PLUGIN_FILE));
define('CONTENT_PUB_ACCESS_TOKEN_OPTION_KEY', 'content_pub_access_token');
define('CONTENT_PUB_PREVIEW_SECRET_OPTION_KEY', 'content_pub_preview_secret');
define('CONTENT_PUB_SITE_ID_OPTION_KEY', 'content_pub_site_id');
define('CONTENT_PUB_ENCODED_SITE_URL_OPTION_KEY', 'content_pub_encoded_site_url');
define('CONTENT_PUB_API_KEY_OPTION_KEY', 'content_pub_api_key');
define('CONTENT_PUB_INTEGRATION_POST_TYPE_OPTION_KEY', 'content_pub_integration_post_type');
define('CONTENT_PUB_API_NAMESPACE', 'pcc/v1');
define('CONTENT_PUB_CONTENT_META_KEY', 'content_pub_id');
define('CONTENT_PUB_ENDPOINT', 'https://addonapi-gfttxsojwq-uc.a.run.app');
define('CONTENT_PUB_WEBHOOK_SECRET_OPTION_KEY', 'content_pub_webhook_secret');

call_user_func(static function ($rootPath) {
	$autoload = "{$rootPath}vendor/autoload.php";
	if (is_readable($autoload)) {
		require_once $autoload;
	}
	add_action('plugins_loaded', [Plugin::class, 'getInstance'], -10);
}, CONTENT_PUB_PLUGIN_DIR);

/**
 * Content Publisher activation hook.
 *
 * Generates a temporary preview secret for WordPress that is used for
 * content previews from content.pantheon.io document previews.
 *
 * @return void
 */
function _pcc_on_activate(): void
{
	if (! get_option(CONTENT_PUB_PREVIEW_SECRET_OPTION_KEY)) {
		// 64 chars, mixed, not autoloaded to avoid polluting requests.
		add_option(CONTENT_PUB_PREVIEW_SECRET_OPTION_KEY, wp_generate_password(64, true, true));
	}
}

/**
 * Content Publisher deactivation hook.
 *
 * Cleans up the preview secret stored in the database.
 *
 * @return void
 */
function _pcc_on_deactivate(): void
{
	delete_option(CONTENT_PUB_PREVIEW_SECRET_OPTION_KEY);
}

register_activation_hook(__FILE__, __NAMESPACE__ . '\\_pcc_on_activate');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\\_pcc_on_deactivate');
