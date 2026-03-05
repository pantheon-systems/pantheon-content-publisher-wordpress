<?php

namespace PccPhpSdk\core\Status;

enum StatusLevel: string
{
  case BASIC = 'basic';
  case DEBUG = 'debug';
}

class StatusOptions
{
  public bool $smartComponents;
  public ?int $smartComponentsCount;
  public bool $smartComponentPreview;
  public bool $metadataGroups;
  /** @var string[]|null */
  public ?array $metadataGroupIdentifiers;
  public bool $resolvePathConfigured;
  public string $notFoundPath;

  public function __construct(
    bool $smartComponents = false,
    ?int $smartComponentsCount = null,
    bool $smartComponentPreview = false,
    bool $metadataGroups = false,
    ?array $metadataGroupIdentifiers = null,
    bool $resolvePathConfigured = false,
    string $notFoundPath = '/404'
  ) {
    $this->smartComponents = $smartComponents;
    $this->smartComponentsCount = $smartComponentsCount;
    $this->smartComponentPreview = $smartComponentPreview;
    $this->metadataGroups = $metadataGroups;
    $this->metadataGroupIdentifiers = $metadataGroupIdentifiers;
    $this->resolvePathConfigured = $resolvePathConfigured;
    $this->notFoundPath = $notFoundPath;
  }
}
