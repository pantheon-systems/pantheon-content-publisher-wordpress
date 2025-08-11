<?php

use Pantheon\ContentPublisher\PccSyncManager;

class TestArticleStub
{
  public string $id;
  public string $slug;
  public string $title;
  public string $content;
  /** @var array<int,string> */
  public array $tags = [];
  /** @var array<string,mixed> */
  public array $metadata = [];
}


class ArticleOperationsTest extends WP_UnitTestCase
{
  private PccSyncManager $manager;

  public function setUp(): void
  {
    parent::setUp();
    update_option(PCC_INTEGRATION_POST_TYPE_OPTION_KEY, 'post');
    $this->manager = new PccSyncManager();
  }

  private function makeArticle(array $overrides = []): PccPhpSdk\api\Response\Article
  {
    $a = new PccPhpSdk\api\Response\Article();
    $a->id = $overrides['id'] ?? 'doc-1';
    $a->slug = $overrides['slug'] ?? 'hello-world';
    $a->title = $overrides['title'] ?? 'Original Title';
    $a->content = $overrides['content'] ?? '<p>Body</p>';
    $a->tags = $overrides['tags'] ?? ['tag-one', 'tag-two'];
    $a->metadata = $overrides['metadata'] ?? [
      'title' => 'Custom Title',
      'description' => 'Desc',
      'Categories' => 'Cat A,Cat B',
    ];
    return $a;
  }

  public function test_find_existing_connected_post_returns_null_when_absent(): void
  {
    $this->assertNull($this->manager->findExistingConnectedPost('non-existent-id'));
  }

  public function test_find_existing_connected_post_by_meta_and_status(): void
  {
    $publishedId = wp_insert_post([
      'post_status' => 'publish',
      'post_title' => 'Published',
      'post_type' => 'post',
    ]);
    update_post_meta($publishedId, PCC_CONTENT_META_KEY, 'doc-123');

    $draftId = wp_insert_post([
      'post_status' => 'draft',
      'post_title' => 'Draft',
      'post_type' => 'post',
    ]);
    update_post_meta($draftId, PCC_CONTENT_META_KEY, 'doc-123');

    $this->assertSame($publishedId, $this->manager->findExistingConnectedPost('doc-123'));
    $this->assertSame($draftId, $this->manager->findExistingConnectedPost('doc-123', 'draft'));
    $this->assertSame($publishedId, $this->manager->findExistingConnectedPost('doc-123', 'publish'));
  }

  public function test_store_article_creates_post_and_is_retrievable(): void
  {
    $article = $this->makeArticle([
      'id' => 'doc-1',
      'slug' => 'hello-world',
      'metadata' => [
        'title' => 'Custom Title',
        'description' => 'Desc',
        'Categories' => 'Cat A,Cat B',
      ],
    ]);

    $postId = $this->manager->storeArticle($article, false);
    $this->assertIsInt($postId);
    $this->assertGreaterThan(0, $postId);
    $this->assertSame($postId, $this->manager->findExistingConnectedPost('doc-1'));

    $post = get_post($postId);
    $this->assertSame('post', $post->post_type);
    $this->assertSame('publish', $post->post_status);
    $this->assertSame('hello-world', $post->post_name);
    $this->assertSame('Custom Title', $post->post_title);
    $this->assertStringContainsString('<p>Body</p>', $post->post_content);
    $this->assertSame('Desc', $post->post_excerpt);

    $tagNames = wp_get_post_terms($postId, 'post_tag', ['fields' => 'names']);
    $this->assertIsArray($tagNames);
    $this->assertNotEmpty(array_intersect(['tag-one', 'tag-two'], $tagNames));

    $catNames = wp_get_post_terms($postId, 'category', ['fields' => 'names']);
    $this->assertNotEmpty(array_intersect(['Cat A', 'Cat B'], $catNames));
  }

  public function test_store_article_draft_status(): void
  {
    $article = $this->makeArticle(['id' => 'doc-2', 'slug' => 'as-draft']);
    $postId = $this->manager->storeArticle($article, true);
    $post = get_post($postId);
    $this->assertSame('draft', $post->post_status);
  }

  public function test_store_article_updates_existing_post(): void
  {
    $article = $this->makeArticle(['id' => 'doc-3', 'slug' => 'first-slug']);
    $postId1 = $this->manager->storeArticle($article, false);

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
    $this->assertSame($postId1, $postId2);

    $post = get_post($postId2);
    $this->assertSame('updated-slug', $post->post_name);
    $this->assertSame('Updated Title', $post->post_title);
    $this->assertStringContainsString('<p>Updated Body</p>', $post->post_content);
    $this->assertSame('Updated Desc', $post->post_excerpt);

    $tagNames = wp_get_post_terms($postId2, 'post_tag', ['fields' => 'names']);
    $this->assertSame(['tag-three'], array_values($tagNames));

    $catNames = wp_get_post_terms($postId2, 'category', ['fields' => 'names']);
    $this->assertSame(['Cat C'], array_values($catNames));
  }

  public function test_publish_updates_existing_draft_without_duplication(): void
  {
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
    $this->assertSame($draftId, $publishedId);

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
