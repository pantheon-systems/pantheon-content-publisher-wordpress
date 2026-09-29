<?php

namespace PccPhpSdk\core\Query\Builder\Article;

use GraphQL\Actions\Query;
use GraphQL\Entities\Variable;
use PccPhpSdk\api\Query\Enums\PublishingLevel;
use PccPhpSdk\api\Query\Enums\ContentType;
use PccPhpSdk\core\Entity\Loader\ArticleLoaderInterface;
use PccPhpSdk\core\Query\Builder\QueryBuilderInterface;
use PccPhpSdk\core\Query\GraphQLQuery;
use PccPhpSdk\core\Query\QueryInterface;

/**
 * Article Query Builder to get single Article.
 */
class ArticleQueryBuilder extends ArticleBaseQueryBuilder
{
  /**
   * Article ID to filter.
   *
   * @var string|null
   */
  private ?string $articleId = null;

  /**
   * Article Slug to filter.
   *
   * @var string|null
   */
  private ?string $articleSlug = null;

  /**
   * Publishing Level.
   *
   * @var PublishingLevel
   */
  private PublishingLevel $publishing_level = PublishingLevel::PRODUCTION;

  /**
   * Content Type.
   *
   * @var ContentType
   */
  private ContentType $content_type = ContentType::TEXT_MARKDOWN;

  /**
   * Version ID.
   *
   * @var string|null
   */
  private ?string $version_id = null;

  /**
   * Add ID for filter in the query.
   *
   * @param string $articleId
   *   ID value.
   *
   * @return \PccPhpSdk\core\Query\Builder\QueryBuilderInterface
   *   Returns self.
   */
  public function filterById(string $articleId): QueryBuilderInterface
  {
    $this->articleId = $articleId;
    return $this;
  }

  /**
   * Add Slug for filter in the query.
   *
   * If ID filter already present, that will take precedence.
   *
   * @param string $articleSlug
   *   Slug Value.
   *
   * @return \PccPhpSdk\core\Query\Builder\QueryBuilderInterface
   *   Returns self.
   */
  public function filterBySlug(string $articleSlug): QueryBuilderInterface
  {
    $this->articleSlug = $articleSlug;
    return $this;
  }

  /**
   * Set publishing level.
   *
   * @param ?PublishingLevel $publishing_level
   *   Publishing Level.
   *
   * @return QueryBuilderInterface
   *   Return self.
   */
  public function setPublishingLevel(?PublishingLevel $publishing_level): QueryBuilderInterface
  {
    $this->publishing_level = empty($publishing_level) ? PublishingLevel::PRODUCTION : $publishing_level;
    return $this;
  }

  /**
   * Set content type.
   *
   * @param ?ContentType $content_type
   *   Content type.
   *
   * @return QueryBuilderInterface
   *   Return self.
   */
  public function setContentType(?ContentType $content_type): QueryBuilderInterface
  {
    $this->content_type = empty($content_type) ? ContentType::TEXT_MARKDOWN : $content_type;
    return $this;
  }

  /**
   * Set version ID.
   *
   * @param ?string $version_id
   *   Version ID.
   *
   * @return QueryBuilderInterface
   *   Return self.
   */
  public function setVersionId(?string $version_id): QueryBuilderInterface
  {
    $this->version_id = $version_id;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function build(): QueryInterface
  {
    $query = new Query('article', $this->getQueryArgs());
    $query->use(...$this->fields);
    $parsedQuery = $query->root()->parse();
    $variables = $this->getVariableVal();
    return new GraphQLQuery($parsedQuery, $variables);
  }

  /**
   * Get Query Args.
   *
   * @return array
   *   Returns Query Args.
   */
  private function getQueryArgs(): array
  {
    $content_type = $this->content_type->value;
    $args = [
      Variables::CONTENT_TYPE => Variables::getVariableDefinition(Variables::CONTENT_TYPE, $content_type),
    ];

    $variable = $this->getVariableDef();
    if (!empty($variable)) {
      $args = array_merge($args, $variable);
    }
    return $args;
  }

  /**
   * Get Variable value.
   *
   * @return \ArrayObject
   *   Returns Variable mapped values.
   */
  private function getVariableVal(): \ArrayObject
  {
    $value = [];
    if ($this->articleId !== null) {
      $value[ArticleLoaderInterface::ID] = $this->articleId;
    } elseif ($this->articleSlug !== null) {
      $value[ArticleLoaderInterface::SLUG] = $this->articleSlug;
    }
    $value[Variables::PUBLISHING_LEVEL] = $this->publishing_level;
    if ($this->version_id !== null) {
      $value[Variables::VERSION_ID] = $this->version_id;
    }

    return new \ArrayObject($value);
  }

  /**
   * Get Variable Definition for GraphQL\Actions\Query.
   *
   * @return \GraphQL\Entities\Variable[]|null
   *   Return ID or Slug variable definition or null.
   */
  private function getVariableDef(): ?array
  {
    $variable = null;
    if ($this->articleId !== null) {
      $variable = [ArticleLoaderInterface::ID => $this->buildIdVariableDef()];
    } elseif ($this->articleSlug !== null) {
      $variable = [ArticleLoaderInterface::SLUG => $this->buildSlugVariableDef()];
    }
    $variable[Variables::PUBLISHING_LEVEL] = $this->buildPublishingLevelVariableDef();
    if ($this->version_id !== null) {
      $variable[Variables::VERSION_ID] = Variables::getVariableDefinition(Variables::VERSION_ID);
    }

    return $variable;
  }

  /**
   * Build ID Variable Definition for the GraphQL\Actions\Query.
   *
   * @return \GraphQL\Entities\Variable
   *   Return Variable Definition.
   */
  private function buildIdVariableDef(): Variable
  {
    return new Variable(ArticleLoaderInterface::ID, 'String');
  }

  /**
   * Build ID Variable Definition for the GraphQL\Actions\Query.
   *
   * @return \GraphQL\Entities\Variable
   *   Return Variable Definition.
   */
  private function buildSlugVariableDef(): Variable
  {
    return new Variable(ArticleLoaderInterface::SLUG, 'String');
  }

  private function buildPublishingLevelVariableDef(): Variable
  {
    return Variables::getVariableDefinition(Variables::PUBLISHING_LEVEL);
  }
}
