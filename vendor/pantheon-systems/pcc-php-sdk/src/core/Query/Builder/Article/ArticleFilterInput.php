<?php

namespace PccPhpSdk\core\Query\Builder\Article;

use PccPhpSdk\api\Query\Enums\PublishStatus;

/**
 * Class ArticleFilterInput
 *
 * This class represents the filter inputs for querying articles.
 *
 * @package PccPhpSdk\core\Query\Builder\Article
 */
class ArticleFilterInput
{
  /**
   * @var string $bodyContains
   *   The string to search for within the body of articles.
   */
  public string $bodyContains;

  /**
   * The string to search for within the tags of articles.
   *
   * @var string $tagContains
   */
  public string $tagContains;

  /**
   * The string to search for within the title of articles.
   *
   * @var string $titleContains
   */
  public string $titleContains;

  /**
   * The publishing status to filter articles by.
   *
   * @var PublishStatus $publishStatus
   */
  public PublishStatus $publishStatus;

  /**
   * ArticleFilterInput constructor.
   *
   * Initializes a new instance of the ArticleFilterInput class.
   *
   * @param string $bodyContains
   *   The string to search for within the body of articles.
   * @param string $tagContains
   *   The string to search for within the tags of articles.
   * @param string $titleContains
   *   The string to search for within the title of articles.
   * @param PublishStatus $publishStatus
   *   The publishing status to filter articles by.
   */
  public function __construct(
      string $bodyContains,
      string $tagContains,
      string $titleContains,
      PublishStatus $publishStatus
  ) {
    $this->bodyContains = $bodyContains;
    $this->tagContains = $tagContains;
    $this->titleContains = $titleContains;
    $this->publishStatus = $publishStatus;
  }

  /**
   * Sets the string to search for within the body of articles.
   *
   * @param string $bodyContains
   *   The string to search for within the body of articles.
   */
  public function setBodyContains(string $bodyContains): void
  {
    $this->bodyContains = $bodyContains;
  }

  /**
   * Sets the string to search for within the tags of articles.
   *
   * @param string $tagContains
   *   The string to search for within the tags of articles.
   */
  public function setTagContains(string $tagContains): void
  {
    $this->tagContains = $tagContains;
  }

  /**
   * Sets the string to search for within the title of articles.
   *
   * @param string $titleContains
   *   The string to search for within the title of articles.
   */
  public function setTitleContains(string $titleContains): void
  {
    $this->titleContains = $titleContains;
  }

  /**
   * Sets the publishing status to filter articles by.
   *
   * @param PublishStatus $publishStatus
   *   The publishing status to filter articles by.
   */
  public function setPublishStatus(PublishStatus $publishStatus): void
  {
    $this->publishStatus = $publishStatus;
  }

  /**
   * Gets the string to search for within the body of articles.
   *
   * @return string
   *   The string to search for within the body of articles.
   */
  public function getBodyContains(): string
  {
    return $this->bodyContains;
  }

  /**
   * Gets the string to search for within the tags of articles.
   *
   * @return string
   *   The string to search for within the tags of articles.
   */
  public function getTagContains(): string
  {
    return $this->tagContains;
  }

  /**
   * Gets the string to search for within the title of articles.
   *
   * @return string
   *   The string to search for within the title of articles.
   */
  public function getTitleContains(): string
  {
    return $this->titleContains;
  }

  /**
   * Gets the publishing status to filter articles by.
   *
   * @return PublishStatus
   *   The publishing status to filter articles by.
   */
  public function getPublishStatus(): PublishStatus
  {
    return $this->publishStatus;
  }
}
