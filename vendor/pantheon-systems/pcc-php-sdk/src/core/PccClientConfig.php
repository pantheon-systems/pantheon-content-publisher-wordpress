<?php

namespace PccPhpSdk\core;

/**
 * Pcc Client Configurations.
 */
class PccClientConfig
{
  /**
   * Default PCC Host URL.
   */
  const PCC_HOST_DEFAULT = 'https://gql.prod.pcc.pantheon.io/';

  /**
   * PCC Host
   *
   * @var string
   */
  private string $pccHost;

  /**
   * The site ID.
   *
   * @var string
   */
  private string $siteId;

  /**
   * The site token.
   *
   * @var string
   */
  private string $siteToken;

  /**
   * PCC Grant.
   *
   * @var string|null
   */
  private ?string $pccGrant;

  /**
   * Constructor
   *
   * @param string $siteId
   *   Site ID.
   * @param string $siteToken
   *   Site Token.
   * @param string|null $pccHost
   *   Pcc Host.
   * @param string|null $pccGrant
   *   Pcc Grant.
   */
  public function __construct(string $siteId, string $siteToken, string $pccHost = null, string $pccGrant = null)
  {
    $this->siteId = $siteId;
    $this->siteToken = $siteToken;
    $this->pccHost = $pccHost ?? self::PCC_HOST_DEFAULT;
    $this->pccGrant = $pccGrant;
  }

  /**
   * Get Pcc Host
   *
   * @return string
   *   PCC Host URL string.
   */
  public function getPccHost(): string
  {
    return $this->pccHost;
  }

  /**
   * Get Site ID.
   *
   * @return string
   *   PCC Site ID.
   */
  public function getSiteId(): string
  {
    return $this->siteId;
  }

  /**
   * Get Site Token.
   *
   * @return string
   *   PCC Site Token
   */
  public function getSiteToken(): string
  {
    return !empty($this->siteToken) ? $this->siteToken : $this->getPCCGrant();
  }

  /**
   * Get PCC Grant string.
   *
   * @return string
   *   PCC grant string.
   */
  private function getPCCGrant(): string
  {
    $pccGrant = $this->pccGrant;
    if (!empty($pccGrant) && !str_contains('pcc_grant', $pccGrant)) {
      $pccGrant = "pcc_grant $pccGrant";
    }

    return $pccGrant ?? '';
  }
}
