<?php

namespace PccPhpSdk\core\Query\Builder;

use PccPhpSdk\core\Query\QueryInterface;

/**
 * Query Builder Interface.
 *
 * To build Query
 * @see QueryInterface
 */
interface QueryBuilderInterface
{
  /**
   * Add field.
   *
   * @param string $fieldName
   *   Field name.
   *
   * @return QueryBuilderInterface
   *   Returns the QueryBuilderInterface for chaining.
   */
  public function addField(string $fieldName): QueryBuilderInterface;

  /**
   * Add fields.
   *
   * @param string[] $fieldNames
   *   Array of field names.
   *
   * @return QueryBuilderInterface
   *   Returns the QueryBuilderInterface for chaining.
   */
  public function addFields(array $fieldNames): QueryBuilderInterface;

  /**
   * Build Query.
   *
   * @return QueryInterface
   *   Returns Instance of QueryInterface.
   */
  public function build(): QueryInterface;
}
