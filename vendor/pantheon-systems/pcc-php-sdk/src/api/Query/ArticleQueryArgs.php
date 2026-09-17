<?php

namespace PccPhpSdk\api\Query;

use PccPhpSdk\api\Query\Enums\ArticleSortField;
use PccPhpSdk\api\Query\Enums\ArticleSortOrder;
use PccPhpSdk\api\Query\Enums\ContentType;

/**
 * Class ArticleQueryArgs
 *
 * This class is used to define the arguments for querying articles.
 *
 * @package PccPhpSdk\api\Query
 */
class ArticleQueryArgs
{
  /**
   * The type of content to query.
   *
   * @var ContentType $contentType
   */
  public ContentType $contentType;

  /**
   * The field by which to sort the articles.
   *
   * @var ArticleSortField $sortField
   */
  public ArticleSortField $sortField;

  /**
   * The order in which to sort the articles.
   *
   * @var ArticleSortOrder $sortOrder
   */
  public ArticleSortOrder $sortOrder;

  /**
   * The number of articles per page.
   *
   * @var int $pageSize
   */
  public int $pageSize;

  /**
   *  The index of the page to retrieve.
   *
   * @var int $pageIndex
   */
  public int $pageIndex;

  /**
   * ArticleQueryArgs constructor.
   *
   * @param ArticleSortField $sortField
   *   The field by which to sort the articles.
   * @param ArticleSortOrder $sortOrder
   *   The order in which to sort the articles.
   * @param int $pageSize
   *   The number of articles per page.
   * @param int $pageIndex
   *   The index of the page to retrieve.
   * @param ContentType $contentType
   *   The type of content to query.
   */
  public function __construct(
      ArticleSortField $sortField = ArticleSortField::UPDATED_AT,
      ArticleSortOrder $sortOrder = ArticleSortOrder::DESC,
      int $pageSize = 10,
      int $pageIndex = 1,
      ContentType $contentType = ContentType::TEXT_MARKDOWN,
  ) {
    $this->contentType = $contentType;
    $this->sortField = $sortField;
    $this->sortOrder = $sortOrder;
    $this->pageSize = $pageSize;
    $this->pageIndex = $pageIndex;
  }

  /**
   * Sets the content type.
   *
   * @param ContentType $contentType
   *   The type of content to query.
   */
  public function setContentType(ContentType $contentType): void
  {
    $this->contentType = $contentType;
  }

  /**
   * Sets the sort field.
   *
   * @param ArticleSortField $sortField
   *   The field by which to sort the articles.
   */
  public function setSortField(ArticleSortField $sortField): void
  {
    $this->sortField = $sortField;
  }

  /**
   * Sets the sort order.
   *
   * @param ArticleSortOrder $sortOrder
   *   The order in which to sort the articles.
   */
  public function setSortOrder(ArticleSortOrder $sortOrder): void
  {
    $this->sortOrder = $sortOrder;
  }

  /**
   * Sets the page size.
   *
   * @param int $pageSize
   *   The number of articles per page.
   */
  public function setPageSize(int $pageSize): void
  {
    $this->pageSize = $pageSize;
  }

  /**
   * Sets the page index.
   *
   * @param int $pageIndex
   *   The index of the page to retrieve.
   */
  public function setPageIndex(int $pageIndex): void
  {
    $this->pageIndex = $pageIndex;
  }

  /**
   * Gets the content type.
   *
   * @return ContentType
   *   The type of content to query.
   */
  public function getContentType(): ContentType
  {
    return $this->contentType;
  }

  /**
   * Gets the sort field.
   *
   * @return ArticleSortField
   *   The field by which to sort the articles.
   */
  public function getSortField(): ArticleSortField
  {
    return $this->sortField;
  }

  /**
   * Gets the sort order.
   *
   * @return ArticleSortOrder
   *   The order in which to sort the articles.
   */
  public function getSortOrder(): ArticleSortOrder
  {
    return $this->sortOrder;
  }

  /**
   * Gets the page size.
   *
   * @return int
   *   The number of articles per page.
   */
  public function getPageSize(): int
  {
    return $this->pageSize;
  }

  /**
   * Gets the page index.
   *
   * @return int
   *   The index of the page to retrieve.
   */
  public function getPageIndex(): int
  {
    return $this->pageIndex;
  }
}
