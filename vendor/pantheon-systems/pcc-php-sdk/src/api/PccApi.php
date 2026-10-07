<?php

namespace PccPhpSdk\api;

use PccPhpSdk\core\PccClient;

/**
 * PccAPI class providing underlying required functionalities for APIs.
 */
abstract class PccApi
{
  /**
   *  PccClient.
   *
   * @var PccClient
   */
  protected PccClient $pccClient;

  /**
   * Constructor for Content API.
   *
   * @param PccClient $pccClient
   *   Preconfigured PccClient
   */
  public function __construct(PccClient $pccClient)
  {
    $this->pccClient = $pccClient;
  }
}
