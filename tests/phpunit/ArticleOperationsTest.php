<?php

/**
 * PHPUnit tests for article operations integration with WordPress.
 *
 * Covers creating, updating, publishing, and locating posts synchronized
 * from Content Publisher via `PccSyncManager`.
 */

namespace Pantheon\ContentPublisher\Tests;

use Pantheon\ContentPublisher\PccSyncManager;
use PccPhpSdk\api\Response\Article;
use WP_UnitTestCase;

/**
 * Verifies end-to-end behavior of storing and retrieving articles as WP posts.
 */
class ArticleOperationsTest extends WP_UnitTestCase
{
	private PccSyncManager $manager;

  /**
   * Prepare WordPress environment and instantiate the sync manager for tests.
   */
	public function setUp(): void
	{
		parent::setUp();
	  // Use the standard `post` type.
		update_option(PCC_INTEGRATION_POST_TYPE_OPTION_KEY, 'post');
		$this->manager = new PccSyncManager();
	}

  /**
   * Creates an article object with defaults, allowing overrides per test.
   *
   * @param array<string,mixed> $overrides
   * @return Article
   */
	private function makeArticle(array $overrides = []): Article
	{
		$article = new Article();
		$article->id = $overrides['id'] ?? 'doc-1';
		$article->slug = $overrides['slug'] ?? 'hello-world';
		$article->title = $overrides['title'] ?? 'Original Title';
		$article->content = $overrides['content'] ?? '<p>Body</p>';
		$article->tags = $overrides['tags'] ?? ['tag-one', 'tag-two'];
		$article->metadata = $overrides['metadata'] ?? [
		'title' => 'Custom Title',
		'description' => 'Desc',
		'Categories' => 'Cat A,Cat B',
		];
		return $article;
	}

  /**
   * Ensures no post is returned when no WordPress post is connected to the PCC content ID.
   */
	public function testFindExistingConnectedPostReturnsNullWhenAbsent(): void
	{
	  // No post exists with this PCC content ID.
		$this->assertNull($this->manager->findExistingConnectedPost('non-existent-id'));
	}

  /**
   * Validates lookup prefers a published post but can target a specific status when requested.
   */
	public function testFindExistingConnectedPostByMetaAndStatus(): void
	{
	  // Create a published post connected to the same PCC content ID.
		$publishedId = wp_insert_post([
		'post_status' => 'publish',
		'post_title' => 'Published',
		'post_type' => 'post',
		]);
		update_post_meta($publishedId, PCC_CONTENT_META_KEY, 'doc-123');

	  // Create a draft connected to the same PCC content ID.
		$draftId = wp_insert_post([
		'post_status' => 'draft',
		'post_title' => 'Draft',
		'post_type' => 'post',
		]);
		update_post_meta($draftId, PCC_CONTENT_META_KEY, 'doc-123');

	  // Default behavior finds the published post.
		$this->assertSame($publishedId, $this->manager->findExistingConnectedPost('doc-123'));
	  // When status is specified, return the matching status.
		$this->assertSame($draftId, $this->manager->findExistingConnectedPost('doc-123', 'draft'));
		$this->assertSame($publishedId, $this->manager->findExistingConnectedPost('doc-123', 'publish'));
	}

  /**
   * Stores a new article as a published WP post and verifies fields, tags, and categories.
   */
	public function testStoreArticleCreatesPostAndIsRetrievable(): void
	{
	  // Create an article with custom metadata
		$article = $this->makeArticle([
		'id' => 'doc-1',
		'slug' => 'hello-world',
		'metadata' => [
		'title' => 'Custom Title',
		'description' => 'Desc',
		'Categories' => 'Cat A,Cat B',
		],
		]);

	  // Store the article as published.
		$postId = $this->manager->storeArticle($article, false);

	  // The post was created and can be found by PCC content ID.
		$this->assertIsInt($postId);
		$this->assertGreaterThan(0, $postId);
		$this->assertSame($postId, $this->manager->findExistingConnectedPost('doc-1'));

	  // Core post fields and excerpt/body should reflect article data.
		$post = get_post($postId);
		$this->assertSame('post', $post->post_type);
		$this->assertSame('publish', $post->post_status);
		$this->assertSame('hello-world', $post->post_name);
		$this->assertSame('Custom Title', $post->post_title);
		$this->assertStringContainsString('<p>Body</p>', $post->post_content);
		$this->assertSame('Desc', $post->post_excerpt);

	  // Tags and categories should be applied from article metadata.
		$tagNames = wp_get_post_terms($postId, 'post_tag', ['fields' => 'names']);
		$this->assertIsArray($tagNames);
		$this->assertNotEmpty(array_intersect(['tag-one', 'tag-two'], $tagNames));

		$catNames = wp_get_post_terms($postId, 'category', ['fields' => 'names']);
		$this->assertNotEmpty(array_intersect(['Cat A', 'Cat B'], $catNames));
	}

  /**
   * When stored as a draft, the post status should remain `draft`.
   */
	public function testStoreArticleDraftStatus(): void
	{
	  // Create an article and store as draft.
		$article = $this->makeArticle(['id' => 'doc-2', 'slug' => 'as-draft']);
		$postId = $this->manager->storeArticle($article, true);

	  // Post status should be `draft`.
		$post = get_post($postId);
		$this->assertSame('draft', $post->post_status);
	}

  /**
   * Updates an existing post when the same PCC content ID is stored again.
   *
   * Verifies that fields, tags, and categories are replaced with the new values.
   */
	public function testStoreArticleUpdatesExistingPost(): void
	{
		$article = $this->makeArticle(['id' => 'doc-3', 'slug' => 'first-slug']);
		$postId1 = $this->manager->storeArticle($article, false);

	  // Store updated article with the same ID.
		$articleUpdated = $this->makeArticle([
		'id' => 'doc-3',
		'slug' => 'updated-slug',
		'content' => '<p>Updated Body</p>',
		'metadata' => [
		'title' => 'Updated Title',
		'description' => 'Updated Desc',
		'Categories' => 'Cat C',
		],
		'tags' => ['tag-three'],
		]);

		$postId2 = $this->manager->storeArticle($articleUpdated, false);

	  // Same post should be updated in place.
		$this->assertSame($postId1, $postId2);

	  // Fields/body/excerpt should be updated.
		$post = get_post($postId2);
		$this->assertSame('updated-slug', $post->post_name);
		$this->assertSame('Updated Title', $post->post_title);
		$this->assertStringContainsString('<p>Updated Body</p>', $post->post_content);
		$this->assertSame('Updated Desc', $post->post_excerpt);

	  // Taxonomies should be replaced with new values.
		$tagNames = wp_get_post_terms($postId2, 'post_tag', ['fields' => 'names']);
		$this->assertSame(['tag-three'], array_values($tagNames));

		$catNames = wp_get_post_terms($postId2, 'category', ['fields' => 'names']);
		$this->assertSame(['Cat C'], array_values($catNames));
	}

  /**
   * Promotes an existing draft to published for the same PCC content ID without creating duplicates.
   */
	public function testPublishUpdatesExistingDraftWithoutDuplication(): void
	{
	  // Create draft post connected to PCC content ID.
		$article = $this->makeArticle([
		'id' => 'doc-4',
		'slug' => 'draft-slug',
		'metadata' => [
		'title' => 'Draft Title',
		'description' => 'Draft Desc',
		'Categories' => 'Cat P',
		],
		]);

		$draftId = $this->manager->storeArticle($article, true);
		$this->assertIsInt($draftId);
		$this->assertGreaterThan(0, $draftId);
		$this->assertSame('draft', get_post_status($draftId));

	  // Store a published version of the same article.
		$articlePublished = $this->makeArticle([
		'id' => 'doc-4',
		'slug' => 'published-slug',
		'content' => '<p>Published Body</p>',
		'metadata' => [
		'title' => 'Published Title',
		'description' => 'Published Desc',
		'Categories' => 'Cat Q',
		],
		'tags' => ['tag-published'],
		]);

		$publishedId = $this->manager->storeArticle($articlePublished, false);

	  // Draft was promoted in place (same ID), no duplicate created.
		$this->assertSame($draftId, $publishedId);

	  // Post is now published and updated with new values.
		$post = get_post($publishedId);
		$this->assertSame('publish', $post->post_status);
		$this->assertSame('published-slug', $post->post_name);
		$this->assertSame('Published Title', $post->post_title);
		$this->assertStringContainsString('<p>Published Body</p>', $post->post_content);
		$this->assertSame('Published Desc', $post->post_excerpt);

		$tagNames = wp_get_post_terms($publishedId, 'post_tag', ['fields' => 'names']);
		$this->assertSame(['tag-published'], array_values($tagNames));

		$catNames = wp_get_post_terms($publishedId, 'category', ['fields' => 'names']);
		$this->assertSame(['Cat Q'], array_values($catNames));

	  // Exactly one post remains connected to the PCC content ID.
		$dupCheck = get_posts([
		'post_type' => 'any',
		'post_status' => 'any',
		'meta_key' => PCC_CONTENT_META_KEY,
		'meta_value' => 'doc-4',
		'fields' => 'ids',
		'numberposts' => -1,
		]);
		$this->assertCount(1, $dupCheck);
	}
}
