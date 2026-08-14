<?php

namespace PccPhpSdk\api\Response\Builder;

use PccPhpSdk\api\Response\Article;
use PccPhpSdk\api\Response\PaginatedArticles;
use PccPhpSdk\core\Entity\Article as ArticleEntity;
use PccPhpSdk\core\Entity\ArticlesList;

/**
 * Response builder class.
 * Provides static methods to convert internal Entities to Response objects.
 */
#[\AllowDynamicProperties]
class ResponseBuilder
{
  /**
   * Cast to Article Response.
   *
   * @param \PccPhpSdk\api\Response\ArticleEntity $entity
   *   Corresponding Article Entity.
   *
   * @return \PccPhpSdk\api\Response\Article
   *   Article Response object.
   */
  public static function toArticleResponse(ArticleEntity $entity): Article
  {
    $response = null;
    if (!empty($entity)) {
      $entity_properties = get_object_vars($entity);
      $response = new Article();
      foreach ($entity_properties as $key => $value) {
        $response->$key = $value;
      }
    }

    return $response;
  }

  /**
   * Cast to Paginated Articles List.
   *
   * @param \PccPhpSdk\api\Response\ArticlesList $articlesList
   *   Corresponding ArticlesList Entity.
   *
   * @return \PccPhpSdk\api\Response\PaginatedArticles
   *   PaginatedArticles Response Object.
   */
  public static function toPaginatedArticles(ArticlesList $articlesList): PaginatedArticles
  {
    $response = new PaginatedArticles();
    $response->articles = [];
    if ($articlesList->articles) {
      foreach ($articlesList->articles as $articleEntity) {
        $response->articles[] = self::toArticleResponse($articleEntity);
      }
    }
    $response->total = $articlesList->total;
    $response->cursor = $articlesList->cursor;
    return $response;
  }
}
