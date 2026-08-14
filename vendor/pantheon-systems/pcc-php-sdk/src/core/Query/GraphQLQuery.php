<?php

namespace PccPhpSdk\core\Query;

/**
 * GraphQL Query class.
 */
class GraphQLQuery implements QueryInterface
{
  /**
   * Query string.
   *
   * @var string
   */
  private string $query;

  /**
   * Variables Array.
   *
   * @var \ArrayObject
   */
  private \ArrayObject $variables;

  public function __construct(string $query, \ArrayObject $variables = new \ArrayObject())
  {
    $this->query = $query;
    $this->variables = $variables;
  }

  /**
   * {@inheritDoc}
   */
  public function toRequestBody(): string
  {
    $data = [
      'query' => $this->query,
      'variables' => $this->variables,
    ];
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  }

  /**
   * Set query string.
   *
   * @param string $query
   *   Query string.
   *
   * @return void
   *   Returns void.
   */
  public function setQuery(string $query): void
  {
    $this->query = $query;
  }

  /**
   * Set variables array.
   *
   * @param array $variables
   *   Variables Array.
   *
   * @return void
   *   Returns void.
   */
  public function setVariables(array $variables): void
  {
    $this->variables = new \ArrayObject($variables);
  }
}
