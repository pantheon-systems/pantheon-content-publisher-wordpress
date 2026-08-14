<?php

namespace PccPhpSdk\api;

use PccPhpSdk\api\Query\Enums\ContentType;
use PccPhpSdk\api\Query\Enums\PublishingLevel;
use PccPhpSdk\api\Query\ArticleQueryArgs;
use PccPhpSdk\api\Query\ArticleSearchArgs;
use PccPhpSdk\api\Response\Article;
use PccPhpSdk\api\Response\Builder\ResponseBuilder;
use PccPhpSdk\api\Response\PaginatedArticles;
use PccPhpSdk\core\PccClient;
use PccPhpSdk\core\Services\ArticlesManager;

/**
 * Articles API to fetch articles.
 */
class ArticlesApi extends PccApi
{
  /**
   * Internal Articles Manager Service.
   *
   * @var \PccPhpSdk\api\Response\ArticlesManager
   */
  private ArticlesManager $articlesManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(PccClient $pccClient)
  {
    parent::__construct($pccClient);
    $this->articlesManager = new ArticlesManager($pccClient);
  }

  /**
   * Get All Articles.
   *
   * @param \PccPhpSdk\api\Query\ArticleQueryArgs $queryArgs
   *   Query Args.
   * @param \PccPhpSdk\api\Query\ArticleSearchArgs $searchArgs
   *   Search Criterion.
   * @param array $fields
   *   The Article fields.
   *
   * @return \PccPhpSdk\api\Response\PaginatedArticles
   *   Returns articles list matching the search criterion as PaginatedArticles.
   */
  public function getAllArticles(
      ?ArticleQueryArgs $queryArgs = null,
      ?ArticleSearchArgs $searchArgs = null,
      array $fields = []
  ): PaginatedArticles {
    $articles = $this->articlesManager->getArticles($queryArgs, $searchArgs, $fields);
    return ResponseBuilder::toPaginatedArticles($articles);
  }

  /**
   * Get article by ID.
   *
   * @param string $articleId
   *   The article id.
   * @param array $fields
   *   The Article fields.
   * @param PublishingLevel $publishingLevel
   *   The publishing level.
   * @param ContentType|null $contentType
   *   The content type.
   * @param string|null $versionId
   *   The version ID.
   *
   * @return \PccPhpSdk\api\Response\Article|null
   *   Return Article response object.
   *
   * @throws \PccPhpSdk\Exception\PccClientException
   */
  public function getArticleById(
      string $articleId,
      array $fields = [],
      PublishingLevel $publishingLevel = PublishingLevel::PRODUCTION,
      ?ContentType $contentType = null,
      ?string $versionId = null
  ): ?Article {
    $article = $this->articlesManager->getArticleById($articleId, $fields, $publishingLevel, $contentType, $versionId);
    return $article ? ResponseBuilder::toArticleResponse($article) : null;
  }

  /**
   * Get article by slug.
   *
   * @param string $slug
   *   The article slug.
   * @param array $fields
   *   The Article fields.
   * @param PublishingLevel $publishingLevel
   *   The publishing level.
   * @param string|null $versionId
   *   The version ID.
   *
   * @return \PccPhpSdk\api\Response\Article|null
   *   Return Article response object.
   *
   * @throws \PccPhpSdk\Exception\PccClientException
   */
  public function getArticleBySlug(
      string $slug,
      array $fields = [],
      PublishingLevel $publishingLevel = PublishingLevel::PRODUCTION,
      ?string $versionId = null
  ): ?Article {
    $article = $this->articlesManager->getArticleBySlug($slug, $fields, $publishingLevel, $versionId);
    return $article ? ResponseBuilder::toArticleResponse($article) : null;
  }
}
