<?php

/**
 * PHPUnit tests for the publish-as-draft setting functionality.
 *
 * Tests the three-mode system: 'publish', 'draft', and 'author_choice',
 * ensuring posts are created with correct status and existing published
 * posts are never unpublished.
 */

namespace Pantheon\ContentPublisher\Tests;

use Pantheon\ContentPublisher\PccSyncManager;
use PccPhpSdk\api\Response\Article;
use WP_UnitTestCase;

/**
 * Verifies publish-as-draft setting behavior across all three modes.
 */
class PublishAsDraftSettingTest extends WP_UnitTestCase
{
	private PccSyncManager $manager;

	/**
	 * Prepare WordPress environment and instantiate the sync manager for tests.
	 */
	public function setUp(): void
	{
		parent::setUp();
		// Use the standard `post` type.
		update_option(CPUB_INTEGRATION_POST_TYPE_OPTION_KEY, 'post');
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
		$article->id = $overrides['id'] ?? 'doc-test';
		$article->slug = $overrides['slug'] ?? 'test-slug';
		$article->title = $overrides['title'] ?? 'Test Title';
		$article->content = $overrides['content'] ?? '<p>Test content</p>';
		$article->tags = $overrides['tags'] ?? [];
		$article->metadata = $overrides['metadata'] ?? [];
		return $article;
	}

	/**
	 * Test 'publish' mode (default): new posts should be published.
	 */
	public function testPublishModeCreatesPublishedPost(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'publish');

		$article = $this->makeArticle(['id' => 'doc-publish-1']);
		$postId = $this->manager->storeArticle($article, false);

		$post = get_post($postId);
		$this->assertSame('publish', $post->post_status);
	}

	/**
	 * Test 'publish' mode: existing draft should be published when updated.
	 */
	public function testPublishModePromotesExistingDraft(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'publish');

		// Create as draft first
		$article = $this->makeArticle(['id' => 'doc-publish-2']);
		$draftId = $this->manager->storeArticle($article, true);
		$this->assertSame('draft', get_post_status($draftId));

		// Update with publish mode
		$articleUpdated = $this->makeArticle([
			'id' => 'doc-publish-2',
			'content' => '<p>Updated</p>',
		]);
		$publishedId = $this->manager->storeArticle($articleUpdated, false);

		$this->assertSame($draftId, $publishedId);
		$this->assertSame('publish', get_post_status($publishedId));
	}

	/**
	 * Test 'draft' mode: new posts should be created as drafts.
	 */
	public function testDraftModeCreatesNewPostAsDraft(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'draft');

		$article = $this->makeArticle(['id' => 'doc-draft-1']);
		$postId = $this->manager->storeArticle($article, false);

		$post = get_post($postId);
		$this->assertSame('draft', $post->post_status);
	}

	/**
	 * Test 'draft' mode: existing draft should remain draft when updated.
	 */
	public function testDraftModeKeepsExistingDraftAsDraft(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'draft');

		// Create as draft
		$article = $this->makeArticle(['id' => 'doc-draft-2']);
		$draftId = $this->manager->storeArticle($article, true);
		$this->assertSame('draft', get_post_status($draftId));

		// Update with draft mode
		$articleUpdated = $this->makeArticle([
			'id' => 'doc-draft-2',
			'content' => '<p>Updated</p>',
		]);
		$updatedId = $this->manager->storeArticle($articleUpdated, false);

		$this->assertSame($draftId, $updatedId);
		$this->assertSame('draft', get_post_status($updatedId));
	}

	/**
	 * Test 'draft' mode: CRITICAL - existing published post should NOT be unpublished.
	 */
	public function testDraftModeDoesNotUnpublishExistingPublishedPost(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'publish');

		// Create as published first
		$article = $this->makeArticle(['id' => 'doc-draft-3']);
		$publishedId = $this->manager->storeArticle($article, false);
		$this->assertSame('publish', get_post_status($publishedId));

		// Switch to draft mode and update
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'draft');
		$articleUpdated = $this->makeArticle([
			'id' => 'doc-draft-3',
			'content' => '<p>Updated content</p>',
		]);
		$updatedId = $this->manager->storeArticle($articleUpdated, false);

		// Should be same post, still published
		$this->assertSame($publishedId, $updatedId);
		$this->assertSame('publish', get_post_status($updatedId), 'Existing published post should NOT be unpublished by draft mode');
	}

	/**
	 * Test 'author_choice' mode: without metadata field, post should be published.
	 */
	public function testAuthorChoiceModePublishesWhenMetadataAbsent(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'author_choice');

		$article = $this->makeArticle([
			'id' => 'doc-author-1',
			'metadata' => [], // No 'publish-as-draft' field
		]);
		$postId = $this->manager->storeArticle($article, false);

		$post = get_post($postId);
		$this->assertSame('publish', $post->post_status);
	}

	/**
	 * Test 'author_choice' mode: metadata field false means publish.
	 */
	public function testAuthorChoiceModePublishesWhenMetadataIsFalse(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'author_choice');

		$article = $this->makeArticle([
			'id' => 'doc-author-2',
			'metadata' => ['publish-as-draft' => false],
		]);
		$postId = $this->manager->storeArticle($article, false);

		$post = get_post($postId);
		$this->assertSame('publish', $post->post_status);
	}

	/**
	 * Test 'author_choice' mode: metadata field true (boolean) creates draft for new post.
	 */
	public function testAuthorChoiceModeCreatesDraftWhenMetadataIsTrue(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'author_choice');

		$article = $this->makeArticle([
			'id' => 'doc-author-3',
			'metadata' => ['publish-as-draft' => true],
		]);
		$postId = $this->manager->storeArticle($article, false);

		$post = get_post($postId);
		$this->assertSame('draft', $post->post_status);
	}

	/**
	 * Test 'author_choice' mode: metadata field 'true' (string) creates draft for new post.
	 */
	public function testAuthorChoiceModeCreatesDraftWhenMetadataIsTrueString(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'author_choice');

		$article = $this->makeArticle([
			'id' => 'doc-author-4',
			'metadata' => ['publish-as-draft' => 'true'],
		]);
		$postId = $this->manager->storeArticle($article, false);

		$post = get_post($postId);
		$this->assertSame('draft', $post->post_status);
	}

	/**
	 * Test 'author_choice' mode: metadata field '1' (string) creates draft for new post.
	 */
	public function testAuthorChoiceModeCreatesDraftWhenMetadataIsOne(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'author_choice');

		$article = $this->makeArticle([
			'id' => 'doc-author-5',
			'metadata' => ['publish-as-draft' => '1'],
		]);
		$postId = $this->manager->storeArticle($article, false);

		$post = get_post($postId);
		$this->assertSame('draft', $post->post_status);
	}

	/**
	 * Test 'author_choice' mode: CRITICAL - existing published post should NOT be unpublished
	 * even if metadata says to publish as draft.
	 */
	public function testAuthorChoiceModeDoesNotUnpublishExistingPublishedPost(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'publish');

		// Create as published first
		$article = $this->makeArticle([
			'id' => 'doc-author-6',
			'metadata' => ['publish-as-draft' => false],
		]);
		$publishedId = $this->manager->storeArticle($article, false);
		$this->assertSame('publish', get_post_status($publishedId));

		// Switch to author_choice mode and update with draft metadata
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'author_choice');
		$articleUpdated = $this->makeArticle([
			'id' => 'doc-author-6',
			'content' => '<p>Updated with draft metadata</p>',
			'metadata' => ['publish-as-draft' => true], // Author now wants draft
		]);
		$updatedId = $this->manager->storeArticle($articleUpdated, false);

		// Should be same post, still published
		$this->assertSame($publishedId, $updatedId);
		$this->assertSame('publish', get_post_status($updatedId), 'Existing published post should NOT be unpublished even with draft metadata');
	}

	/**
	 * Test 'author_choice' mode: existing draft with draft metadata should remain draft.
	 */
	public function testAuthorChoiceModeKeepsExistingDraftAsDraft(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'author_choice');

		// Create as draft with metadata
		$article = $this->makeArticle([
			'id' => 'doc-author-7',
			'metadata' => ['publish-as-draft' => true],
		]);
		$draftId = $this->manager->storeArticle($article, true);
		$this->assertSame('draft', get_post_status($draftId));

		// Update with same draft metadata
		$articleUpdated = $this->makeArticle([
			'id' => 'doc-author-7',
			'content' => '<p>Updated</p>',
			'metadata' => ['publish-as-draft' => true],
		]);
		$updatedId = $this->manager->storeArticle($articleUpdated, false);

		$this->assertSame($draftId, $updatedId);
		$this->assertSame('draft', get_post_status($updatedId));
	}

	/**
	 * Test 'author_choice' mode: existing draft can be promoted to published when metadata is false.
	 */
	public function testAuthorChoiceModePromotesDraftWhenMetadataIsFalse(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'author_choice');

		// Create as draft
		$article = $this->makeArticle([
			'id' => 'doc-author-8',
			'metadata' => ['publish-as-draft' => true],
		]);
		$draftId = $this->manager->storeArticle($article, true);
		$this->assertSame('draft', get_post_status($draftId));

		// Update with publish metadata
		$articleUpdated = $this->makeArticle([
			'id' => 'doc-author-8',
			'content' => '<p>Ready to publish</p>',
			'metadata' => ['publish-as-draft' => false],
		]);
		$publishedId = $this->manager->storeArticle($articleUpdated, false);

		$this->assertSame($draftId, $publishedId);
		$this->assertSame('publish', get_post_status($publishedId));
	}

	/**
	 * Test preview mode: always draft regardless of publish-as-draft setting.
	 */
	public function testPreviewModeAlwaysCreatesDraftRegardlessOfSetting(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'publish');

		$article = $this->makeArticle(['id' => 'doc-preview-1']);
		$postId = $this->manager->storeArticle($article, true); // $isDraft = true (preview mode)

		$post = get_post($postId);
		$this->assertSame('draft', $post->post_status, 'Preview mode should always create draft');
	}

	/**
	 * Test preview mode with 'draft' setting: still draft.
	 */
	public function testPreviewModeWithDraftSettingCreatesDraft(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'draft');

		$article = $this->makeArticle(['id' => 'doc-preview-2']);
		$postId = $this->manager->storeArticle($article, true); // $isDraft = true

		$post = get_post($postId);
		$this->assertSame('draft', $post->post_status);
	}

	/**
	 * Test preview mode with 'author_choice' setting and false metadata: still draft.
	 */
	public function testPreviewModeWithAuthorChoiceFalseMetadataCreatesDraft(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'author_choice');

		$article = $this->makeArticle([
			'id' => 'doc-preview-3',
			'metadata' => ['publish-as-draft' => false],
		]);
		$postId = $this->manager->storeArticle($article, true); // $isDraft = true

		$post = get_post($postId);
		$this->assertSame('draft', $post->post_status, 'Preview mode should override author choice');
	}

	/**
	 * Test that invalid setting values don't break behavior.
	 * Falls back to treating as 'publish' mode.
	 */
	public function testInvalidSettingValueDefaultsToPublish(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'invalid_value');

		$article = $this->makeArticle(['id' => 'doc-invalid-1']);
		$postId = $this->manager->storeArticle($article, false);

		$post = get_post($postId);
		// Should default to publish behavior when invalid value
		$this->assertSame('publish', $post->post_status);
	}

	/**
	 * Test that missing setting (null/empty) defaults to 'publish' mode.
	 */
	public function testMissingSettingDefaultsToPublish(): void
	{
		delete_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY);

		$article = $this->makeArticle(['id' => 'doc-missing-1']);
		$postId = $this->manager->storeArticle($article, false);

		$post = get_post($postId);
		$this->assertSame('publish', $post->post_status);
	}

	/**
	 * Test mode switching: create as published, switch to draft, create new post.
	 * First post should remain published, second should be draft.
	 */
	public function testModeSwitchingBetweenPublishAndDraft(): void
	{
		// Start with publish mode
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'publish');
		$article1 = $this->makeArticle(['id' => 'doc-switch-1']);
		$postId1 = $this->manager->storeArticle($article1, false);
		$this->assertSame('publish', get_post_status($postId1));

		// Switch to draft mode
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'draft');
		$article2 = $this->makeArticle(['id' => 'doc-switch-2']);
		$postId2 = $this->manager->storeArticle($article2, false);
		$this->assertSame('draft', get_post_status($postId2));

		// First post should still be published
		$this->assertSame('publish', get_post_status($postId1));
	}

	/**
	 * Test 'author_choice' mode with various truthy/falsy string values.
	 */
	public function testAuthorChoiceWithVariousStringValues(): void
	{
		update_option(CPUB_PUBLISH_AS_DRAFT_OPTION_KEY, 'author_choice');

		// Test '0' string (falsy) - should publish
		$article1 = $this->makeArticle([
			'id' => 'doc-strings-1',
			'metadata' => ['publish-as-draft' => '0'],
		]);
		$postId1 = $this->manager->storeArticle($article1, false);
		$this->assertSame('publish', get_post_status($postId1));

		// Test 'false' string - should publish (not handled as special case)
		$article2 = $this->makeArticle([
			'id' => 'doc-strings-2',
			'metadata' => ['publish-as-draft' => 'false'],
		]);
		$postId2 = $this->manager->storeArticle($article2, false);
		$this->assertSame('publish', get_post_status($postId2));

		// Test empty string - should publish
		$article3 = $this->makeArticle([
			'id' => 'doc-strings-3',
			'metadata' => ['publish-as-draft' => ''],
		]);
		$postId3 = $this->manager->storeArticle($article3, false);
		$this->assertSame('publish', get_post_status($postId3));
	}
}
