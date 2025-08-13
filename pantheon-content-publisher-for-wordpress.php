<?php

//phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Plugin Name: Pantheon Content Publisher
 * Description: Publish WordPress content from Google Docs with Pantheon Content Publisher.
 * Plugin URI: https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/
 * Author: Pantheon
 * Author URI: https://pantheon.io
 * Version: 1.2.6
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Pantheon\ContentPublisher;

// Exit if accessed directly.
if (!\defined('ABSPATH')) {
	exit;
}

define('PCC_PLUGIN_FILE', __FILE__);
define('PCC_PLUGIN_DIR', plugin_dir_path(PCC_PLUGIN_FILE));
define('PCC_BASENAME', plugin_basename(PCC_PLUGIN_FILE));
define('PCC_PLUGIN_DIR_URL', plugin_dir_url(PCC_PLUGIN_FILE));
define('PCC_ACCESS_TOKEN_OPTION_KEY', 'pcc_access_token');
define('PCC_SITE_ID_OPTION_KEY', 'pcc_site_id');
define('PCC_ENCODED_SITE_URL_OPTION_KEY', 'pcc_encoded_site_url');
define('PCC_API_KEY_OPTION_KEY', 'pcc_api_key');
define('PCC_INTEGRATION_POST_TYPE_OPTION_KEY', 'pcc_integration_post_type');
define('PCC_API_NAMESPACE', 'pcc/v1');
define('PCC_CONTENT_META_KEY', 'pcc_id');
define('PCC_ENDPOINT', 'https://addonapi-gfttxsojwq-uc.a.run.app');
define('PCC_WEBHOOK_SECRET_OPTION_KEY', 'pcc_webhook_secret');

call_user_func(static function ($rootPath) {
	$autoload = "{$rootPath}vendor/autoload.php";
	if (is_readable($autoload)) {
		require_once $autoload;
	}
	add_action('plugins_loaded', [Plugin::class, 'getInstance'], -10);
}, PCC_PLUGIN_DIR);
