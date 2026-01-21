<?php

namespace Pantheon\ContentPublisher;

if ( ! defined( 'ABSPATH' ) ) exit;

use WP_Error;
use WP_HTTP_Requests_Response;

class PccSiteManager
{
	private $endpoints = [
		'create_site' => CPUB_ENDPOINT . '/sites',
		'site' => CPUB_ENDPOINT . '/sites/%s',
		'api_key' => CPUB_ENDPOINT . '/api-key',
		'default_site' => CPUB_ENDPOINT . '/sites/default',
	];

	/**
	 * @param string|null $siteId
	 * @return mixed|WP_Error
	 */
	public function registerWebhook()
	{
		$endpoint = sprintf($this->endpoints['site'], get_option(CPUB_SITE_ID_OPTION_KEY));
		$webhookSecret = $this->generateWebhookSecret();
		$args = [
			'method' => 'PATCH',
			'headers' => $this->getHeaders(),
			'body' => wp_json_encode([
				'webhookConfig' => [
					'webhookUrl' => $this->getWebhookEndpoint(),
					'webhookSecret' => $webhookSecret,
				]
			]),
		];
		$response = wp_remote_request($endpoint, $args);
		/** @var WP_HTTP_Requests_Response $response */
		$statusCode = $response['http_response']->get_status();
		if (204 === intval($statusCode)) {
			update_option(CPUB_WEBHOOK_SECRET_OPTION_KEY, $webhookSecret);
			update_option(CPUB_WEBHOOK_NOTICE_DISMISSED_OPTION_KEY, true);
			return true;
		}

		return new WP_Error(400, 'Error while registering webhook. Please try again.');
	}

	/**
	 * @return string
	 */
	private function generateWebhookSecret(): string
	{
		return wp_generate_password(32, false);
	}

	/**
	 * @return array[]
	 */
	private function getHeaders(string $token = null)
	{
		return [
			'Content-Type' => 'application/json',
			'Authorization' => sprintf('Bearer %s', $token ?? $this->getAccessToken()),
		];
	}

	/**
	 * @return false|mixed|null
	 */
	private function getAccessToken()
	{
		return trim(get_option(CPUB_ACCESS_TOKEN_OPTION_KEY));
	}

	/**
	 * @return string
	 */
	private function getWebhookEndpoint()
	{
		return rest_url(CPUB_API_NAMESPACE . '/webhook');
	}

	/**
	 * @return mixed|WP_Error
	 */
	public function getSiteID(): mixed
	{
		$siteURL = site_url();
		$sites = wp_remote_get($this->endpoints['create_site'], ['headers' => $this->getHeaders()]);
		foreach ($this->parseResponse($sites) as $site) {
			if ($siteURL === $site['url']) {
				return $site['id'];
			}
		}
		$args = [
			'headers' => $this->getHeaders(),
			'body' => wp_json_encode([
				'url' => $siteURL,
				'name' => get_bloginfo('name') ?: $siteURL,
			]),
		];
		$response = wp_remote_post($this->endpoints['create_site'], $args);
		$content = $this->parseResponse($response);

		if (isset($content['id']) && !empty($content['id'])) {
			return $content['id'];
		}

		if (isset($content['code']) && isset($content['message'])) {
			return new WP_Error($content['code'], $content['message']);
		}

		return new WP_Error(400, 'Error while creating your site. Please try again.');
	}

	/**
	 * @param $response
	 * @return mixed|WP_Error
	 */
	private function parseResponse($response)
	{
		if (is_wp_error($response)) {
			return $response;
		}

		// Handle HTTP 200 [PCC always returns 200]
		return json_decode(wp_remote_retrieve_body($response), true);
	}

	/**
	 * Create Site API Key for article management
	 *
	 * @return mixed|WP_Error
	 */
	public function createSiteApiKey()
	{
		$args = [
			'headers' => $this->getHeaders(),
			'body' => wp_json_encode([
				'siteId' => get_option(CPUB_SITE_ID_OPTION_KEY),
				'isManagementKey' => false,
			]),
		];
		$response = wp_remote_post($this->endpoints['api_key'], $args);
		$content = $this->parseResponse($response);

		if (isset($content['apiKey']) && !empty($content['apiKey'])) {
			return $content['apiKey'];
		}

		if (isset($content['code']) && isset($content['message'])) {
			return new WP_Error($content['code'], $content['message']);
		}

		return new WP_Error(400, 'Error while creating your API key. Please try again.');
	}

	/**
	 * Validate management token with PCC API
	 *
	 * @param string $managementToken
	 * @return bool
	 */
	public function validateManagementToken($managementToken)
	{
		$response = wp_remote_get($this->endpoints['default_site'], [
			'headers' => $this->getHeaders($managementToken),
		]);
		$content = $this->parseResponse($response);

		// If we get a 200 response and the site id is not empty, the token is valid
		if (isset($content['id']) && !empty($content['id'])) {
			return true;
		}

		return false;
	}
}
