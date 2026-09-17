<?php

namespace PccPhpSdk\core\Entity\Loader;

use PccPhpSdk\api\Query\Enums\ContentType;
use PccPhpSdk\api\Query\Enums\PublishingLevel;
use PccPhpSdk\api\Query\ArticleQueryArgs;
use PccPhpSdk\api\Query\ArticleSearchArgs;
use PccPhpSdk\core\Entity\Article;
use PccPhpSdk\core\Entity\ArticlesList;

/**
 * Article Loader Interface.
 */
interface ArticleLoaderInterface
{
    /**
     * ID identifier.
     */
  public const ID = 'id';

    /**
     * Slug identifier.
     */
  public const SLUG = 'slug';


    /**
     * Load Article by ID.
     *
     * @param string $articleId
     *   Article id.
     * @param array $fields
     *   The Article fields.
     * @param PublishingLevel $publishingLevel
     *   The publishing Level.
     * @param ContentType|null $contentType
     *   The content type.
     * @param string|null $versionId
     *   The version ID.
     *
     * @return \PccPhpSdk\core\Entity\Article|null
     *   Article Entity or null.
     */
  public function loadById(
      string $articleId,
      array $fields = [],
      PublishingLevel $publishingLevel = PublishingLevel::PRODUCTION,
      ?ContentType $contentType = null,
      ?string $versionId = null
  ): ?Article;


    /**
     * Load Article by slug.
     *
     * @param string $slug
     *   Article slug.
     * @param array $fields
     *   The Article fields.
     * @param PublishingLevel $publishingLevel
     *     The publishing Level.
     * @param string|null $versionId
     *   The version ID.
     *
     * @return \PccPhpSdk\core\Entity\Article|null
     *   Article or null.
     */
  public function loadBySlug(
      string $slug,
      array $fields = [],
      PublishingLevel $publishingLevel = PublishingLevel::PRODUCTION,
      ?string $versionId = null
  ): ?Article;

    /**
     * Load All Articles based on Query and Search args.
     *
     * @param \PccPhpSdk\api\Query\ArticleQueryArgs|null $queryArgs
     *   Article Query Args.
     * @param \PccPhpSdk\api\Query\ArticleSearchArgs|null $searchArgs
     *   Article Search Args.
     * @param array $fields
     *   The Article fields.
     *
     * @return \PccPhpSdk\core\Entity\ArticlesList
     *   ArticlesList containing all articles matching the criterion.
     */
  public function loadAll(
      ?ArticleQueryArgs $queryArgs,
      ?ArticleSearchArgs $searchArgs,
      array $fields = []
  ): ArticlesList;
}
