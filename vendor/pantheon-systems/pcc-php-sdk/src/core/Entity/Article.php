<?php

namespace PccPhpSdk\core\Entity;

/**
 * Article Entity.
 *
 * Entity object containing Article data.
 */
#[\AllowDynamicProperties]
class Article
{
  /**
   * Article ID.
   *
   * @var string $id
   */
  public string $id;

  /**
   * Article Slug.
   *
   * @var string $slug
   */
  public string $slug;

  /**
   * Article Title.
   *
   * @var string $title
   */
  public string $title;

  /**
   * Site ID.
   *
   * @var string $siteId
   */
  public string $siteId;

  /**
   * Article Content.
   *
   * @var string $content
   */
  public string $content;

  /**
   * Article snippet.
   *
   * @var string $snippet
   */
  public string $snippet;

  /**
   * Article Tags.
   *
   * @var array $tags
   */
  public array $tags = [];

  /**
   * Published Date.
   *
   * @var string $publishedDate
   */
  public string $publishedDate;

  /**
   * Updated Date.
   *
   * @var string $updatedAt
   */
  public string $updatedAt;

  /**
   * Publishing Level.
   *
   * @var string $publishingLevel
   */
  public string $publishingLevel;
}
