<?php

namespace PccPhpSdk\core\Query\Builder\Article;

use GraphQL\Actions\Query;
use GraphQL\Entities\Variable;
use ArrayObject;
use PccPhpSdk\api\Query\ArticleQueryArgs;
use PccPhpSdk\api\Query\ArticleSearchArgs;
use PccPhpSdk\api\Query\Enums\ArticleSortField;
use PccPhpSdk\api\Query\Enums\ArticleSortOrder;
use PccPhpSdk\api\Query\Enums\ContentType;
use PccPhpSdk\core\Query\GraphQLQuery;
use PccPhpSdk\core\Query\QueryInterface;

/**
 * Articles List Query Builder to get multiple articles.
 */
class ArticlesListQueryBuilder extends ArticleBaseQueryBuilder
{
  /**
   * Filter inputs for querying articles.
   *
   * @var ArticleFilterInput|null
   */
  private ?ArticleFilterInput $filter = null;

  /**
   * Number of articles to fetch per page.
   *
   * @var int|null
   */
  private ?int $pageSize = 20;

  /**
   * Field to sort the articles by.
   *
   * @var ArticleSortField|null
   */
  private ?ArticleSortField $sortField = null;

  /**
   * Order in which to sort the articles.
   *
   * @var ArticleSortOrder|null
   */
  private ?ArticleSortOrder $sortOrder = null;

  /**
   * Index of the page to fetch.
   *
   * @var float|null
   */
  private ?float $pageIndex = null;

  /**
   * Type of content to query.
   *
   * @var ContentType
   */
  private ContentType $contentType = ContentType::TEXT_MARKDOWN;

  /**
   * Build the GraphQL query.
   *
   * {@inheritDoc}
   *
   * @return QueryInterface
   *   The built query.
   */
  public function build(): QueryInterface
  {
    $query = new Query('articles', $this->getQueryArgs());
    $query->use(...$this->fields);
    $parsedQuery = $query->root()->parse();
    $variables = $this->getVariableVal();
    return new GraphQLQuery($parsedQuery, $variables);
  }

  /**
   * Set filter inputs using ArticleSearchArgs.
   *
   * @param ArticleSearchArgs $searchArgs
   *   Article search arguments.
   *
   * @return $this
   *   Returns self for method chaining.
   */
  public function setFilter(ArticleSearchArgs $searchArgs): ArticlesListQueryBuilder
  {
    $this->filter = new ArticleFilterInput(
        $searchArgs->bodyContains,
        $searchArgs->tagContains,
        $searchArgs->titleContains,
        $searchArgs->publishStatus
    );
    return $this;
  }

  /**
   * Based on Query Args set properties.
   *
   * @param ArticleQueryArgs $queryArgs
   *   Article Query Args.
   *
   * @return $this
   *   Returns self.
   */
  public function setQueryArgs(ArticleQueryArgs $queryArgs): ArticlesListQueryBuilder
  {
    $this->contentType = $queryArgs->getContentType();
    $this->sortField = $queryArgs->getSortField();
    $this->sortOrder = $queryArgs->getSortOrder();
    $this->pageSize = $queryArgs->getPageSize();
    $this->pageIndex = $queryArgs->getPageIndex();
    return $this;
  }

  /**
   * Set the field to sort the articles by.
   *
   * @param ArticleSortField $sortField
   *   The field to sort by.
   *
   * @return $this
   *   Returns self for method chaining.
   */
  public function setSortField(ArticleSortField $sortField): ArticlesListQueryBuilder
  {
    $this->sortField = $sortField;
    return $this;
  }

  /**
   * Set the order in which to sort the articles.
   *
   * @param ArticleSortOrder $sortOrder
   *   The order to sort by.
   *
   * @return $this
   *   Returns self for method chaining.
   */
  public function setSortOrder(ArticleSortOrder $sortOrder): ArticlesListQueryBuilder
  {
    $this->sortOrder = $sortOrder;
    return $this;
  }

  /**
   * Set the number of articles to fetch per page.
   *
   * @param int $pageSize
   *   The number of articles per page.
   *
   * @return $this
   *   Returns self for method chaining.
   */
  public function setPageSize(int $pageSize): ArticlesListQueryBuilder
  {
    $this->pageSize = $pageSize;
    return $this;
  }

  /**
   * Set the index of the page to fetch.
   *
   * @param int $pageIndex
   *   The page index.
   *
   * @return $this
   *   Returns self for method chaining.
   */
  public function setPageIndex(int $pageIndex): ArticlesListQueryBuilder
  {
    $this->pageIndex = $pageIndex;
    return $this;
  }

  /**
   * Set the type of content to query.
   *
   * @param ContentType $contentType
   *   The content type.
   *
   * @return $this
   *   Returns self for method chaining.
   */
  public function setContentType(ContentType $contentType): ArticlesListQueryBuilder
  {
    $this->contentType = $contentType;
    return $this;
  }

  /**
   * Return article filter input.
   *
   * @return ArticleFilterInput|null
   *   Filter inputs for querying articles.
   */
  public function getFilter(): ?ArticleFilterInput
  {
    return $this->filter;
  }

  /**
   * Get the field to sort the articles by.
   *
   * @return ArticleSortField|null
   *   The sort field.
   */
  public function getSortField(): ?ArticleSortField
  {
    return $this->sortField;
  }

  /**
   * Get the order in which to sort the articles.
   *
   * @return ArticleSortOrder|null
   *   The sort order.
   */
  public function getSortOrder(): ?ArticleSortOrder
  {
    return $this->sortOrder;
  }

  /**
   * Get the number of articles to fetch per page.
   *
   * @return int|null
   *   The number of articles per page.
   */
  public function getPageSize(): ?int
  {
    return $this->pageSize;
  }

  /**
   * Get the index of the page to fetch.
   *
   * @return int|null
   *   The page index.
   */
  public function getPageIndex(): ?int
  {
    return $this->pageIndex;
  }

  /**
   * Get the type of content to query.
   *
   * @return ContentType
   *   The content type.
   */
  public function getContentType(): ContentType
  {
    return $this->contentType;
  }

  /**
   * Get query arguments.
   *
   * @return array
   *   Returns query arguments.
   */
  private function getQueryArgs(): array
  {
    $args = [
      'contentType' => $this->contentType,
    ];
    $variable = $this->getVariableDef();
    if (!empty($variable)) {
      $args = array_merge($args, $variable);
    }

    return $args;
  }

  /**
   * Get variable definitions for GraphQL\Actions\Query.
   *
   * @return Variable[]|null
   *   Return filter variable definitions or null.
   */
  private function getVariableDef(): ?array
  {
    $variables = [];

    if ($this->filter !== null) {
      $variables[Variables::FILTER] = Variables::getVariableDefinition(Variables::FILTER);
    }
    if ($this->sortField !== null) {
      $variables[Variables::SORT_FIELD] = Variables::getVariableDefinition(Variables::SORT_FIELD);
    }
    if ($this->sortOrder !== null) {
      $variables[Variables::SORT_ORDER] = Variables::getVariableDefinition(Variables::SORT_ORDER);
    }
    if ($this->pageIndex !== null) {
      $variables[Variables::PAGE_INDEX] = Variables::getVariableDefinition(Variables::PAGE_INDEX);
    }
    if ($this->contentType !== null) {
      $variables[Variables::CONTENT_TYPE] = Variables::getVariableDefinition(Variables::CONTENT_TYPE);
    }
    if ($this->pageSize !== null) {
      $variables[Variables::PAGE_SIZE] = Variables::getVariableDefinition(Variables::PAGE_SIZE);
    }

    return $variables;
  }

  /**
   * Get variable values.
   *
   * @return ArrayObject
   *   Returns variable mapped values.
   */
  private function getVariableVal(): ArrayObject
  {
    $value = [];
    if ($this->filter !== null) {
      $value[Variables::FILTER] = [
        'body' => $this->filter->bodyContains ? ['contains' => $this->filter->bodyContains] : null,
        'title' => $this->filter->titleContains ? ['contains' => $this->filter->titleContains] : null,
        'tag' => $this->filter->tagContains ? ['contains' => $this->filter->tagContains] : null,
        'publishStatus' => $this->filter->publishStatus ?? null,
      ];
    }
    if ($this->sortField !== null) {
      $value[Variables::SORT_FIELD] = $this->sortField;
    }
    if ($this->sortOrder !== null) {
      $value[Variables::SORT_ORDER] = $this->sortOrder;
    }
    if ($this->pageIndex !== null) {
      $value[Variables::PAGE_INDEX] = $this->pageIndex;
    }
    if ($this->contentType !== null) {
      $value[Variables::CONTENT_TYPE] = $this->contentType;
    }
    if ($this->pageSize !== null) {
      $value[Variables::PAGE_SIZE] = $this->pageSize;
    }

    return new ArrayObject($value);
  }
}
