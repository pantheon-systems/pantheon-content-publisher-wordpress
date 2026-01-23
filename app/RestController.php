<?php

/*
 * REST controller class exposing endpoints for OAuth2 authorization and credentials saving.
 */

namespace Pantheon\ContentPublisher;

if (!defined('ABSPATH')) {
	exit;
}

use WP_REST_Request;
use WP_REST_Response;
use PccPhpSdk\api\Query\Enums\PublishingLevel;
use PccPhpSdk\api\SitesApi;
use PccPhpSdk\core\PccClient;
use PccPhpSdk\core\PccClientConfig;
use PccPhpSdk\core\Status\Status;
use PccPhpSdk\core\Status\StatusOptions;

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
				'route' => '/webhook-notice',
				'method' => 'PUT',
				'callback' => [$this, 'updateWebhookNoticeState'],
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
		$pccManager = new PccSyncManager();
		$config = $pccManager->getClientConfig();
		$isPCCConfigured = $pccManager->isPCCConfigured();

		$options = new StatusOptions(
			smartComponents: false,
			smartComponentsCount: 0,
			smartComponentPreview: false,
			metadataGroups: false,
			metadataGroupIdentifiers: null,
			resolvePathConfigured: true,
			notFoundPath: ''
		);
		$status = new Status($config, $options);

		$payload = $status->toArray();

		return new WP_REST_Response($payload);
	}

	/**
	 * Handle incoming webhook requests.
	 * @return void|WP_REST_Response
	 */
	public function handleWebhook(WP_REST_Request $request)
	{
		// Timing-safe secret comparison
		$expected_secret = (string) get_option(PCC_WEBHOOK_SECRET_OPTION_KEY);
		$provided_secret = (string) $request->get_header('x-pcc-webhook-secret');

		// prevent empty secret or header
		// Protection against unconfigured secrets
		if ('' === $expected_secret) {
			return new WP_REST_Response('Webhook configuration missing', 500);
		}

		// provide the user-supplied string as the second parameter, rather than the first.
		if (!hash_equals($expected_secret, $provided_secret)) {
			error_log('PCC Webhook: Unauthorized attempt at ' . current_time('mysql'));
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
			return new WP_REST_Response(
				esc_html__('Invalid article ID in payload', 'pantheon-content-publisher'),
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
		if (!empty($cookie_error)) {
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
			return new WP_REST_Response(
				esc_html__(
					'You are not authorized to perform this action.',
					'pantheon-content-publisher'
				),
				401
			);
		}
		// Check management token is set
		if (!get_option(CPUB_ACCESS_TOKEN_OPTION_KEY)) {
			return new WP_REST_Response(
				esc_html__('Management token is not set yet', 'pantheon-content-publisher'),
				401
			);
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
			return new WP_REST_Response(
				esc_html__('Management token is not set yet', 'pantheon-content-publisher'),
				400
			);
		}

		// Check site id is set
		if (!get_option(CPUB_SITE_ID_OPTION_KEY)) {
			return new WP_REST_Response(
				esc_html__('Site is not created yet', 'pantheon-content-publisher'),
				400
			);
		}

		// Check if you are authorized
		if (!current_user_can('manage_options')) {
			return new WP_REST_Response(
				esc_html__(
					'You are not authorized to perform this action.',
					'pantheon-content-publisher'
				),
				401
			);
		}

		$siteManager = new PccSiteManager();
		if ($siteManager->registerWebhook()) {
			return new WP_REST_Response(
				esc_html__('Webhook registered', 'pantheon-content-publisher')
			);
		}

		return new WP_REST_Response(
			esc_html__('Error while register webhook', 'pantheon-content-publisher'),
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
		if (!get_option(CPUB_SITE_ID_OPTION_KEY)) {
			return new WP_REST_Response(
				esc_html__('Site is not created yet', 'pantheon-content-publisher'),
				400
			);
		}

		// Check if you are authorized
		if (!current_user_can('manage_options')) {
			return new WP_REST_Response(
				esc_html__(
					'You are not authorized to perform this action.',
					'pantheon-content-publisher'
				),
				401
			);
		}

		$siteManager = new PccSiteManager();
		$apiKey = $siteManager->createSiteApiKey();
		if ($apiKey) {
			update_option(CPUB_API_KEY_OPTION_KEY, $apiKey);
			return new WP_REST_Response(esc_html__('API created', 'pantheon-content-publisher'));
		}

		return new WP_REST_Response(
			esc_html__('Error while creating API key', 'pantheon-content-publisher'),
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
			return new WP_REST_Response(
				esc_html__(
					'You are not authorized to perform this action.',
					'pantheon-content-publisher'
				),
				401
			);
		}

		$accessToken = sanitize_text_field($request->get_param('access_token'));

		// Validate input field
		if (empty($accessToken)) {
			return new WP_REST_Response(
				esc_html__(
					'Management token cannot be empty.',
					'pantheon-content-publisher'
				),
				400
			);
		}

		// Validate token with PCC API
		$siteManager = new PccSiteManager();
		$isValid = $siteManager->validateManagementToken($accessToken);
		if (!$isValid) {
			return new WP_REST_Response(
				esc_html__(
					'Management token is invalid. Visit the Content Publisher dashboard to generate a new token.',
					'pantheon-content-publisher'
				),
				400
			);
		}

		update_option(CPUB_ACCESS_TOKEN_OPTION_KEY, $accessToken);
		return new WP_REST_Response(
			esc_html__(
				'Management token saved.',
				'pantheon-content-publisher'
			),
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
					'pantheon-content-publisher'
				),
				401
			);
		}

		// Disconnect the site
		$manager = new PccSyncManager();
		$manager->disconnect();

		// Reset dismissed webhook notice
		update_option(CPUB_WEBHOOK_NOTICE_DISMISSED_OPTION_KEY, false);

		return new WP_REST_Response(
			esc_html__('Saved Data deleted.', 'pantheon-content-publisher'),
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
					'pantheon-content-publisher'
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
					esc_html__('Missing collection ID or access token', 'pantheon-content-publisher'),
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
					esc_html__('Failed to connect collection. Ensure your collection ID and access token are correct.', 'pantheon-content-publisher'),
					400
				);
			}

			// Parse the JSON response
			$parsedResponse = json_decode($siteResponse, true);

			if (json_last_error() !== JSON_ERROR_NONE) {
				error_log('PCC connectCollection JSON decode error: ' . json_last_error_msg());
				return new WP_REST_Response(
					esc_html__('Failed to connect collection: Unable to reach Content Publisher API.', 'pantheon-content-publisher'),
					500
				);
			}

			// Check for GraphQL errors
			if (isset($parsedResponse['errors']) && !empty($parsedResponse['errors'])) {
				$errorMessage = $parsedResponse['errors'][0]['message'] ?? 'Unknown error';
				error_log('PCC connectCollection GraphQL error: ' . $errorMessage);
				// translators: %s: Error message from the Content Publisher API
				return new WP_REST_Response(
					esc_html(
						sprintf(
							__( 'Failed to connect collection: %s', 'pantheon-content-publisher' ),
							$errorMessage
						)
					),
					400
				);

			// Check if site data exists
			$site = $parsedResponse['data']['site'] ?? null;
			if (!$site || empty($site['id'])) {
				return new WP_REST_Response(
					esc_html__('Failed to connect collection: Collection not found.', 'pantheon-content-publisher'),
					400
				);
			}

			// Update with the site id and access token (api key)
			update_option(CPUB_SITE_ID_OPTION_KEY, $site['id']);
			update_option(CPUB_ENCODED_SITE_URL_OPTION_KEY, md5(wp_parse_url(site_url())['host']));
			update_option(CPUB_API_KEY_OPTION_KEY, $accessToken);

			// Ensure webhook notice is not dismissed for newly connected existing collections
			update_option(CPUB_WEBHOOK_NOTICE_DISMISSED_OPTION_KEY, false);

			// Update with the site id
			return new WP_REST_Response(esc_html__('Collection connected', 'pantheon-content-publisher'));
		} catch (\Throwable $e) {
			error_log('PCC connectCollection unexpected error: ' . $e->getMessage());
			error_log('PCC connectCollection stack trace: ' . $e->getTraceAsString());
			return new WP_REST_Response(
				esc_html__('An unexpected error occurred while connecting the collection. Please try again. Contact support if the issue persists.', 'pantheon-content-publisher'),
				500
			);
		}
	}

	/**
	 * Update webhook notice state for current site
	 */
	public function updateWebhookNoticeState(WP_REST_Request $request): WP_REST_Response
	{
		if (!current_user_can('manage_options')) {
			return new WP_REST_Response(
				esc_html__(
					'You are not authorized to perform this action.',
					'pantheon-content-publisher'
				),
				401
			);
		}

		$dismissed = (bool) $request->get_param('dismissed');
		update_option(CPUB_WEBHOOK_NOTICE_DISMISSED_OPTION_KEY, $dismissed);

		return new WP_REST_Response([
			'dismissed' => $dismissed,
		]);
	}

	private function getPluginVersion(): string
	{
		$headers = get_file_data(CPUB_PLUGIN_FILE, ['Version' => 'Version']);
		return isset($headers['Version']) ? (string) $headers['Version'] : '';
	}
}
