<?php

namespace PccPhpSdk\api\Response;

/**
 * Class PaginatedArticles.
 *
 * Response Object containing articles and result summary.
 */
class PaginatedArticles
{
  /**
   * Array of articles.
   *
   * @var Article[]
   */
  public array $articles;

  /**
   * Total number of articles.
   *
   * @var int
   */
  public int $total;

  /**
   * Articles count per page.
   *
   * @var int
   */
  public int $perPageCount;

  /**
   * Current page number, starting from 1.
   *
   * @var int
   */
  public int $currentPage;

  /**
   * Current page cursor.
   *
   * @var int
   */
  public int $cursor;

  /**
   * Offset.
   *
   * @var int
   */
  public int $offset;

  /**
   * Limit.
   *
   * @var int
   */
  public int $limit;
}
