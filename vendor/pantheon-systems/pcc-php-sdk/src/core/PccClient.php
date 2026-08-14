<?php

namespace PccPhpSdk\core;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use PccPhpSdk\core\Query\QueryInterface;
use PccPhpSdk\Exception\PccClientException;

/**
 * PccClient Class.
 */
class PccClient
{
  /**
   * PCC Token Header.
   */
  const PCC_TOKEN_HEADER = 'PCC-TOKEN';

  /**
   * Pantheon Client Configuration.
   *
   * @var PccClientConfig
   */
  private PccClientConfig $clientConfig;

  /**
   * @param PccClientConfig $clientConfig
   */
  public function __construct(PccClientConfig $clientConfig)
  {
    $this->clientConfig = $clientConfig;
  }

  /**
   * Execute Query.
   *
   * @param \PccPhpSdk\core\Query\QueryInterface $query
   *   Query Interface object.
   *
   * @return mixed
   *   Response content string.
   *
   * @throws \PccPhpSdk\Exception\PccClientException
   */
  public function executeQuery(QueryInterface $query): mixed
  {
    return $this->sendRequest($query->toRequestBody());
  }

  /**
   * Send Request to PCC API.
   *
   * @param bool|string $body
   *   JSON encoded string for payload.
   *
   * @return mixed
   *   Response content string.
   *
   * @throws \PccPhpSdk\Exception\PccClientException
   */
  public function sendRequest(bool|string $body): mixed
  {
    $client = new Client();
    $headers = $this->getHeaders();
    $request = new Request('POST', $this->getUrl(), $headers, $body);
    try {
      $response = $client->sendAsync($request)->wait();
    } catch (\Exception $e) {
      throw new PccClientException($e->getMessage(), $request, null, $e);
    }
    return $response->getBody()->getContents();
  }

  /**
   * Get Headers for the request.
   *
   * @return array
   *   Array of headers and the corresponding values.
   */
  private function getHeaders(): array
  {
    return [
      'Content-Type' => 'application/json',
      self::PCC_TOKEN_HEADER => $this->clientConfig->getSiteToken(),
    ];
  }

  /**
   * Get PCC Host URL.
   *
   * @return string
   *   PCC API URL string.
   */
  private function getUrl(): string
  {
    return $this->clientConfig->getPccHost() . 'sites/' . $this->clientConfig->getSiteId() . '/query';
  }
}
