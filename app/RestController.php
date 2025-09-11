<?php

/*
 * REST controller class exposing endpoints for OAuth2 authorization and credentials saving.
 */

namespace Pantheon\ContentPublisher;

use WP_REST_Request;
use WP_REST_Response;
use PccPhpSdk\api\Query\Enums\PublishingLevel;
use PccPhpSdk\api\SitesApi;
use PccPhpSdk\core\PccClient;
use PccPhpSdk\core\PccClientConfig;

use function esc_html__;

use const PCC_ACCESS_TOKEN_OPTION_KEY;

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
				'route' => '/collection/connect',
				'method' => 'POST',
				'callback' => [$this, 'connectCollection'],
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
			register_rest_route(PCC_API_NAMESPACE, $endpoint['route'], [
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
		if (get_option(PCC_WEBHOOK_SECRET_OPTION_KEY) !== $request->get_header('x-pcc-webhook-secret')) {
			return new WP_REST_Response(
				esc_html__('You are not authorized to perform this action', 'pantheon-content-publisher-for-wordpress'),
				401
			);
		}

		$event = $request->get_param('event');
		$payload = $request->get_param('payload');
		$isPCCConfiguredCorrectly = (new PccSyncManager())->isPCCConfigured();

		// Bail if current website id is not correctly configured
		if (!$isPCCConfiguredCorrectly) {
			return new WP_REST_Response(
				esc_html__('Website is not correctly configured', 'pantheon-content-publisher-for-wordpress'),
				500
			);
		}

		if (!is_array($payload) || !isset($payload['articleId']) || empty($payload['articleId'])) {
			return new WP_REST_Response(
				esc_html__('Invalid article ID in payload', 'pantheon-content-publisher-for-wordpress'),
				400
			);
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
					esc_html__('Event type is currently unsupported', 'pantheon-content-publisher-for-wordpress'),
					200
				);
		}
	}

	/**
	 * @return true
	 */
	public function permissionCallback()
	{
		rest_cookie_check_errors(null);

		return true;
	}

	public function createCollection(WP_REST_Request $request): WP_REST_Response
	{
		$siteId = sanitize_text_field($request->get_param('site_id') ?: '');
		if (!$siteId) {
			return new WP_REST_Response([
				'message' => esc_html__('Missing site id', 'pantheon-content-publisher-for-wordpress'),
			], 400);
		}

		$postType = sanitize_text_field($request->get_param('post_type') ?: '');
		if (!$postType) {
			return new WP_REST_Response([
				'message' => esc_html__('Missing integration post type', 'pantheon-content-publisher-for-wordpress'),
			], 400);
		}

		update_option(PCC_SITE_ID_OPTION_KEY, $siteId);
		update_option(PCC_INTEGRATION_POST_TYPE_OPTION_KEY, $postType);

		return new WP_REST_Response(esc_html__('Saved!', 'pantheon-content-publisher-for-wordpress'));
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function createOrUpdateSite(WP_REST_Request $request): WP_REST_Response
	{
		// Check if you are authorized
		if (!current_user_can('manage_options')) {
			return new WP_REST_Response(
				esc_html__(
					'You are not authorized to perform this action.',
					'pantheon-content-publisher-for-wordpress'
				),
				401
			);
		}
		// Check management token is set
		if (!get_option(PCC_ACCESS_TOKEN_OPTION_KEY)) {
			return new WP_REST_Response(
				esc_html__('Management token is not set yet', 'pantheon-content-publisher-for-wordpress'),
				401
			);
		}

		$siteManager = new PccSiteManager();
		$response = $siteManager->getSiteID();
		if (is_wp_error($response)) {
			return new WP_REST_Response($response->get_error_message(), $response->get_error_code());
		}

		// Update with the site id
		update_option(PCC_SITE_ID_OPTION_KEY, $response);
		update_option(PCC_ENCODED_SITE_URL_OPTION_KEY, md5(wp_parse_url(site_url())['host']));
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
		if (!get_option(PCC_ACCESS_TOKEN_OPTION_KEY)) {
			return new WP_REST_Response(
				esc_html__('Management token is not set yet', 'pantheon-content-publisher-for-wordpress'),
				400
			);
		}

		// Check site id is set
		if (!get_option(PCC_SITE_ID_OPTION_KEY)) {
			return new WP_REST_Response(
				esc_html__('Site is not created yet', 'pantheon-content-publisher-for-wordpress'),
				400
			);
		}

		// Check if you are authorized
		if (!current_user_can('manage_options')) {
			return new WP_REST_Response(
				esc_html__(
					'You are not authorized to perform this action.',
					'pantheon-content-publisher-for-wordpress'
				),
				401
			);
		}

		$siteManager = new PccSiteManager();
		if ($siteManager->registerWebhook()) {
			return new WP_REST_Response(
				esc_html__('Webhook registered', 'pantheon-content-publisher-for-wordpress')
			);
		}

		return new WP_REST_Response(
			esc_html__('Error while register webhook', 'pantheon-content-publisher-for-wordpress'),
			400
		);
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
		if (!get_option(PCC_SITE_ID_OPTION_KEY)) {
			return new WP_REST_Response(
				esc_html__('Site is not created yet', 'pantheon-content-publisher-for-wordpress'),
				400
			);
		}

		// Check if you are authorized
		if (!current_user_can('manage_options')) {
			return new WP_REST_Response(
				esc_html__(
					'You are not authorized to perform this action.',
					'pantheon-content-publisher-for-wordpress'
				),
				401
			);
		}

		$siteManager = new PccSiteManager();
		$apiKey = $siteManager->createSiteApiKey();
		if ($apiKey) {
			update_option(PCC_API_KEY_OPTION_KEY, $apiKey);
			return new WP_REST_Response(esc_html__('API created', 'pantheon-content-publisher-for-wordpress'));
		}

		return new WP_REST_Response(
			esc_html__('Error while creating API key', 'pantheon-content-publisher-for-wordpress'),
			400
		);
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
			update_option(PCC_SITE_ID_OPTION_KEY, $siteId);
		}

		$postType = sanitize_text_field($request->get_param('post_type') ?: '');
		if ($postType) {
			update_option(PCC_INTEGRATION_POST_TYPE_OPTION_KEY, $postType);
		}

		return new WP_REST_Response(esc_html__('Saved!', 'pantheon-content-publisher-for-wordpress'));
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
			return new WP_REST_Response(
				esc_html__(
					'You are not authorized to perform this action.',
					'pantheon-content-publisher-for-wordpress'
				),
				401
			);
		}

		$accessToken = sanitize_text_field($request->get_param('access_token'));

		// Validate input field
		if (empty($accessToken)) {
			return new WP_REST_Response(
				esc_html__('Management token cannot be empty.', 'pantheon-content-publisher-for-wordpress'),
				400
			);
		}

		// Validate token with PCC API
		$siteManager = new PccSiteManager();
		$isValid = $siteManager->validateManagementToken($accessToken);
		if (!$isValid) {
			return new WP_REST_Response(
				esc_html__('Management token is invalid. Visit the Content Publisher dashboard to generate a new token.', 'pantheon-content-publisher-for-wordpress'),
				400
			);
		}

		update_option(PCC_ACCESS_TOKEN_OPTION_KEY, $accessToken);
		return new WP_REST_Response(
			esc_html__('Management token saved.', 'pantheon-content-publisher-for-wordpress'),
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
			return new WP_REST_Response(
				esc_html__(
					'You are not authorized to perform this action.',
					'pantheon-content-publisher-for-wordpress'
				),
				401
			);
		}

		// Disconnect the site
		$manager = new PccSyncManager();
		$manager->disconnect();

		return new WP_REST_Response(
			esc_html__('Saved Data deleted.', 'pantheon-content-publisher-for-wordpress'),
			200
		);
	}

	/**
	 * Connect collection to the current WordPress site
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function connectCollection(WP_REST_Request $request): WP_REST_Response
	{
		if (!current_user_can('manage_options')) {
			return new WP_REST_Response(
				esc_html__(
					'You are not authorized to perform this action.',
					'pantheon-content-publisher-for-wordpress'
				),
				401
			);
		}

		try {
			$collectionId = sanitize_text_field($request->get_param('collection_id'));
			$accessToken = sanitize_text_field($request->get_param('access_token'));

			// Validate input fields
			if (empty($collectionId) || empty($accessToken)) {
				return new WP_REST_Response(
					esc_html__('Missing collection ID or access token', 'pantheon-content-publisher-for-wordpress'),
					400
				);
			}

			// Validate collection ID and access token with PCC API
			try {
				$client = new PccClient(new PccClientConfig($collectionId, $accessToken));
				$siteApi = new SitesApi($client);
				$siteResponse = $siteApi->getSite($collectionId);
			} catch (\Throwable $e) {
				error_log('PCC connectCollection API error: ' . $e->getMessage());
				return new WP_REST_Response(
					esc_html__('Failed to connect collection. Ensure your collection ID and access token are correct.', 'pantheon-content-publisher-for-wordpress'),
					400
				);
			}

			// Parse the JSON response
			$parsedResponse = json_decode($siteResponse, true);

			if (json_last_error() !== JSON_ERROR_NONE) {
				error_log('PCC connectCollection JSON decode error: ' . json_last_error_msg());
				return new WP_REST_Response(
					esc_html__('Failed to connect collection: Unable to reach Content Publisher API.', 'pantheon-content-publisher-for-wordpress'),
					500
				);
			}

			// Check for GraphQL errors
			if (isset($parsedResponse['errors']) && !empty($parsedResponse['errors'])) {
				$errorMessage = $parsedResponse['errors'][0]['message'] ?? 'Unknown error';
				error_log('PCC connectCollection GraphQL error: ' . $errorMessage);
				return new WP_REST_Response(
					esc_html__('Failed to connect collection: ' . $errorMessage, 'pantheon-content-publisher-for-wordpress'),
					400
				);
			}

			// Check if site data exists
			$site = $parsedResponse['data']['site'] ?? null;
			if (!$site || empty($site['id'])) {
				return new WP_REST_Response(
					esc_html__('Failed to connect collection: Collection not found.', 'pantheon-content-publisher-for-wordpress'),
					400
				);
			}

			// Update with the site id and access token (api key)
			update_option(PCC_SITE_ID_OPTION_KEY, $site['id']);
			update_option(PCC_ENCODED_SITE_URL_OPTION_KEY, md5(wp_parse_url(site_url())['host']));
			update_option(PCC_API_KEY_OPTION_KEY, $accessToken);

			// Update with the site id
			return new WP_REST_Response(esc_html__('Collection connected', 'pantheon-content-publisher-for-wordpress'));
		} catch (\Throwable $e) {
			error_log('PCC connectCollection unexpected error: ' . $e->getMessage());
			error_log('PCC connectCollection stack trace: ' . $e->getTraceAsString());
			return new WP_REST_Response(
				esc_html__('An unexpected error occurred while connecting the collection. Please try again. Contact support if the issue persists.', 'pantheon-content-publisher-for-wordpress'),
				500
			);
		}
	}
}
