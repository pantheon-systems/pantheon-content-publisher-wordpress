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
				error_log('Pantheon Content Publisher: media_sideload_image does not exist after includes');
				return;
			}
		}

		// WordPress's media_sideload_image() normally generates thumbnails during upload (10-60+ seconds).
		// This causes PHP-FPM timeout on Pantheon. We use filters to prevent thumbnail generation NOW,
		// and defer it to WP-Cron instead. These filters intercept WordPress's internal calls and skip
		// the expensive operations, allowing only the full-size image to upload (fast ~1-2s).
		add_filter('intermediate_image_sizes_advanced', [$this, 'skipThumbnailGeneration']);
		add_filter('big_image_size_threshold', [$this, 'skipImageScaling']);
		add_filter('wp_generate_attachment_metadata', [$this, 'skipMetadataGeneration'], 10, 2);

		try {
			// Upload full-size image only (filters above prevent the timeout-causing thumbnail generation)
			$imageId = media_sideload_image($featuredImageURL, $postId, null, 'id');

			// Remove filters immediately - only this specific image should skip thumbnails, not subsequent uploads
			remove_filter('intermediate_image_sizes_advanced', [$this, 'skipThumbnailGeneration']);
			remove_filter('big_image_size_threshold', [$this, 'skipImageScaling']);
			remove_filter('wp_generate_attachment_metadata', [$this, 'skipMetadataGeneration'], 10);

			// Check if image download returned an error
			if (is_wp_error($imageId)) {
				$error_msg = 'Pantheon Content Publisher: Failed to download featured image - ';
			error_log($error_msg . $imageId->get_error_message());
				// Mark post as having a failed featured image for admin attention
				update_post_meta($postId, '_pcc_featured_image_failed', [
					'url' => $featuredImageURL,
					'error' => $imageId->get_error_message(),
					'timestamp' => current_time('mysql')
				]);
				return;
			}

			if (is_int($imageId)) {
				update_post_meta($imageId, 'cpub_feature_image_url', $featuredImageURL);
				// Set full-size image as featured image (thumbnails don't exist yet, but full-size displays correctly)
				set_post_thumbnail($postId, $imageId);
				// Schedule WP-Cron to generate the skipped thumbnails in background (~1 min delay).
				// Thumbnails that would timeout NOW (10-60s) run safely later via WP-Cron.
				$this->scheduleAsyncThumbnailGeneration($imageId);
			}
		} catch (\Exception $e) {
			error_log('Pantheon Content Publisher: Exception processing featured image - ' . $e->getMessage());
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

	/**
	 * Skip thumbnail generation during image upload to prevent timeout.
	 * Returns empty array to prevent WordPress from creating any thumbnail sizes.
	 *
	 * @param array $sizes Array of thumbnail sizes
	 * @return array Empty array
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function skipThumbnailGeneration($_sizes)
	{
		return [];
	}

	/**
	 * Prevent WordPress from creating a "scaled" version of large images.
	 * The scaled version is created when images exceed the threshold (default 2560px).
	 *
	 * @param int $threshold The pixel threshold
	 * @return bool False to disable scaling
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function skipImageScaling($_threshold)
	{
		return false;
	}

	/**
	 * Skip metadata generation during image upload.
	 * Returns minimal metadata to prevent thumbnail generation from happening.
	 *
	 * @param array $metadata Attachment metadata
	 * @param int $attachment_id Attachment ID
	 * @return array Minimal metadata to prevent processing
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function skipMetadataGeneration($metadata, $_attachment_id)
	{
		// Return minimal metadata to prevent any image processing
		// Full metadata will be generated async later
		return [
			'file' => isset($metadata['file']) ? $metadata['file'] : '',
			'width' => isset($metadata['width']) ? $metadata['width'] : 0,
			'height' => isset($metadata['height']) ? $metadata['height'] : 0,
			'filesize' => isset($metadata['filesize']) ? $metadata['filesize'] : 0,
		];
	}

	/**
	 * Schedule asynchronous thumbnail generation for an image using WP-Cron.
	 * The full-size image is available immediately; thumbnails will be generated
	 * within a few minutes via WordPress's cron system.
	 *
	 * @param int $imageId The attachment ID to generate thumbnails for
	 */
	private function scheduleAsyncThumbnailGeneration($imageId)
	{
		// Schedule thumbnail generation to run in 1 minute
		// WP-Cron will process this on the next page load after the scheduled time
		wp_schedule_single_event(time() + 60, 'cpub_generate_thumbnails', [$imageId]);
	}

	/**
	 * Generate thumbnails for an image asynchronously.
	 * This is called by the REST endpoint in a separate PHP process.
	 *
	 * @param int $imageId The attachment ID to generate thumbnails for
	 */
	public static function generateThumbnailsAsync($imageId)
	{
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$file_path = get_attached_file($imageId);
		if (!$file_path || !file_exists($file_path)) {
			$error_msg = 'Pantheon Content Publisher: File not found for async thumbnail generation, ';
			error_log($error_msg . 'image ID: ' . $imageId);
			return;
		}

		// Generate all thumbnail sizes
		$metadata = wp_generate_attachment_metadata($imageId, $file_path);

		if (is_wp_error($metadata)) {
			$error_msg = 'Pantheon Content Publisher: Thumbnail generation failed for image ';
			error_log($error_msg . $imageId . ' - ' . $metadata->get_error_message());
			return;
		}

		// Update attachment metadata with new thumbnail info
		wp_update_attachment_metadata($imageId, $metadata);
	}
}
