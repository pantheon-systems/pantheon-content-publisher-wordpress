<?php

namespace PccPhpSdk\api\Query;

use PccPhpSdk\api\query\Enums\PublishStatus;

/**
 * Class ArticleSearchArgs
 *
 * This class represents the arguments for searching articles.
 *
 * @package PccPhpSdk\api\Query
 */
class ArticleSearchArgs
{
  /**
   * @var string $bodyContains
   *
   * The string to search for within the body of articles.
   */
  public string $bodyContains;

  /**
   * @var string $tagContains
   *
   * The string to search for within the tags of articles.
   */
  public string $tagContains;

  /**
   * @var string $titleContains
   *
   * The string to search for within the title of articles.
   */
  public string $titleContains;

  /**
   * @var PublishStatus $publishStatus
   *
   * The publish status to filter articles by.
   */
  public PublishStatus $publishStatus;

  /**
   * ArticleSearchArgs constructor.
   *
   * Initializes a new instance of the ArticleSearchArgs class.
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
      string $bodyContains = '',
      string $tagContains = '',
      string $titleContains = '',
      PublishStatus $publishStatus = PublishStatus::PUBLISHED
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
