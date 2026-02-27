<?php

namespace Pantheon\ContentPublisher;

if (!defined('ABSPATH')) {
	exit;
}

use function add_action;
use function add_menu_page;
use function file_exists;
use function file_get_contents;
use function wp_add_inline_script;
use function wp_enqueue_script_module;
use function wp_enqueue_style;
use function wp_json_encode;
use function wp_register_style;
use function menu_page_url;

class Admin
{
	private string $menuSlug = 'pantheon-content-publisher';
	private string $title = 'Pantheon Content Publisher';
	private const CPUB_ICON_BASE64 = 'PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxwYXRoIGQ9Ik00LjcxNjkxIDFMNi4xNTA3MSA0LjQ1NDE4SDQuMzI1ODdMNC45MTI0MiA1Ljk1MzE2SDguNjI3MjlMNC43MTY5MSAxWiIgZmlsbD0id2hpdGUiLz4KICAgIDxwYXRoIGQ9Ik05LjU3MjI5IDEzLjU0NThMOC45NTMxNCAxMi4wNDY5SDguMTA1ODlMNi4zNDYyMiA3Ljc3ODAySDUuNTk2NzNMNy4zNTY0IDEyLjA0NjlINS4yMDU2OUw5LjE4MTI1IDE3TDcuNzQ3NDQgMTMuNTQ1OEg5LjU3MjI5WiIKICAgICAgICAgIGZpbGw9IndoaXRlIi8+CiAgICA8cGF0aCBkPSJNMTAuMDYxMSAxMC41MTUzSDcuNzQ3NDRMOC4yMzYyNCAxMS42ODg0SDEwLjA2MTFDMTAuMDkzNyAxMS42ODg0IDEwLjIyNCAxMS42MjMyIDEwLjIyNCAxMS4xMDE4QzEwLjE5MTQgMTAuNTgwNCAxMC4wOTM3IDEwLjUxNTMgMTAuMDYxMSAxMC41MTUzWiIKICAgICAgICAgIGZpbGw9IndoaXRlIi8+CiAgICA8cGF0aCBkPSJNMTAuMjg5MiA5LjExNDA0SDcuMTkzNDhMNy42ODIyOCAxMC4yODcySDEwLjI4OTJDMTAuMzIxOCAxMC4yODcyIDEwLjQ1MjEgMTAuMjIyIDEwLjQ1MjEgOS43MDA2QzEwLjQxOTYgOS4xNzkyMiAxMC4zMjE4IDkuMTE0MDQgMTAuMjg5MiA5LjExNDA0WiIKICAgICAgICAgIGZpbGw9IndoaXRlIi8+CiAgICA8cGF0aCBkPSJNMTAuMDYxMSA3LjQ4NDczQzEwLjA5MzcgNy40ODQ3MyAxMC4yMjQgNy40MTk1NiAxMC4yMjQgNi44OTgxN0MxMC4yMjQgNi4zNzY3OSAxMC4xMjYzIDYuMzExNjEgMTAuMDYxMSA2LjMxMTYxSDcuNTE5MzVMOC4wMDgxNSA3LjQ4NDczSDEwLjA2MTFaIgogICAgICAgICAgZmlsbD0id2hpdGUiLz4KICAgIDxwYXRoIGQ9Ik04LjU2MjEgOC44ODU5NUgxMC4yNTY2QzEwLjI4OTIgOC44ODU5NSAxMC40MTk1IDguODIwNzcgMTAuNDE5NSA4LjI5OTM5QzEwLjQxOTUgNy43NzggMTAuMzIxOCA3LjcxMjgzIDEwLjI1NjYgNy43MTI4M0g4LjA3MzNMOC41NjIxIDguODg1OTVaIgogICAgICAgICAgZmlsbD0id2hpdGUiLz4KICAgIDxwYXRoIGQ9Ik01Ljc1OTY3IDguODg1OTVMNS4yMDU3IDcuNDg0NzNINi40NzY1OEw3LjA2MzE0IDguODg1OTVIOC4yNjg4NEw3LjE5MzQ4IDYuMzExNjFINC41NTM5N0M0LjM1ODQ1IDYuMzExNjEgNC4yMjgxMSA2LjMxMTYxIDQuMTMwMzUgNi42MDQ4OUM0LjAzMjU5IDYuOTYzMzUgNCA3LjY0NzY2IDQgOC45ODM3MUM0IDEwLjMxOTggNCAxMS4wMDQxIDQuMTMwMzUgMTEuMzYyNUM0LjIyODExIDExLjY1NTggNC4zMjU4NyAxMS42NTU4IDQuNTUzOTcgMTEuNjU1OEg2Ljg2NzYyTDUuNzU5NjcgOC44ODU5NVoiCiAgICAgICAgICBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4=';

	public function __construct()
	{
		add_action('admin_menu', [$this, 'registerMenu']);
		add_action('admin_enqueue_scripts', [$this, 'maybeEnqueueAssets']);
	}

	public function registerMenu(): void
	{
		add_menu_page(
			$this->title,
			$this->title,
			'manage_options',
			$this->menuSlug,
			[$this, 'renderPage'],
			'data:image/svg+xml;base64,' . self::CPUB_ICON_BASE64,
			20
		);
	}

	public function renderPage(): void
	{
		echo '<div id="content-pub-root" style="background-color:white;"></div>';
	}

	public function maybeEnqueueAssets(): void
	{
		if (!$this->isOnPluginAdminPage()) {
			return;
		}

		$handle = 'pcc-admin-app';

		$this->addAdminPageStyles();

		if ($this->isDevServerEnabled()) {
			$this->enqueueDevAssets($handle);
			return;
		}

		$this->enqueueProdAssets($handle);
	}

	private function isOnPluginAdminPage(): bool
	{
		$pageParam = filter_input(INPUT_GET, 'page', FILTER_UNSAFE_RAW);
		if (!is_string($pageParam)) {
			return false;
		}

		return $pageParam === $this->menuSlug;
	}

	private function isDevServerEnabled(): bool
	{
		return (defined('WP_DEBUG') && WP_DEBUG && (bool) getenv('VITE_DEV_SERVER'));
	}

	private function enqueueDevAssets(string $handle): void
	{
		$protocol = is_ssl() ? 'https' : 'http';
		$clientHandle = $handle . '-client';
		$preambleHandle = $handle . '-react-refresh-preamble';
		wp_enqueue_script_module($clientHandle, "$protocol://localhost:5173/@vite/client", [], null, true);
		wp_enqueue_script_module($preambleHandle, "$protocol://localhost:5173/src/scripts/react-refresh-preamble.js", [$clientHandle], null, true);
		wp_enqueue_script_module($handle, "$protocol://localhost:5173/src/admin/main.tsx", [$clientHandle, $preambleHandle], null, true);

		$this->addBootstrap();
	}

	private function enqueueProdAssets(string $handle): void
	{
		$manifestPath = CPUB_PLUGIN_DIR . 'assets/dist/build/.vite/manifest.json';
		if (!file_exists($manifestPath)) {
			error_log('Manifest file not found');
			return;
		}
		$manifest = json_decode((string) file_get_contents($manifestPath), true);
		$entry = reset($manifest) ?? null;
		if (!$entry) {
			error_log('Entry not found');
			return;
		}

		$jsFile = $entry['file'] ?? null;
		$cssFiles = $entry['css'] ?? [];
		if ($jsFile) {
			wp_enqueue_script_module($handle, CPUB_PLUGIN_DIR_URL . 'assets/dist/build/' . $jsFile, [], null, ['in_footer' => true]);
			$this->addBootstrap();
		}
		foreach ($cssFiles as $css) {
			wp_enqueue_style($handle, CPUB_PLUGIN_DIR_URL . 'assets/dist/build/' . $css, [], null);
		}
	}

	private function addAdminPageStyles(): void
	{
		$styleHandle = 'pcc-admin-page-styles';
		wp_register_style($styleHandle, '', [], null);
		wp_enqueue_style($styleHandle);

		$slug = $this->menuSlug;
		$css = 'body.toplevel_page_' . $slug . ' #wpwrap, '
			. 'body.toplevel_page_' . $slug . ' #wpcontent, '
			. 'body.toplevel_page_' . $slug . ' #wpbody, '
			. 'body.toplevel_page_' . $slug . ' #wpbody-content { background: #fff; } '
			. 'body.toplevel_page_' . $slug . ' #wpbody-content { padding-top: 0; } '
			. 'body.toplevel_page_' . $slug . ' #wpbody-content .wrap { margin: 0; padding: 0; } '
			. 'body.toplevel_page_' . $slug . ' #wpcontent { padding-left: 0; }';

		wp_add_inline_style($styleHandle, $css);
	}

	private function addBootstrap(): void
	{
		$pccSyncManager = new PccSyncManager();
		$isPCCConfigured = $pccSyncManager->isPCCConfigured();

		$bootstrap = 'window.CPUB_BOOTSTRAP = ' . wp_json_encode([
			'rest_url' => get_rest_url(get_current_blog_id(), CPUB_API_NAMESPACE),
			'nonce' => wp_create_nonce('wp_rest'),
			'site_url' => site_url(),
			'assets_url' => CPUB_PLUGIN_DIR_URL . 'assets',
			'plugin_main_page' => menu_page_url($this->menuSlug, false),
			'is_pcc_configured' => $isPCCConfigured,
			'acf_active' => (new AcfFieldMapper())->isAcfActive(),
			'configured' => [
				'collection_url' => site_url(),
				'collection_id' => get_option(CPUB_SITE_ID_OPTION_KEY),
				'publish_as' => get_option(CPUB_INTEGRATION_POST_TYPE_OPTION_KEY, 'post'),
				'webhook' => [
					'url' => rest_url(CPUB_API_NAMESPACE . '/webhook'),
					'notice_dismissed' => (bool) get_option(CPUB_WEBHOOK_NOTICE_DISMISSED_OPTION_KEY, false),
				],
			],
		]) . ';';

		// If PCC is configured, we can enrich the bootstrap data with the collection data
		if ($isPCCConfigured) {
			$site = $pccSyncManager->getSiteData();
			if ($site) {
				$bootstrap .= 'window.CPUB_BOOTSTRAP.configured.collection_data = ' . wp_json_encode($site) . ';';
			}
		}

		wp_register_script('pcc-admin-bootstrap', '', [], null, false);
		wp_enqueue_script('pcc-admin-bootstrap');
		wp_add_inline_script('pcc-admin-bootstrap', $bootstrap, 'before');
	}
}
