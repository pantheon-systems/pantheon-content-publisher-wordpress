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

define('CPUB_PLUGIN_FILE', __FILE__);
define('CPUB_PLUGIN_DIR', plugin_dir_path(CPUB_PLUGIN_FILE));
define('CPUB_BASENAME', plugin_basename(CPUB_PLUGIN_FILE));
define('CPUB_PLUGIN_DIR_URL', plugin_dir_url(CPUB_PLUGIN_FILE));
define('CPUB_ACCESS_TOKEN_OPTION_KEY', 'cpub_access_token');
define('CPUB_PREVIEW_SECRET_OPTION_KEY', 'cpub_preview_secret');
define('CPUB_SITE_ID_OPTION_KEY', 'cpub_site_id');
define('CPUB_ENCODED_SITE_URL_OPTION_KEY', 'cpub_encoded_site_url');
define('CPUB_API_KEY_OPTION_KEY', 'cpub_api_key');
define('CPUB_INTEGRATION_POST_TYPE_OPTION_KEY', 'cpub_integration_post_type');
define('CPUB_API_NAMESPACE', 'pcc/v1');
define('CPUB_CONTENT_META_KEY', 'cpub_id');
define('CPUB_ENDPOINT', 'https://addonapi-gfttxsojwq-uc.a.run.app');
define('CPUB_WEBHOOK_SECRET_OPTION_KEY', 'cpub_webhook_secret');

call_user_func(static function ($rootPath) {
	$autoload = "{$rootPath}vendor/autoload.php";
	if (is_readable($autoload)) {
		require_once $autoload;
	}
	add_action('plugins_loaded', [Plugin::class, 'getInstance'], -10);
}, CPUB_PLUGIN_DIR);
