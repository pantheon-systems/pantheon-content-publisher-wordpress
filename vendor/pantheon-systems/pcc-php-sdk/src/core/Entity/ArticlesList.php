<?php

namespace PccPhpSdk\core\Entity;

/**
 * ArticlesList Entity.
 *
 * Entity object containing Articles.
 */
class ArticlesList
{
  /**
   * Total number of articles.
   *
   * @var int
   */
  public int $total;

  /**
   * The page cursor.
   *
   * @var int
   */
  public int $cursor;

  /**
   * Articles List as array.
   *
   * @var array
   */
  public array $articles = [];

  /**
   * Construct ArticlesList entity.
   *
   * @param Article[] $articles
   *   Array of Articles.
   * @param int $total
   *   The total articles.
   * @param int $cursor
   *   The current cursor.
   */
  public function __construct(array $articles = [], $total = 20, int $cursor = null)
  {
    $this->articles = $articles;
    $this->total = $total;
    if ($cursor === null) {
      // Get the current Unix timestamp with microseconds.
      $microtime = microtime(true);
      // Convert to milliseconds.
      $this->cursor = round($microtime * 1000);
    }
  }

  /**
   * Add total count of articles.
   *
   * @param int $total
   *   The total cont.
   *
   * @return void
   *   Nothing.
   */
  public function addTotalArticlesCount(int $total): void
  {
    $this->total = $total;
  }

  /**
   * Add page cursor.
   *
   * @param int $cursor
   *   The cursor.
   *
   * @return void
   *   Nothing.
   */
  public function addPageCursor(int $cursor): void
  {
    $this->cursor = $cursor;
  }

  /**
   * Add an Article.
   *
   * @param Article $article
   *   Article Entity.
   *
   * @return void
   *   Nothing.
   */
  public function addArticle(Article $article): void
  {
    $this->articles[] = $article;
  }
}
