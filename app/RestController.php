<?php

/*
 * REST controller class exposing endpoints for OAuth2 authorization and credentials saving.
 */

namespace Pantheon\ContentPublisher;

use WP_REST_Request;
use WP_REST_Response;
use PccPhpSdk\api\Query\Enums\PublishingLevel;

use function esc_html__;

use const CPUB_ACCESS_TOKEN_OPTION_KEY;

/**
 * REST controller class.
 */
class RestController
{
	/**
	 * Class constructor, hooking into the REST API initialization.
	 */
	public function __construct()
	{
		add_action('rest_api_init', [$this, 'registerRoutes']);
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function registerRoutes(): void
	{
		$endpoints = [
			[
				'route' => '/oauth/access-token',
				'method' => 'POST',
				'callback' => [$this, 'saveAccessToken'],
			],
			[
				'route' => '/collection',
				'method' => 'POST',
				'callback' => [$this, 'createCollection'],
			],
			[
				'route' => '/site',
				'method' => 'POST',
				'callback' => [$this, 'createOrUpdateSite'],
			],
			[
				'route' => '/api-key',
				'method' => 'POST',
				'callback' => [$this, 'createApiKey'],
			],
			[
				'route' => '/collection',
				'method' => 'PUT',
				'callback' => [$this, 'updateCollection'],
			],
			[
				'route' => '/webhook',
				'method' => 'POST',
				'callback' => [$this, 'handleWebhook'],
			],
			[
				'route' => '/webhook',
				'method' => 'PUT',
				'callback' => [$this, 'registerWebhook'],
			],
			[
				'route' => '/disconnect',
				'method' => 'DELETE',
				'callback' => [$this, 'disconnect'],
			],
			[
				'route' => 'api/pantheoncloud/status',
				'method' => 'GET',
				'callback' => [$this, 'pantheonCloudStatusCheck'],
			],
		];

		foreach ($endpoints as $endpoint) {
			register_rest_route(CPUB_API_NAMESPACE, $endpoint['route'], [
				'methods' => $endpoint['method'],
				'callback' => $endpoint['callback'],
				'permission_callback' => [$this, 'permissionCallback'],
			]);
		}
	}

	/**
	 * Public endpoint for to check website publish status.
	 *
	 * @return WP_REST_Response
	 */
	public function pantheonCloudStatusCheck()
	{
		return new WP_REST_Response((object)[]);
	}

	/**
	 * Handle incoming webhook requests.
	 * @return void|WP_REST_Response
	 */
	public function handleWebhook(WP_REST_Request $request)
	{
		if (get_option(CPUB_WEBHOOK_SECRET_OPTION_KEY) !== $request->get_header('x-pcc-webhook-secret')) {
			return new WP_REST_Response(
				esc_html__('You are not authorized to perform this action', 'pantheon-content-publisher'),
				401
			);
		}

		$event = $request->get_param('event');
		$payload = $request->get_param('payload');
		$isPCCConfiguredCorrectly = (new PccSyncManager())->isPCCConfigured();

		// Bail if current website id is not correctly configured
		if (!$isPCCConfiguredCorrectly) {
			return new WP_REST_Response(
				esc_html__('Website is not correctly configured', 'pantheon-content-publisher'),
				500
			);
		}

		if (!is_array($payload) || !isset($payload['articleId']) || empty($payload['articleId'])) {
			return new WP_REST_Response(esc_html__('Invalid article ID in payload', 'pantheon-content-publisher'), 400);
		}

		$articleId = sanitize_text_field($payload['articleId']);
		$pccManager = new PccSyncManager();
		switch ($event) {
			case 'article.unpublish':
				$pccManager->unPublishPostByDocumentId($articleId);
				break;
			case 'article.publish':
				$pccManager->fetchAndStoreDocument($articleId, PublishingLevel::PRODUCTION);
				break;
			default:
				return new WP_REST_Response(
					esc_html__('Event type is currently unsupported', 'pantheon-content-publisher'),
					200
				);
		}
	}

	/**
	 * @param WP_REST_Request $request
	 * @return true|WP_Error
	 */
	public function permissionCallback(WP_REST_Request $request)
	{
		$cookie_error = rest_cookie_check_errors(null);
		if (!empty( $cookie_error)) {
			return $cookie_error;
		}

		// Nonce check
		$nonce = $request->get_header('X-WP-Nonce');
		if (!$nonce) {
			$nonce = $request->get_param('_wpnonce');
		}

		// Sanitize nonce
		if ($nonce) {
			$nonce = sanitize_text_field(wp_unslash($nonce));
		}

		if ($nonce && ! wp_verify_nonce($nonce, 'wp_rest')) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__('Security check failed', 'pantheon-content-publisher'),
				['status' => 403]
			);
		}

		return true;
	}

	public function createCollection(WP_REST_Request $request): WP_REST_Response
	{
		$siteId = sanitize_text_field($request->get_param('site_id') ?: '');
		if (!$siteId) {
			return new WP_REST_Response([
				'message' => esc_html__('Missing site id', 'pantheon-content-publisher'),
			], 400);
		}

		$postType = sanitize_text_field($request->get_param('post_type') ?: '');
		if (!$postType) {
			return new WP_REST_Response([
				'message' => esc_html__('Missing integration post type', 'pantheon-content-publisher'),
			], 400);
		}

		update_option(CPUB_SITE_ID_OPTION_KEY, $siteId);
		update_option(CPUB_INTEGRATION_POST_TYPE_OPTION_KEY, $postType);

		return new WP_REST_Response(esc_html__('Saved!', 'pantheon-content-publisher'));
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function createOrUpdateSite(WP_REST_Request $request): WP_REST_Response
	{
		// Check if you are authorized
		if (!current_user_can('manage_options')) {
			return new WP_REST_Response(esc_html__('You are not authorized to perform this action.', 'pantheon-content-publisher'), 401);
		}
		// Check management token is set
		if (!get_option(CPUB_ACCESS_TOKEN_OPTION_KEY)) {
			return new WP_REST_Response(esc_html__('Management token is not set yet', 'pantheon-content-publisher'), 401);
		}

		$siteManager = new PccSiteManager();
		$response = $siteManager->getSiteID();
		if (is_wp_error($response)) {
			return new WP_REST_Response($response->get_error_message(), $response->get_error_code());
		}

		// Update with the site id
		update_option(CPUB_SITE_ID_OPTION_KEY, $response);
		update_option(CPUB_ENCODED_SITE_URL_OPTION_KEY, md5(wp_parse_url(site_url())['host']));
		return new WP_REST_Response($response);
	}

	/**
	 * Create API key for the site
	 *
	 * @return WP_REST_Response
	 */
	public function registerWebhook(): WP_REST_Response
	{
		// Check management token is set
		if (!get_option(CPUB_ACCESS_TOKEN_OPTION_KEY)) {
			return new WP_REST_Response(esc_html__('Management token is not set yet', 'pantheon-content-publisher'), 400);
		}

		// Check site id is set
		if (!get_option(CPUB_SITE_ID_OPTION_KEY)) {
			return new WP_REST_Response(esc_html__('Site is not created yet', 'pantheon-content-publisher'), 400);
		}

		// Check if you are authorized
		if (!current_user_can('manage_options')) {
			return new WP_REST_Response(esc_html__('You are not authorized to perform this action.', 'pantheon-content-publisher'), 401);
		}

		$siteManager = new PccSiteManager();
		if ($siteManager->registerWebhook()) {
			return new WP_REST_Response(esc_html__('Webhook registered', 'pantheon-content-publisher'));
		}

		return new WP_REST_Response(esc_html__('Error while register webhook', 'pantheon-content-publisher'), 400);
	}

	/**
	 * Create Api Key
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function createApiKey(): WP_REST_Response
	{
		// Check site id is set
		if (!get_option(CPUB_SITE_ID_OPTION_KEY)) {
			return new WP_REST_Response(esc_html__('Site is not created yet', 'pantheon-content-publisher'), 400);
		}

		// Check if you are authorized
		if (!current_user_can('manage_options')) {
			return new WP_REST_Response(esc_html__('You are not authorized to perform this action.', 'pantheon-content-publisher'), 401);
		}

		$siteManager = new PccSiteManager();
		$apiKey = $siteManager->createSiteApiKey();
		if ($apiKey) {
			update_option(CPUB_API_KEY_OPTION_KEY, $apiKey);
			return new WP_REST_Response(esc_html__('API created', 'pantheon-content-publisher'));
		}

		return new WP_REST_Response(esc_html__('Error while creating API key', 'pantheon-content-publisher'), 400);
	}

	/**
	 * Update collection settings
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function updateCollection(WP_REST_Request $request): WP_REST_Response
	{
		$siteId = sanitize_text_field($request->get_param('site_id') ?: '');
		if ($siteId) {
			update_option(CPUB_SITE_ID_OPTION_KEY, $siteId);
		}

		$postType = sanitize_text_field($request->get_param('post_type') ?: '');
		if ($postType) {
			update_option(CPUB_INTEGRATION_POST_TYPE_OPTION_KEY, $postType);
		}

		return new WP_REST_Response(esc_html__('Saved!', 'pantheon-content-publisher'));
	}

	/**
	 * Save Credentials into database
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function saveAccessToken(WP_REST_Request $request): WP_REST_Response
	{
		if (!current_user_can('manage_options')) {
			return new WP_REST_Response(esc_html__('You are not authorized to perform this action.', 'pantheon-content-publisher'), 401);
		}

		$accessToken = sanitize_text_field($request->get_param('access_token'));

		// Validate input field
		if (empty($accessToken)) {
			return new WP_REST_Response(
				esc_html__('Management token cannot be empty.', 'pantheon-content-publisher'),
				400
			);
		}

		update_option(CPUB_ACCESS_TOKEN_OPTION_KEY, $accessToken);
		return new WP_REST_Response(
			esc_html__('Management token saved.', 'pantheon-content-publisher'),
			200
		);
	}

	/**
	 * Delete saved data from the database.
	 *
	 * @return WP_REST_Response
	 */
	public function disconnect()
	{
		if (!current_user_can('manage_options')) {
			return new WP_REST_Response(esc_html__('You are not authorized to perform this action.', 'pantheon-content-publisher'), 401);
		}

		// Disconnect the site
		$manager = new PccSyncManager();
		$manager->disconnect();

		return new WP_REST_Response(
			esc_html__('Saved Data deleted.', 'pantheon-content-publisher'),
			200
		);
	}
}
