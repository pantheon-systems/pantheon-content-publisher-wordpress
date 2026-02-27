<?php

namespace Pantheon\ContentPublisher;

if (!defined('ABSPATH')) {
	exit;
}

use PccPhpSdk\api\ArticlesApi;
use PccPhpSdk\api\Query\Enums\ContentType;
use PccPhpSdk\api\Query\Enums\PublishingLevel;
use PccPhpSdk\api\Response\Article;
use PccPhpSdk\core\PccClient;
use PccPhpSdk\core\PccClientConfig;
use PccPhpSdk\core\Query\GraphQLQuery;

use function media_sideload_image;

class PccSyncManager
{
	/**
	 * @var string $siteId
	 */
	private string $siteId;
	private string $apiKey;

	public function __construct()
	{
		$this->siteId = get_option(CPUB_SITE_ID_OPTION_KEY);
		$this->apiKey = get_option(CPUB_API_KEY_OPTION_KEY);
	}

	/**
	 * Fetch and store document.
	 *
	 * @param $documentId
	 * @param PublishingLevel $publishingLevel
	 * @param bool $isDraft
	 * @param PccClient|null $pccClient
	 * @param string|null $versionId
	 * @return int
	 */
	public function fetchAndStoreDocument(
		$documentId,
		PublishingLevel $publishingLevel,
		bool $isDraft = false,
		?PccClient $pccClient = null,
		?string $versionId = null
	): int {
		$articlesApi = new ArticlesApi($pccClient ?? $this->pccClient());
		$article = $articlesApi->getArticleById(
			$documentId,
			[
				'id',
				'slug',
				'title',
				'tags',
				'content',
				'metadata',
			],
			$publishingLevel,
			ContentType::TREE_PANTHEON_V2,
			$versionId
		);

		return $article ? $this->storeArticle($article, $isDraft) : 0;
	}

	/**
	 * Build PccClientConfig.
	 *
	 * @param string|null $pccGrant
	 * @return PccClientConfig
	 */
	public function getClientConfig(?string $pccGrant = null): PccClientConfig
	{
		$args = [$this->siteId, $this->apiKey];
		if ($pccGrant) {
			$args = [$this->siteId, '', null, $pccGrant];
		}

		return new PccClientConfig(...$args);
	}

	/**
	 * Get PccClient instance.
	 *
	 * @param string|null $pccGrant
	 * @return PccClient
	 */
	public function pccClient(?string $pccGrant = null): PccClient
	{
		$config = $this->getClientConfig($pccGrant);

		return new PccClient($config);
	}

	/**
	 * Store article.
	 *
	 * @param Article $article
	 * @param bool $isDraft
	 * @return int
	 */
	public function storeArticle(Article $article, bool $isDraft = false)
	{
		$postId = $this->findExistingConnectedPost($article->id);

		return $this->createOrUpdatePost($postId, $article, $isDraft);
	}

	/**
	 * @param $value
	 * @return int|null
	 */
	public function findExistingConnectedPost($value, $postStatus = null)
	{
		$args = [
			'post_type'   => 'any',
			'post_status' => 'any',
			'meta_key'    => CPUB_CONTENT_META_KEY,
			'meta_value'  => $value,
			'fields'      => 'ids',
			'numberposts' => 1,
		];

		if ($postStatus) {
			$args['post_status'] = $postStatus;
		}

		$posts = get_posts($args);
		return !empty($posts) ? (int) $posts[0] : null;
	}

	/**
	 * Create or update post.
	 *
	 * @param $postId
	 * @param Article $article
	 * @param bool $isDraft
	 * @return int post id
	 */
	private function createOrUpdatePost($postId, Article $article, bool $isDraft = false)
	{
		$preparedData = $this->preparePostDataFromArticle($article);

		$data = [
			'post_title' => $preparedData['post_title'],
			'post_content' => $preparedData['post_content'],
			'post_excerpt' => $preparedData['post_excerpt'],
			'post_status' => $isDraft ? 'draft' : 'publish',
			'post_name' => $article->slug,
			'post_type' => $this->getIntegrationPostType(),
		];

		if (!$postId) {
			$insertData = $data;
			$insertData['post_author'] = $this->getDefaultAuthorId($article);
			$postId = wp_insert_post($insertData);
			update_post_meta($postId, CPUB_CONTENT_META_KEY, $article->id);
			$this->syncPostMetaAndTags($postId, $article);
			return $postId;
		}

		$data['ID'] = $postId;
		wp_update_post($data);
		$this->syncPostMetaAndTags($postId, $article);
		return $postId;
	}

	/**
	 * Update post tags.
	 *
	 * @param $postId
	 * @param Article $article
	 */
	private function syncPostMetaAndTags($postId, Article $article): void
	{
		//static variable persists between calls withing the same request
		static $yoastActive; // implicitly null

		// Cache Yoast active status for this request to avoid repeated DB checks
		if (!isset($yoastActive)) {
			$activePlugins = apply_filters('active_plugins', get_option('active_plugins'));
			$yoastActive = in_array('wordpress-seo/wp-seo.php', $activePlugins, true);
		}

		if (isset($article->tags) && is_array($article->tags)) {
			wp_set_post_terms($postId, $article->tags, 'post_tag', false);
		}

		if (!isset($article->metadata)) {
			return;
		}

		$this->setPostFeatureImage($postId, $article);
		if (isset($article->metadata['Categories'])) {
			wp_set_post_categories($postId, $this->findArticleCategories($article));
		}

		if ($yoastActive) {
			if (isset($article->metadata['title'])) {
			update_post_meta($postId, '_yoast_wpseo_title', $article->metadata['title']);
			}
			if (isset($article->metadata['description'])) {
				update_post_meta($postId, '_yoast_wpseo_metadesc', $article->metadata['description']);
			}
		}

		// Apply ACF field mappings when configured.
		(new AcfFieldMapper())->applyMappings(
			$postId,
			$this->getIntegrationPostType(),
			(array) $article->metadata
		);
	}

	private function getFeaturedImageKey()
	{
		return apply_filters('cpub_featured_image_key', 'image');
	}

	/**
	 * Set the post feature image.
	 *
	 * @param $postId
	 * @param Article $article
	 */
	private function setPostFeatureImage($postId, Article $article)
	{
		$metadata = $article->metadata ?? [];
		$imageKey = $this->getFeaturedImageKey();
		$legacyKey = 'FeaturedImage';

		$hasNewKey = is_array($metadata) && array_key_exists($imageKey, $metadata) && $metadata[$imageKey];
		$hasLegacyKey = is_array($metadata) && array_key_exists($legacyKey, $metadata) && $metadata[$legacyKey];

		if (!$hasNewKey && !$hasLegacyKey) {
			return;
		}

		$selectedKey = $hasNewKey ? $imageKey : $legacyKey;
		$imageValue = $metadata[$selectedKey] ?? null;

		// If the selected key is present but empty, delete the existing thumbnail.
		if (!$imageValue) {
			delete_post_thumbnail($postId);
			return;
		}

		$featuredImageURL = $imageValue . '#image.jpg';

		// Check if there was an existing image.
		$existingImageId = $this->getImageIdByUrl($featuredImageURL);
		if ($existingImageId) {
			set_post_thumbnail($postId, $existingImageId);
			return;
		}

		// Ensure media_sideload_image function is available.
		if (!function_exists('media_sideload_image')) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			if (!function_exists('media_sideload_image')) {
				error_log('media_sideload_image does not exist after includes, returning');
				// has to fail silently
				return;
			}
		}

		// Download and attach the new image.
		$imageId = media_sideload_image($featuredImageURL, $postId, null, 'id');

		if (is_int($imageId)) {
			update_post_meta($imageId, 'cpub_feature_image_url', $featuredImageURL);
			// Set as the featured image.
			set_post_thumbnail($postId, $imageId);
		}
	}

	/**
	 * Retrieves an image ID by searching for the URL in the image post meta.
	 *
	 * @param string $imageUrl The URL of the image to search for.
	 * @return int|false The image ID if found, or false if not found.
	 */
	private function getImageIdByUrl($imageUrl)
	{
		$args = [
			'post_type'  => 'attachment', // Ensure we're looking for attachments.
			'meta_key'   => 'cpub_feature_image_url',
			'meta_value' => $imageUrl,
			'fields'     => 'ids', // Return only the IDs.
			'numberposts' => 1,    // Limit to 1 post.
		];

		$image_ids = get_posts($args);

		return !empty($image_ids) ? (int) $image_ids[0] : false;
	}


	/**
	 * Find or create categories.
	 *
	 * @param Article $article
	 * @return array
	 */
	private function findArticleCategories(Article $article): array
	{
		// TODO: actually get the categories from the Google Doc Form
		// Right now, it's empty
		$categories = $article->metadata['Categories'] ? explode(',', (string) $article->metadata['Categories']) : [];
		$categories = array_filter($categories);
		if (!$categories) {
			return [];
		}

		return $this->findOrCreateCategories($categories);
	}

	/**
	 * Check if a category exists by name.
	 * If the category does not exist, create it.
	 *
	 * @param array $categories array of categories names to check or create.
	 *
	 * @return array The categories IDs.
	 */
	private function findOrCreateCategories(array $categories): array
	{
		$ids = [];
		if (!function_exists('wp_insert_term')) {
			error_log('wp_insert_term does not exist, category insert will fail');
			// has to fail silently
			return $ids;
		}

		foreach ($categories as $category) {
			$categoryId = (int) get_cat_ID($category);
			if (0 === $categoryId) {
				$newTerm = wp_insert_term($category, 'category');
				if (!is_wp_error($newTerm) && isset($newTerm['term_id'])) {
					$categoryId = (int) $newTerm['term_id'];
				}
			}
			$ids[] = $categoryId;
		}

		return $ids;
	}


	/**
	 * Get selected integration post type.
	 *
	 * @return false|mixed|null
	 */
	private function getIntegrationPostType()
	{
		return get_option(CPUB_INTEGRATION_POST_TYPE_OPTION_KEY);
	}

	/**
	 * Get the default author ID for content created by Content Publisher.
	 *
	 * Allows filtering via 'cpub_default_author_id'. The filter receives the
	 * computed default ID and the Article (if available). The filter can be
	 * used to override the default author ID for a given article.
	 *
	 * @param Article|null $article
	 * @return int
	 */
	public function getDefaultAuthorId(?Article $article = null): int
	{
		$adminIds = get_users([
			'role' => 'administrator',
			'orderby' => 'ID',
			'order' => 'ASC',
			'number' => 1,
			'fields' => 'ID',
		]);

		$defaultId = !empty($adminIds) ? (int) $adminIds[0] : 0;

		return (int) apply_filters('cpub_default_author_id', $defaultId, $article);
	}

	/**
	 * Store articles from PCC to WordPress.
	 */
	public function storeArticles()
	{
		if (!$this->getIntegrationPostType()) {
			return;
		}
		$articlesApi = new ArticlesApi($this->pccClient());
		$articles = $articlesApi->getAllArticles();
		/** @var Article $article */
		foreach ($articles->articles as $article) {
			$this->storeArticle($article);
		}
	}

	/**
	 * Publish post by document id.
	 *
	 * @param $documentId
	 * @return void
	 */
	public function unPublishPostByDocumentId($documentId)
	{
		$postId = $this->findExistingConnectedPost($documentId);
		if (!$postId) {
			return;
		}

		wp_update_post([
			'ID' => $postId,
			'post_status' => 'draft',
		]);
	}

	/**
	 * Get preview link.
	 * @param string $documentId
	 * @param $postId
	 * @param $pccGrant
	 * @param string|null $versionId
	 * @param PublishingLevel|null $publishingLevel
	 * @return string
	 */
	public function preparePreviewingURL(
		string $documentId,
		$postId = null,
		$pccGrant = null,
		?PublishingLevel $publishingLevel = null,
		?string $versionId = null
	): string {
		$postId = $postId ?: $this->findExistingConnectedPost($documentId);
		$queryArgs = [
			'publishing_level' => ($publishingLevel ?? PublishingLevel::REALTIME)->value,
			'document_id' => $documentId,
			'pccGrant' => $pccGrant,
		];
		if ($versionId) {
			$queryArgs['versionId'] = $versionId;
		}

		return add_query_arg($queryArgs, get_permalink($postId));
	}

	/**
	 * Disconnect PCC.
	 */
	public function disconnect()
	{
		delete_option(CPUB_ACCESS_TOKEN_OPTION_KEY);
		delete_option(CPUB_SITE_ID_OPTION_KEY);
		delete_option(CPUB_ENCODED_SITE_URL_OPTION_KEY);
		delete_option(CPUB_INTEGRATION_POST_TYPE_OPTION_KEY);
		delete_option(CPUB_WEBHOOK_SECRET_OPTION_KEY);
		delete_option(CPUB_API_KEY_OPTION_KEY);

		$this->removeMetaDataFromPosts();
	}

	/**
	 * Remove all saved meta from posts
	 *
	 * @return void
	 */
	private function removeMetaDataFromPosts()
	{
		// Delete all post meta entries with the key 'terminate'
		delete_post_meta_by_key(CPUB_CONTENT_META_KEY);
	}

	/**
	 * Check if PCC is configured.
	 *
	 * @return bool
	 */
	public function isPCCConfigured(): bool
	{
		$accessToken = get_option(CPUB_ACCESS_TOKEN_OPTION_KEY);
		$siteId = get_option(CPUB_SITE_ID_OPTION_KEY);
		$encodedSiteURL = get_option(CPUB_ENCODED_SITE_URL_OPTION_KEY);
		$apiKey = get_option(CPUB_API_KEY_OPTION_KEY);

		if ((!$accessToken && !$apiKey) || !$siteId || !$encodedSiteURL) {
			return false;
		}

		// TODO: Decide if we need to check the encoded site URL given a site can have multiple domains
		// $currentHashedSiteURL = md5(wp_parse_url(site_url())['host']);
		// if ($encodedSiteURL === $currentHashedSiteURL) {
		// 	return true;
		// }

		return true;
	}

	/**
	 * Prepare core post data fields from a PCC Article object.
	 *
	 * @param Article $article The PCC Article object.
	 * @return array Associative array with keys 'post_title', 'post_content', 'post_excerpt'.
	 */
	public function preparePostDataFromArticle(Article $article): array
	{
		// Original content
		$content = $article->content;

		// Pattern to match all style blocks
		$stylePattern = '/<style.*?>.*?<\/style>/is';

		// Remove all style blocks from the content
		$content = preg_replace($stylePattern, '', $content);

		$data = [
			'post_content' => $content,
			'post_title' => $article->title, // Default title
			'post_excerpt' => '', // Default empty excerpt
		];

		// Set post excerpt if description is available.
		if (isset($article->metadata['description'])) {
			$data['post_excerpt'] = $article->metadata['description'];
		}

		// Set post title if override is available.
		if (isset($article->metadata['title']) && $article->metadata['title']) {
			$data['post_title'] = $article->metadata['title'];
		}

		return $data;
	}

	public function getSiteData()
	{
		$siteApi = $this->pccClient();
		// TODO: Remove this query and use the SitesApi::getSite() method instead when
		// the getSite() method is extended to return the name of the site.
		$query = <<<'GRAPHQL'
		query GetSite($siteId: String!) {
			site(id: $siteId) {
				id
				url
				name
			}
		}
		GRAPHQL;
		$variables = new \ArrayObject(['siteId' => get_option(CPUB_SITE_ID_OPTION_KEY)]);
		$graphQLQuery = new GraphQLQuery($query, $variables);

		$siteResponse = $siteApi->executeQuery($graphQLQuery);

		// Parse the JSON response
		$parsedResponse = json_decode($siteResponse, true);

		// Check for GraphQL errors
		if (isset($parsedResponse['errors']) && !empty($parsedResponse['errors'])) {
			$errorMessage = $parsedResponse['errors'][0]['message'] ?? 'Unknown error';
			error_log('PCC connectCollection GraphQL error: ' . $errorMessage);
			return null;
		}

		// Check if site data exists
		$site = $parsedResponse['data']['site'] ?? null;
		if (!$site || empty($site['id'])) {
			return null;
		}

		return $site;
	}
}
