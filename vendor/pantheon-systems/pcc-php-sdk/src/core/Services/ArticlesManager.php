<?php

namespace PccPhpSdk\core\Services;

use PccPhpSdk\api\Query\Enums\ContentType;
use PccPhpSdk\api\Query\Enums\PublishingLevel;
use PccPhpSdk\api\Query\ArticleQueryArgs;
use PccPhpSdk\api\Query\ArticleSearchArgs;
use PccPhpSdk\core\Entity\Article;
use PccPhpSdk\core\Entity\ArticlesList;
use PccPhpSdk\core\Entity\Loader\ArticleLoader;
use PccPhpSdk\core\Entity\Loader\ArticleLoaderInterface;
use PccPhpSdk\core\PccClient;

/**
 * Articles Manager service.
 */
class ArticlesManager
{
  /**
   * Article Loader.
   *
   * @var \PccPhpSdk\core\Entity\ArticleLoaderInterface
   */
  protected ArticleLoaderInterface $articleLoader;

  /**
   * Constructor for Content API.
   *
   * @param \PccPhpSdk\core\PccClient $pccClient
   *   Preconfigured PccClient.
   */
  public function __construct(PccClient $pccClient)
  {
    $this->articleLoader = new ArticleLoader($pccClient);
  }

  /**
   * Get Articles based on Query & Search Arguments.
   *
   * @param array $fields
   *   The Article fields.
   * @param \PccPhpSdk\api\Query\ArticleSearchArgs|null $args
   *   Search Arguments.
   *
   * @return \PccPhpSdk\core\Entity\ArticlesList
   *   ArticlesList containing matching articles.
   */
  public function getArticles(
      ?ArticleQueryArgs $queryArgs = null,
      ?ArticleSearchArgs $searchArgs = null,
      array $fields = []
  ): ArticlesList {
    return $this->articleLoader->loadAll($queryArgs, $searchArgs, $fields);
  }

  /**
   * Get Article by ID.
   *
   * @param string $articleId
   *   Article ID.
   * @param array $fields
   *   The Article fields.
   * @param PublishingLevel $publishingLevel
   *    The publishing Level.
   * @param ContentType|null $contentType
   *   The content type.
   * @param string|null $versionId
   *   The version ID.
   *
   * @return \PccPhpSdk\core\Entity\Article|null
   *   Article Entity.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function getArticleById(
      string $articleId,
      array $fields = [],
      PublishingLevel $publishingLevel = PublishingLevel::PRODUCTION,
      ?ContentType $contentType = null,
      ?string $versionId = null
  ): ?Article {
    return $this->articleLoader->loadById(...func_get_args());
  }

  /**
   * Get Article by Slug.
   *
   * @param string $articleSlug
   *   Article slug.
   * @param array $fields
   *   The Article fields.
   * @param PublishingLevel $publishingLevel
   *    The publishing Level.
   * @param string|null $versionId
   *   The version ID.
   *
   * @return \PccPhpSdk\core\Entity\Article|null
   *   Article Entity.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function getArticleBySlug(
      string $articleSlug,
      array $fields = [],
      PublishingLevel $publishingLevel = PublishingLevel::PRODUCTION,
      ?string $versionId = null
  ): ?Article {
    return $this->articleLoader->loadBySlug(...func_get_args());
  }
}
