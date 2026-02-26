<?php

namespace Pantheon\ContentPublisher;

if (!defined('ABSPATH')) {
	exit;
}

use WP_REST_Request;
use WP_REST_Response;

/**s
 * REST API endpoints for smart component schema and preview.
 *
 * The Google Docs add-on fetches the component schema to know what
 * smart components are available, and uses the preview endpoint to
 * render component output inside the editor sidebar.
 */
class ComponentEndpoints
{
	private const SCHEMA_PATH = 'api/pantheoncloud/component_schema';
	private const COMPONENT_PATH = 'api/pantheoncloud/component';

	/**
	 * @var SmartComponents
	 */
	private SmartComponents $smartComponents;

	public function __construct()
	{
		$this->smartComponents = SmartComponents::getInstance();

		add_action('rest_api_init', [$this, 'registerRoutes']);
		add_action('template_redirect', [$this, 'handleRedirects']);
	}

	/**
	 * Register REST API routes.
	 */
	public function registerRoutes(): void
	{
		register_rest_route(CPUB_API_NAMESPACE, '/' . self::SCHEMA_PATH, [
			'methods' => 'GET',
			'callback' => [$this, 'getComponentSchema'],
			'permission_callback' => '__return_true',
		]);

		register_rest_route(
			CPUB_API_NAMESPACE,
			'/' . self::COMPONENT_PATH . '/(?P<component_id>[a-zA-Z0-9_-]+)',
			[
				'methods' => 'GET',
				'callback' => [$this, 'handleComponentPreview'],
				'permission_callback' => '__return_true',
				'args' => [
					'component_id' => [
						'required' => true,
						'type' => 'string',
					],
					'attrs' => [
						'required' => false,
						'type' => 'string',
						'description' => 'Base64-encoded JSON attributes',
					],
				],
			]
		);
	}

	/**
	 * Redirect pretty URLs to REST API equivalents.
	 */
	public function handleRedirects(): void
	{
		global $wp;

		if (self::SCHEMA_PATH === $wp->request) {
			wp_safe_redirect(rest_url(CPUB_API_NAMESPACE . '/' . self::SCHEMA_PATH));
			exit;
		}

		if (preg_match('#^' . self::COMPONENT_PATH . '/([a-zA-Z0-9_-]+)#', $wp->request, $matches)) {
			$queryString = isset($_SERVER['QUERY_STRING']) ? sanitize_text_field(wp_unslash($_SERVER['QUERY_STRING'])) : '';
			$url = rest_url(CPUB_API_NAMESPACE . '/' . self::COMPONENT_PATH . '/' . $matches[1]);
			if ($queryString) {
				$url .= '?' . $queryString;
			}
			wp_safe_redirect($url);
			exit;
		}
	}

	/**
	 * Return the smart component schema.
	 *
	 * @return WP_REST_Response
	 */
	public function getComponentSchema(): WP_REST_Response
	{
		$response = new WP_REST_Response(
			$this->smartComponents->getSchema(),
			200
		);
		$response->header('Access-Control-Allow-Origin', '*');
		$response->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
		$response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');

		return $response;
	}

	/**
	 * Handle a component preview request.
	 *
	 * @param WP_REST_Request $request
	 */
	public function handleComponentPreview(WP_REST_Request $request): void
	{
		$componentId = strtoupper(sanitize_text_field($request->get_param('component_id')));
		$attrsEncoded = $request->get_param('attrs');

		$attrs = [];
		if ($attrsEncoded) {
			$decoded = base64_decode($attrsEncoded, true);
			if ($decoded !== false) {
				$attrs = json_decode($decoded, true) ?: [];
			}
		}

		$html = $this->smartComponents->renderComponent($componentId, $attrs);

		header('Access-Control-Allow-Origin: *');
		header('Content-Type: text/html; charset=UTF-8');
		?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>body { margin: 0; padding: 16px; }</style>
</head>
<body>
<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-escaped by the component's render method
		echo $html;
?>
</body>
</html>
		<?php
		exit;
	}
}
