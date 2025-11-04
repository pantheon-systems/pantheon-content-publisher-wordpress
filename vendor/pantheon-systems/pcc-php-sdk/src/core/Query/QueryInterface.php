<?php

namespace PccPhpSdk\core\Query;

use PccPhpSdk\Enum\EntityType;

/**
 * Query Interface to build JSON encoded query string for the payload of API request.
 */
interface QueryInterface
{
  public function setVariables(array $variables);

  /**
   * Converts Query to executable string.
   *
   * @return string
   *   JSON encoded query string.
   */
  public function toRequestBody(): string;
}
