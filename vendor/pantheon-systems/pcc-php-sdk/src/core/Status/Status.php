<?php

namespace PccPhpSdk\core\Status;

use PccPhpSdk\core\PccClientConfig;

const VERSION = '1.1.1';

class Status
{
  private array $status;

  public function __construct(PccClientConfig $config, StatusOptions $options)
  {
    $this->status = [
      'timestamp' => gmdate('c'),
      'level' => StatusLevel::BASIC->value,
      'version' => VERSION,
      'siteId' => $config->getSiteId(),
      'smartComponents' => (bool) $options->smartComponents,
      'smartComponentsCount' => $options->smartComponentsCount,
      'smartComponentPreview' => (bool) $options->smartComponentPreview,
      'metadataGroups' => (bool) $options->metadataGroups,
      'metadataGroupIdentifiers' => $options->metadataGroupIdentifiers,
      'resolvePathConfigured' => (bool) $options->resolvePathConfigured,
      'notFoundPath' => $options->notFoundPath,
    ];
  }

  public function withPlatform(array $platform): self
  {
    $clone = clone $this;
    $clone->status['platform'] = $platform;
    return $clone;
  }

  public function toArray(): array
  {
    return $this->status;
  }
}
