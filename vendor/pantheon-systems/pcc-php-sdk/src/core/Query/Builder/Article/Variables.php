<?php

namespace PccPhpSdk\core\Query\Builder\Article;

use GraphQL\Entities\Variable;

/**
 * Class Variables.
 *
 * Defines constants and methods to handle variable definitions for GraphQL queries.
 */
class Variables
{
  public const FILTER = 'filter';
  public const PAGE_SIZE = 'pageSize';
  public const SORT_FIELD = 'sortBy';
  public const SORT_ORDER = 'sortOrder';
  public const PAGE_INDEX = 'cursor';
  public const CONTENT_TYPE = 'contentType';

  public const PUBLISHING_LEVEL = 'publishingLevel';
  public const VERSION_ID = 'versionId';

  /**
   * Get variable definition based on the field name.
   *
   * @param string $fieldName
   *   The field name for which the variable definition is needed.
   * @param string $defaultValue
   *   The default value for field.
   *
   * @return \GraphQL\Entities\Variable|null
   *   Returns the variable definition or null if the field name is invalid.
   */
  public static function getVariableDefinition(string $fieldName, string $defaultValue = ''): ?Variable
  {
    switch ($fieldName) {
      case self::FILTER:
          return new Variable(self::FILTER, 'ArticleFilterInput');

      case self::PAGE_SIZE:
          return new Variable(self::PAGE_SIZE, 'Int');

      case self::SORT_FIELD:
          return new Variable(self::SORT_FIELD, 'ArticleSortField');

      case self::SORT_ORDER:
          return new Variable(self::SORT_ORDER, 'SortOrder');

      case self::PAGE_INDEX:
          return new Variable(self::PAGE_INDEX, 'Float');

      case self::CONTENT_TYPE:
          return new Variable(self::CONTENT_TYPE, 'ContentType', $defaultValue);

      case self::PUBLISHING_LEVEL:
          return new Variable(self::PUBLISHING_LEVEL, 'PublishingLevel');

      case self::VERSION_ID:
          return new Variable(self::VERSION_ID, 'String');

      default:
          return null;
    }
  }
}
