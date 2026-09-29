<?php

namespace PccPhpSdk\api;

use PccPhpSdk\core\Query\GraphQLQuery;

/**
 * Site API to get site.
 */
class SitesApi extends PccApi
{
  /**
   * Get site.
   *
   * @param string $siteId
   *   Site ID string.
   *
   * @return mixed
   *   Returns site information as JSON.
   *
   * @throws \PccPhpSdk\Exception\PccClientException
   */
  public function getSite(string $siteId): mixed
  {
    // @todo Create SitesManager to get the site, similar to ArticlesManager.
    // Once we do this, we can Remove PccApi base class.
    $query = <<<'GRAPHQL'
    query GetSite($siteId: String!) {
      site(id: $siteId) {
        id
        url,
        metadataFields
      }
    }
    GRAPHQL;
    $variables = new \ArrayObject(['siteId' => $siteId]);

    $graphQLQuery = new GraphQLQuery($query, $variables);
    return $this->pccClient->executeQuery($graphQLQuery);
  }
}
