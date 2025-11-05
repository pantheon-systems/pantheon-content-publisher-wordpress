<?php

namespace PccPhpSdk\core\Query\Builder\Article;

use PccPhpSdk\core\Query\Builder\QueryBuilderInterface;

/**
 * Abstract class for Article Entity Query Builder.
 */
abstract class ArticleBaseQueryBuilder implements QueryBuilderInterface
{
  /**
   * Fields to include.
   *
   * @var string[] $fields
   */
  protected array $fields = [];

  /**
   * {@inheritDoc}
   */
  public function addField(string $fieldName): QueryBuilderInterface
  {
    $this->fields[] = $fieldName;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function addFields(array $fieldNames): QueryBuilderInterface
  {
    $this->fields = array_merge($this->fields, $fieldNames);
    return $this;
  }
}
