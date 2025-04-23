<?php

/**
 * Configure Admin Dashboard Settings UI, logic and assets.
 *
 */

namespace Pantheon\ContentPublisher;

use Exception;
use PccPhpSdk\api\Query\Enums\PublishingLevel;

use function add_action;
use function filemtime;
use function get_post_meta;
use function wp_enqueue_script;
use function wp_strip_all_tags;

use const PCC_CONTENT_META_KEY;
use const PCC_INTEGRATION_POST_TYPE_OPTION_KEY;
use const PCC_PLUGIN_DIR;
use const PCC_PLUGIN_DIR_URL;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Class Settings
 *
 */
class Settings
{
	/**
	 * Pantheon menu icon in base64
	 */
	// phpcs:ignore Generic.Files.LineLength.TooLong
	private const PCC_ICON_BASE64 = 'PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxwYXRoIGQ9Ik00LjcxNjkxIDFMNi4xNTA3MSA0LjQ1NDE4SDQuMzI1ODdMNC45MTI0MiA1Ljk1MzE2SDguNjI3MjlMNC43MTY5MSAxWiIgZmlsbD0id2hpdGUiLz4KICAgIDxwYXRoIGQ9Ik05LjU3MjI5IDEzLjU0NThMOC45NTMxNCAxMi4wNDY5SDguMTA1ODlMNi4zNDYyMiA3Ljc3ODAySDUuNTk2NzNMNy4zNTY0IDEyLjA0NjlINS4yMDU2OUw5LjE4MTI1IDE3TDcuNzQ3NDQgMTMuNTQ1OEg5LjU3MjI5WiIKICAgICAgICAgIGZpbGw9IndoaXRlIi8+CiAgICA8cGF0aCBkPSJNMTAuMDYxMSAxMC41MTUzSDcuNzQ3NDRMOC4yMzYyNCAxMS42ODg0SDEwLjA2MTFDMTAuMDkzNyAxMS42ODg0IDEwLjIyNCAxMS42MjMyIDEwLjIyNCAxMS4xMDE4QzEwLjE5MTQgMTAuNTgwNCAxMC4wOTM3IDEwLjUxNTMgMTAuMDYxMSAxMC41MTUzWiIKICAgICAgICAgIGZpbGw9IndoaXRlIi8+CiAgICA8cGF0aCBkPSJNMTAuMjg5MiA5LjExNDA0SDcuMTkzNDhMNy42ODIyOCAxMC4yODcySDEwLjI4OTJDMTAuMzIxOCAxMC4yODcyIDEwLjQ1MjEgMTAuMjIyIDEwLjQ1MjEgOS43MDA2QzEwLjQxOTYgOS4xNzkyMiAxMC4zMjE4IDkuMTE0MDQgMTAuMjg5MiA5LjExNDA0WiIKICAgICAgICAgIGZpbGw9IndoaXRlIi8+CiAgICA8cGF0aCBkPSJNMTAuMDYxMSA3LjQ4NDczQzEwLjA5MzcgNy40ODQ3MyAxMC4yMjQgNy40MTk1NiAxMC4yMjQgNi44OTgxN0MxMC4yMjQgNi4zNzY3OSAxMC4xMjYzIDYuMzExNjEgMTAuMDYxMSA2LjMxMTYxSDcuNTE5MzVMOC4wMDgxNSA3LjQ4NDczSDEwLjA2MTFaIgogICAgICAgICAgZmlsbD0id2hpdGUiLz4KICAgIDxwYXRoIGQ9Ik04LjU2MjEgOC44ODU5NUgxMC4yNTY2QzEwLjI4OTIgOC44ODU5NSAxMC40MTk1IDguODIwNzcgMTAuNDE5NSA4LjI5OTM5QzEwLjQxOTUgNy43NzggMTAuMzIxOCA3LjcxMjgzIDEwLjI1NjYgNy43MTI4M0g4LjA3MzNMOC41NjIxIDguODg1OTVaIgogICAgICAgICAgZmlsbD0id2hpdGUiLz4KICAgIDxwYXRoIGQ9Ik01Ljc1OTY3IDguODg1OTVMNS4yMDU3IDcuNDg0NzNINi40NzY1OEw3LjA2MzE0IDguODg1OTVIOC4yNjg4NEw3LjE5MzQ4IDYuMzExNjFINC41NTM5N0M0LjM1ODQ1IDYuMzExNjEgNC4yMjgxMSA2LjMxMTYxIDQuMTMwMzUgNi42MDQ4OUM0LjAzMjU5IDYuOTYzMzUgNCA3LjY0NzY2IDQgOC45ODM3MUM0IDEwLjMxOTggNCAxMS4wMDQxIDQuMTMwMzUgMTEuMzYyNUM0LjIyODExIDExLjY1NTggNC4zMjU4NyAxMS42NTU4IDQuNTUzOTcgMTEuNjU1OEg2Ljg2NzYyTDUuNzU5NjcgOC44ODU5NVoiCiAgICAgICAgICBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4=';

	/**
	 * Pantheon Cloud Status endpoint required by PCC
	 */
	private const PCC_STATUS_ENDPOINT = 'api/pantheoncloud/status';

	/**
	 * Publish document endpoint required by PCC
	 */
	private const PCC_PUBLISH_DOCUMENT_ENDPOINT = 'api/pantheoncloud/document/';

	/**
	 * Google Docs edit URL.
	 */
	private const PCC_DOCUMENT_EDIT_URL = 'https://docs.google.com/document/d/%s/edit';

	private $pages = [
		'connected-collection' => PCC_PLUGIN_DIR . 'admin/templates/partials/connected-collection.php',
		'create-collection' => PCC_PLUGIN_DIR . 'admin/templates/partials/create-collection.php',
		'disconnect-confirmation' => PCC_PLUGIN_DIR . 'admin/templates/partials/disconnect-confirmation.php',
		'setup' => PCC_PLUGIN_DIR . 'admin/templates/partials/setup.php',
	];

	public function __construct()
	{
		$this->addHooks();
	}

	/**
	 * Add required hooks.
	 *
	 * @return void
	 */
	private function addHooks(): void
	{
		add_action('template_redirect', [$this, 'registerPantheonCloudStatusEndpoint']);
		add_action('template_redirect', [$this, 'publishDocuments']);
		add_action('template_redirect', [$this, 'setPreviewHeaders']);
		add_action('admin_menu', [$this, 'addMenu']);
		add_action(
			'admin_enqueue_scripts',
			[$this, 'enqueueAdminAssets']
		);
		add_action(
			'wp_enqueue_scripts',
			[$this, 'enqueueFrontAssets']
		);
		add_action('admin_menu', [$this, 'pluginAdminNotice']);
		add_filter('post_row_actions', [$this, 'addRowActions'], 10, 2);
		add_filter('page_row_actions', [$this, 'addRowActions'], 10, 2);
		add_filter('wp_list_table_class_name', [$this, 'overrideAdminWPPostsTable']);
		add_filter('the_content', [$this, 'addPreviewContainer']);
		add_filter('admin_init', [$this, 'verifyCollectionUrl']);
		add_filter('wp_kses_allowed_html', [$this, 'allowStyleTags'], PHP_INT_MAX);
		add_filter('get_the_excerpt', [$this, 'stripExcerptTags'], -PHP_INT_MAX);
	}

	/**
	 * Strip excerpt tags.
	 *
	 * @param string $content
	 * @return string
	 */
	public function stripExcerptTags(string $content): string
	{
		if (get_post_meta(get_the_ID(), PCC_CONTENT_META_KEY, true)) {
			return wp_strip_all_tags($content);
		}

		return $content;
	}

	/**
	 * Verify collection URL.
	 *
	 * @return true
	 */
	public function verifyCollectionUrl()
	{
		$accessToken = $this->getAccessToken();
		$siteId = $this->getSiteId();
		$encodedSiteURL = get_option(PCC_ENCODED_SITE_URL_OPTION_KEY);
		$apiKey = $this->getAPIAccessKey();

		if (!$accessToken || !$siteId || !$apiKey || !$encodedSiteURL) {
			return;
		}

		$currentHashedSiteURL = md5(wp_parse_url(site_url())['host']);
		// if both are not equal then disconnect
		if ($encodedSiteURL !== $currentHashedSiteURL) {
			(new PccSyncManager())->disconnect();
		}
	}

	/**
	 * Get access token from the database.
	 *
	 * @return array|mixed
	 */
	private function getAccessToken(): mixed
	{
		$pccToken = get_option(PCC_ACCESS_TOKEN_OPTION_KEY);

		return $pccToken ?: [];
	}

	/**
	 * @return false|mixed|null
	 */
	private function getSiteId(): mixed
	{
		return get_option(PCC_SITE_ID_OPTION_KEY);
	}

	/**
	 * Get access token from the database.
	 *
	 * @return array|mixed
	 */
	private function getAPIAccessKey(): mixed
	{
		$apiKey = get_option(PCC_API_KEY_OPTION_KEY);

		return $apiKey ?: [];
	}

	public function isPreviewRequest(): bool
	{
		return filter_has_var(INPUT_GET, 'preview') &&
			filter_has_var(INPUT_GET, 'document_id') &&
			filter_has_var(INPUT_GET, 'publishing_level') &&
			filter_has_var(INPUT_GET, 'pccGrant') &&
			'google_document' === filter_input(INPUT_GET, 'preview', FILTER_SANITIZE_STRING) &&
			filter_input(INPUT_GET, 'publishing_level', FILTER_SANITIZE_STRING) === PublishingLevel::REALTIME->value;
	}

	/**
	 * Allow style tags in the content.
	 *
	 * @param $allowedTags
	 * @return mixed
	 */
	public function allowStyleTags($allowedTags)
	{
		if (get_post_meta(get_the_ID(), PCC_CONTENT_META_KEY, true)) {
				$allowedTags['style'] = [];
		}

		return $allowedTags;
	}

	/**
	 * Publish documents from Google Docs.
	 *
	 * @return void
	 */
	public function publishDocuments(): void
	{
		global $wp;
		if (!str_starts_with($wp->request, static::PCC_PUBLISH_DOCUMENT_ENDPOINT)) {
			return;
		}

		try {
			$PCCManager = new PccSyncManager();
			// Publish document

			if (
				filter_has_var(INPUT_GET, 'publishingLevel') &&
				PublishingLevel::PRODUCTION->value === filter_input(INPUT_GET, 'publishingLevel', FILTER_SANITIZE_STRING) &&
				$PCCManager->isPCCConfigured()
			) {
				$parts = explode('/', $wp->request);
				$documentId = sanitize_text_field(wp_unslash(end($parts)));
				$pcc = new PccSyncManager();
				$postId = $pcc->fetchAndStoreDocument($documentId, PublishingLevel::PRODUCTION);

				wp_redirect(add_query_arg('nocache', 'true', get_permalink($postId) ?: site_url()));
				exit;
			}

			// Preview document
			if (
				$this->isPreviewRequest() &&
				$PCCManager->isPCCConfigured()
			) {
				$parts = explode('/', $wp->request);
				$documentId = sanitize_text_field(wp_unslash(end($parts)));
				$pccGrant = filter_input(INPUT_GET, 'pccGrant', FILTER_SANITIZE_STRING);
				$pcc = new PccSyncManager();

				if (!$pcc->findExistingConnectedPost($documentId)) {
					$pcc->fetchAndStoreDocument($documentId, PublishingLevel::REALTIME, true);
				}

				// Find a published post to use as a container for the preview
				$query = get_posts([
					'post_type' => get_option(PCC_INTEGRATION_POST_TYPE_OPTION_KEY, 'post'),
					'post_status' => 'publish',
					'posts_per_page' => 1,
					'orderby' => 'date',
					'order' => 'ASC',
					'fields' => 'ids'
				]);

				$url = $pcc->preparePreviewingURL($documentId, $query[0] ?? 0, $pccGrant);

				wp_redirect($url);
				exit;
			}
		} catch (Exception $ex) {
			// No Action needed for safe exit
		}
	}

		/**
	 * Set no-cache and noindex headers for Google Doc preview pages.
	 *
	 * @return void
	 */
	public function setPreviewHeaders(): void
	{
		if ($this->isPreviewRequest()) {
			nocache_headers();
			header("X-Testing: true");
			header('X-Robots-Tag: noindex');
		}
	}

	/**
	 * Register custom endpoint for Pantheon Cloud Status.
	 * This endpoint is used to check if the site is hosted live.
	 * and checked only one time to show your website on PCC google addon
	 */
	public function registerPantheonCloudStatusEndpoint()
	{
		global $wp;
		if (static::PCC_STATUS_ENDPOINT === $wp->request) {
			$url = rest_url(PCC_API_NAMESPACE . '/' . static::PCC_STATUS_ENDPOINT);

			return wp_redirect($url);
		}
	}

	/**
	 * Build the Google Docs edit URL.
	 *
	 * @param string $documentId
	 * @return string
	 */
	private function buildEditDocumentURL(string $documentId): string
	{
		return sprintf(self::PCC_DOCUMENT_EDIT_URL, $documentId);
	}

	/**
	 * Adds a PCC content container if the conditions are met.
	 *
	 * This function checks if the current post preview is for a Google document and
	 * if the document ID and publishing level match the expected values. If the
	 * conditions are met, it returns a div container for PCC content preview.
	 * Otherwise, it returns the original content.
	 *
	 * @param string $content The original post content.
	 * @return string The modified post content with PCC content container if conditions are met.
	 */
	public function addPreviewContainer(string $content): string
	{
		if ($this->isPreviewRequest()) {
			return '<div id="pcc-content-preview"></div>';
		}

		return $content;
	}

	/**
	 * Add PCC actions to quick edit box.
	 *
	 * @param $actions
	 * @param $post
	 * @return array|mixed
	 */
	public function addRowActions($actions, $post): mixed
	{
		$documentId = get_post_meta($post->ID, PCC_CONTENT_META_KEY, true);
		if (!$documentId) {
			return $actions;
		}

		$customActions = array(
			'pcc' => sprintf(
				'<a href="' . $this->buildEditDocumentURL($documentId) . '"
                        class="pcc-sync" data-id="%d" target="_blank">%s</a>',
				$post->ID,
				esc_html__(
					'Edit in Google Docs',
					'pantheon-content-publisher-for-wordpress'
				) . '<svg width="12px" height="12px" viewBox="0 0 24 24" style="display:inline">
                    <g stroke-width="2.1" stroke="#666" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="17 13.5 17 19.5 5 19.5 5 7.5 11 7.5"></polyline>
                    <path d="M14,4.5 L20,4.5 L20,10.5 M20,4.5 L11,13.5"></path></g></svg>'
			)
		);

		$actions = array_merge($customActions, $actions);

		if (isset($actions['inline hide-if-no-js'])) {
			unset($actions['inline hide-if-no-js']);
		}

		return $actions;
	}

	/**
	 * Register settings page.
	 *
	 * @return void
	 */
	public function addMenu(): void
	{
		add_menu_page(
			esc_html__('Pantheon Content Publisher', 'pantheon-content-publisher-for-wordpress'),
			esc_html__('Pantheon Content Publisher', 'pantheon-content-publisher-for-wordpress'),
			'manage_options',
			'pantheon-content-publisher-for-wordpress',
			[$this, 'renderSettingsPage'],
			$this->pccMenuIcon(),
			20
		);
	}

	/**
	 * Build menu icon url.
	 * @return string
	 */
	public function pccMenuIcon(): string
	{
		return 'data:image/svg+xml;base64,' . self::PCC_ICON_BASE64;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function renderSettingsPage(): void
	{

		$view = filter_input(INPUT_GET, 'view', FILTER_SANITIZE_KEY) ?: '';
		if ($view && isset($this->pages[$view])) {
			require $this->pages[$view];

			return;
		}

		// Site id is set and access token is set
		if ($this->getSiteId() && $this->getAccessToken()) {
			require $this->pages['connected-collection'];

			return;
		}
		if ($this->getAccessToken()) {
			require $this->pages['create-collection'];

			return;
		}
		require $this->pages['setup'];
	}

	/**
	 * Enqueue plugin assets on the WP Admin Dashboard.
	 *
	 * @return void
	 */
	public function enqueueAdminAssets(): void
	{
		wp_enqueue_script(
			'pantheon-content-publisher-for-wordpress',
			PCC_PLUGIN_DIR_URL . 'dist/app.js',
			[],
			filemtime(PCC_PLUGIN_DIR . 'dist/app.js'),
			true
		);

		wp_enqueue_style(
			'pantheon-content-publisher-for-wordpress',
			PCC_PLUGIN_DIR_URL . 'dist/app.css',
			[],
			filemtime(PCC_PLUGIN_DIR . 'dist/app.css')
		);

		wp_localize_script(
			'pantheon-content-publisher-for-wordpress',
			'PCCAdmin',
			[
				'rest_url' => get_rest_url(get_current_blog_id(), PCC_API_NAMESPACE),
				'nonce' => wp_create_nonce('wp_rest'),
				'plugin_main_page' => menu_page_url('pantheon-content-publisher-for-wordpress', false),
				'site_url' => site_url(),
			]
		);
	}

	/**
	 * Enqueue plugin assets on the WP front.
	 *
	 * @return void
	 */
	public function enqueueFrontAssets(): void
	{
		if (!(new PccSyncManager())->isPCCConfigured()) {
			return;
		}
		if (!filter_has_var(INPUT_GET, 'document_id')) {
			return;
		}
		$publishingLevel = filter_input(INPUT_GET, 'publishing_level', FILTER_SANITIZE_STRING);
		if (!$publishingLevel || PublishingLevel::REALTIME->value !== $publishingLevel) {
			return;
		}
		$preview = filter_input(INPUT_GET, 'preview', FILTER_SANITIZE_STRING);
		if (!$preview || 'google_document' !== $preview) {
			return;
		}

		wp_enqueue_script(
			'pantheon-content-publisher-for-wordpress',
			PCC_PLUGIN_DIR_URL . 'dist/pcc-front.js',
			[],
			filemtime(PCC_PLUGIN_DIR . 'dist/pcc-front.js'),
			true
		);

		wp_localize_script(
			'pantheon-content-publisher-for-wordpress',
			'PCCFront',
			[
				// phpcs:ignore
				'site_id' => sanitize_text_field(wp_unslash($this->getSiteId())),
			]
		);
	}

	/**
	 * Show notification when authentication details are not set or collection not created
	 */
	public function pluginAdminNotice()
	{
		global $pagenow;
		if ('plugins.php' !== $pagenow) {
			return;
		}

		// Show notification when authentication details are not set or collection not created
		if (!$this->getAccessToken() || !$this->getSiteId()) {
			add_action('admin_notices', [$this, 'pluginNotification']);
		}
	}

	/**
	 * Plugin notification to continue setup
	 */
	public function pluginNotification()
	{
		require PCC_PLUGIN_DIR . 'admin/templates/partials/plugin-notification.php';
	}

	/**
	 * Replace WP_Posts_List_Table with Custom_Posts_List_Table.
	 *
	 * @param string $className The list table class to use.
	 *
	 * @return string The custom list table class.
	 */
	public function overrideAdminWPPostsTable($className)
	{
		if ('WP_Posts_List_Table' === $className) {
			return PccPostsListTable::class;
		}

		return $className;
	}

	/**
	 * @return false|mixed|null
	 */
	private function getEncodedSiteURL(): mixed
	{
		return get_option(PCC_ENCODED_SITE_URL_OPTION_KEY);
	}
}
