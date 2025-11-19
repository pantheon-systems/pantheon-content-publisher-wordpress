<?php

namespace Pantheon\ContentPublisher\SmartComponents;

use WP_REST_Response;

/**
 * REST API endpoint for component schema
 */
class ComponentSchemaEndpoint
{
	private const COMPONENT_SCHEMA_ENDPOINT = 'api/pantheoncloud/component_schema';

	private ComponentSchema $schema;

	public function __construct()
	{
		$this->schema = new ComponentSchema();
		add_action('rest_api_init', [$this, 'registerRoute']);
		add_action('template_redirect', [$this, 'handleRedirect']);
	}

	/**
	 * Register the component schema endpoint
	 *
	 * @return void
	 */
	public function registerRoute(): void
	{
		register_rest_route(CPUB_API_NAMESPACE, '/' . self::COMPONENT_SCHEMA_ENDPOINT, [
			'methods' => 'GET',
			'callback' => [$this, 'getComponentSchema'],
			'permission_callback' => '__return_true', // Public endpoint
		]);
	}

	/**
	 * Handle redirect from /api/pantheoncloud/component_schema to REST endpoint
	 *
	 * @return void
	 */
	public function handleRedirect(): void
	{
		global $wp;
		if (self::COMPONENT_SCHEMA_ENDPOINT === $wp->request) {
			$url = rest_url(CPUB_API_NAMESPACE . '/' . self::COMPONENT_SCHEMA_ENDPOINT);
			wp_redirect($url);
			exit;
		}
	}

	/**
	 * Get component schema response
	 *
	 * @return WP_REST_Response
	 */
	public function getComponentSchema(): WP_REST_Response
	{
		$schema = $this->schema->generateSchema();

		// Set CORS headers for cross-origin access
		$response = new WP_REST_Response($schema, 200);
		$response->header('Access-Control-Allow-Origin', '*');
		$response->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
		$response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');

		return $response;
	}
}
