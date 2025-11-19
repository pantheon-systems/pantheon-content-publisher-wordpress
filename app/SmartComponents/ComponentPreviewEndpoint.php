<?php

namespace Pantheon\ContentPublisher\SmartComponents;

use Pantheon\ContentPublisher\PccSyncManager;

/**
 * REST API endpoint for smart component and document previews
 */
class ComponentPreviewEndpoint
{
	public function __construct()
	{
		add_action('rest_api_init', [$this, 'registerRoutes']);
		add_action('template_redirect', [$this, 'handleRedirect']);
	}

	/**
	 * Handle redirect from /api/pantheoncloud/component/* to REST endpoint
	 *
	 * @return void
	 */
	public function handleRedirect(): void
	{
		global $wp;

		// Check if request matches component preview pattern
		if (preg_match('#^api/pantheoncloud/component/([a-zA-Z0-9_-]+)#', $wp->request, $matches)) {
			$component_id = $matches[1];
			$query_string = $_SERVER['QUERY_STRING'] ?? '';
			$url = rest_url(CPUB_API_NAMESPACE . '/api/pantheoncloud/component/' . $component_id);
			if ($query_string) {
				$url .= '?' . $query_string;
			}
			wp_redirect($url);
			exit;
		}

		// Check if request matches document preview pattern
		if (preg_match('#^api/pantheoncloud/document/([a-zA-Z0-9_-]+)#', $wp->request, $matches)) {
			$document_id = $matches[1];
			$query_string = $_SERVER['QUERY_STRING'] ?? '';
			$url = rest_url(CPUB_API_NAMESPACE . '/api/pantheoncloud/document/' . $document_id);
			if ($query_string) {
				$url .= '?' . $query_string;
			}
			wp_redirect($url);
			exit;
		}
	}

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function registerRoutes(): void
	{
		// Component preview endpoint
		register_rest_route(CPUB_API_NAMESPACE, '/api/pantheoncloud/component/(?P<component_id>[a-zA-Z0-9_-]+)', [
			'methods' => 'GET',
			'callback' => [$this, 'handleComponentPreview'],
			'permission_callback' => '__return_true',
			'args' => [
				'component_id' => [
					'required' => true,
					'type' => 'string',
					'description' => 'Component ID (e.g., core_quote)',
				],
				'attrs' => [
					'required' => false,
					'type' => 'string',
					'description' => 'Base64-encoded JSON attributes',
				],
				'snippet' => [
					'required' => false,
					'type' => 'boolean',
					'description' => 'Return snippet only (for document preview)',
				],
			],
		]);

		// Document preview endpoint
		register_rest_route(CPUB_API_NAMESPACE, '/api/pantheoncloud/document/(?P<document_id>[a-zA-Z0-9_-]+)', [
			'methods' => 'GET',
			'callback' => [$this, 'handleDocumentPreview'],
			'permission_callback' => '__return_true',
			'args' => [
				'document_id' => [
					'required' => true,
					'type' => 'string',
					'description' => 'Document ID from PCC',
				],
			],
		]);
	}

	/**
	 * Handle component preview request
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handleComponentPreview(\WP_REST_Request $request)
	{
		$component_id = $request->get_param('component_id');
		$attrs_encoded = $request->get_param('attrs');
		$is_snippet = $request->get_param('snippet');

		// Decode attributes
		$attrs = [];
		if ($attrs_encoded) {
			$attrs_decoded = base64_decode($attrs_encoded, true);
			if ($attrs_decoded) {
				$attrs = json_decode($attrs_decoded, true) ?: [];
			}
		}

		// Convert component ID to block type (core_embed -> core/embed)
		$component_id = strtolower($component_id);
		$block_type = str_replace('_', '/', $component_id);

		// Convert boolean strings to actual booleans and numbers
		foreach ($attrs as $key => $value) {
			if ($value === 'true') {
				$attrs[$key] = true;
			} elseif ($value === 'false') {
				$attrs[$key] = false;
			} elseif (is_numeric($value)) {
				$attrs[$key] = (float) $value;
			}
		}

		$rendered = ComponentConverter::renderBlockToHtml($block_type, $attrs);

		// Return snippet or full page
		if ($is_snippet) {
			// For document preview pane - return raw HTML (not JSON-encoded)
			header('Access-Control-Allow-Origin: *');
			header('Content-Type: text/html; charset=UTF-8');
			echo $rendered;
			exit;
		} else {
			// For component edit popup - return full HTML page
			// Send CORS headers before any output
			header('Access-Control-Allow-Origin: *');
			header('Content-Type: text/html; charset=UTF-8');

			// Create a global variable for the template to access
			global $cpub_preview_content;
			$cpub_preview_content = $rendered;

			// Render the template directly (bypass REST response)
			include CPUB_PLUGIN_DIR . '/templates/component-preview.php';
			exit;
		}
	}

	/**
	 * Handle document preview request
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handleDocumentPreview(\WP_REST_Request $request)
	{
		$document_id = $request->get_param('document_id');

		$pccManager = new PccSyncManager();

		try {
			if (!$pccManager->isPCCConfigured()) {
				return new \WP_REST_Response('PCC not configured', 500);
			}

			$pccClient = $pccManager->pccClient();
			$publishingLevel = \PccPhpSdk\api\Query\Enums\PublishingLevel::REALTIME;

			$article = DocumentFetcher::fetchDocument($pccClient, $document_id, $publishingLevel);

			if (!$article) {
				return new \WP_REST_Response('Document not found', 404);
			}

			$rendered = apply_filters('the_content', $article->content);

			header('Access-Control-Allow-Origin: *');
			header('Content-Type: text/html; charset=UTF-8');

			global $cpub_preview_content;
			$cpub_preview_content = $rendered;

			include CPUB_PLUGIN_DIR . '/templates/component-preview.php';
			exit;
		} catch (\Throwable $e) {
			error_log('Document preview error: ' . $e->getMessage());
			return new \WP_REST_Response('Error loading document preview', 500);
		}
	}
}
