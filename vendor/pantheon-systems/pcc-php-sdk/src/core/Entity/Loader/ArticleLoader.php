<?php

namespace PccPhpSdk\core\Entity\Loader;

use Exception;
use Parsedown;
use PccPhpSdk\api\Query\ArticleQueryArgs;
use PccPhpSdk\api\Query\ArticleSearchArgs;
use PccPhpSdk\api\Query\Enums\ContentType;
use PccPhpSdk\api\Query\Enums\PublishingLevel;
use PccPhpSdk\core\Entity\Article;
use PccPhpSdk\core\Entity\ArticlesList;
use PccPhpSdk\core\PccClient;
use PccPhpSdk\core\Query\Builder\Article\ArticleQueryBuilder;
use PccPhpSdk\core\Query\Builder\Article\ArticlesListQueryBuilder;
use PccPhpSdk\core\Query\QueryInterface;

use function htmlspecialchars;
use function is_array;

/**
 * Article Loader.
 */
class ArticleLoader implements ArticleLoaderInterface
{
    /**
     * PccClient.
     *
     * @var PccClient
     */
  protected PccClient $pccClient;

    /**
     * Constructor for Content API.
     *
     * @param PccClient $pccClient
     *   Preconfigured PccClient.
     */
  public function __construct(PccClient $pccClient)
  {
      $this->pccClient = $pccClient;
  }


    /**
     * {@inheritDoc}
     */
  public function loadById(
      string $articleId,
      array $fields = [],
      PublishingLevel $publishingLevel = PublishingLevel::PRODUCTION,
      ?ContentType $contentType = null,
      ?string $versionId = null
  ): ?Article {
      $queryBuilder = new ArticleQueryBuilder();
      $queryBuilder->addFields($this->getFields($fields));
      $queryBuilder->filterById($articleId);
      $queryBuilder->setPublishingLevel($publishingLevel);
    if ($contentType) {
        $queryBuilder->setContentType($contentType);
    }
    if ($versionId) {
        $queryBuilder->setVersionId($versionId);
    }

      $query = $queryBuilder->build();
      $response = $this->sendRequest($query);
      $response = $response['article'] ?: null;
      return !empty($response) ? $this->toArticle($fields, $response, $contentType) : null;
  }

    /**
     * Get default article fields.
     *
     * @param array $fields
     *   The Article fields.
     *
     * @return string[]
     *   Array of fields.
     */
  protected function getFields(array $fields = []): array
  {
    if (!empty($fields)) {
        return $fields;
    }

      return [
          'id',
          'slug',
          'title',
          'siteId',
          'tags',
          'content',
          'snippet',
          'publishedDate',
          'updatedAt',
      ];
  }

    /**
     * Send Request with query.
     *
     * @param QueryInterface $query
     *   Query for the request body.
     *
     * @return array
     *   Response data as array.
     */
  private function sendRequest(QueryInterface $query): array
  {
      $response = $this->pccClient->executeQuery($query);
      $jsonResponse = json_decode($response, true);

    if ($jsonResponse === null) {
        throw new Exception('Failed to parse JSON response.');
    }
    if (!isset($jsonResponse['data']) || !is_array($jsonResponse['data'])) {
        throw new Exception('Invalid JSON response structure.');
    }

      $result = [];

    if (!empty($jsonResponse)) {
      if (!empty($jsonResponse['data']['article'])) {
          $result['article'] = $jsonResponse['data']['article'];
      }
      if (!empty($jsonResponse['data']['articles'])) {
          $result['articles'] = $jsonResponse['data']['articles'];
      }
      if (!empty($jsonResponse['extensions']['pagination']['total'])) {
          $result['total'] = $jsonResponse['extensions']['pagination']['total'];
      }
      if (!empty($jsonResponse['extensions']['pagination']['cursor'])) {
          $result['cursor'] = $jsonResponse['extensions']['pagination']['cursor'];
      }
    }
      return $result;
  }

    /**
     * Parse response data to get Article.
     *
     * @param array $fields
     *   The article fields.
     * @param array $data
     *   Response data.
     * @param ContentType|null $contentType
     *   Response data.
     *
     * @return Article|null
     *   Article entity or null.
     */
  private function toArticle(array $fields, array $data, ?ContentType $contentType = null): ?Article
  {
    if (empty($data)) {
        return null;
    }

      $article = new Article();
    foreach ($this->getFields($fields) as $field) {
      switch ($field) {
        case 'tags':
          $article->{$field} = $data[$field] ?: [];
            break;

        case 'snippet':
            $article->{$field} = $data[$field] ? $this->parseMarkdownToHtml($data[$field]) : '';
            break;
        case 'content':
          if (!$data[$field]) {
              $article->{$field} = '';
              break;
          }
          if (ContentType::TREE_PANTHEON_V2 === $contentType) {
              $article->{$field} = $this->parseTreeToHtml($data[$field]);
              break;
          }
            $article->{$field} = $this->parseMarkdownToHtml($data[$field]);
            break;

        default:
            $article->{$field} = $data[$field] ?? '';
      }
    }
      return $article;
  }

    /**
     * Convert markdown format to html.
     *
     * @param string $content
     *   The markdown string.
     *
     * @return string
     *   Returns the html string.
     */
  private function parseMarkdownToHtml(string $content): string
  {
      // Replace all occurrences of the pattern `{#h\..*}\n` with `\n`.
      $pattern = '/{#h\..*}\n/';
      $content = preg_replace($pattern, "\n", $content);
      $parsedown = new Parsedown();
      return $parsedown->text($content);
  }

    /**
     * Parse TREE_PANTHEON_V2 response data to HTML.
     *
     * @param string $content
     *
     * @return string
     */
  private function parseTreeToHtml(string $content): string
  {
      $array = json_decode($content, true);
      $array = is_array($array) ? $array : [];
      $class = 'scoped-' . substr(md5(mt_rand()), 0, 9);
      $html = '';
      $this->processNode($array, $html, $class);

      return $this->createElement('div', ['class' => $class], [], $html);
  }

    /**
     * Processes a JSON node and appends the corresponding HTML to the output by reference.
     *
     * @param array $node The JSON node to process.
     * @param string &$html_output The accumulated HTML output (passed by reference).
     * @param string $unique_class A unique class name used for scoping styles.
     *
     * @return void
     */
  private function processNode(array $node, string &$html_output, string $unique_class): void
  {
      $tag = $node['tag'] ?? 'div';
      $data = $node['data'] ?? '';
      $children = $node['children'] ?? [];
      $style = $node['style'] ?? [];
      $attrs = $node['attrs'] ?? [];

    if (empty($children) && empty($data) && empty($attrs)) {
        return;
    }

    if ($tag === 'style' && $data) {
        $scoped_data = ".$unique_class $data";
        $element = $this->createElement($tag, $attrs, $style, $scoped_data);
        $html_output .= $element;
        return;
    }

      $element = $this->createElement($tag, $attrs, $style, $data);

    if (!empty($children)) {
        $child_html = '';
      foreach ($children as $child) {
          $this->processNode($child, $child_html, $unique_class);
      }
        $element = str_replace('</' . $tag . '>', $child_html . '</' . $tag . '>', $element);
    }

      $html_output .= $element;
  }

    /**
     * Creates an HTML element with the specified tag, attributes, styles, and content.
     *
     * @param string $tag The tag of the element to create.
     * @param array $attrs Optional. An associative array of attributes for the element.
     * @param array|string $styles Optional. An array or string of CSS styles to apply to the element.
     * @param string|null $content Optional. The content to place inside the element.
     * @return string The generated HTML element.
     */
  private function createElement(
      string $tag,
      array $attrs = [],
      array|string $styles = [],
      ?string $content = ''
  ): string {
    if (!$tag) {
        $tag = 'div';
    }

      $element = '<' . $tag;

    foreach ($attrs as $key => $value) {
        $element .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
    }

      $style_string = '';
    if (is_array($styles)) {
      foreach ($styles as $style) {
          [$key, $value] = array_map('trim', explode(':', $style));
          $style_string .= $key . ':' . $value . ';';
      }
    } elseif (is_array($styles)) {
      foreach ($styles as $key => $value) {
          $style_string .= $key . ':' . $value . ';';
      }
    }

    if ($style_string) {
        $element .= ' style="' . htmlspecialchars($style_string) . '"';
    }

      $element .= '>';

    if ($content !== null) {
        $element .= $content;
    }

      $element .= '</' . $tag . '>';

      return $element;
  }

    /**
     * {@inheritDoc}
     */
  public function loadBySlug(
      string $slug,
      array $fields = [],
      PublishingLevel $publishingLevel = PublishingLevel::PRODUCTION,
      ?string $versionId = null
  ): ?Article {
      $queryBuilder = new ArticleQueryBuilder();
      $queryBuilder->addFields($this->getFields($fields));
      $queryBuilder->filterBySlug($slug);
      $queryBuilder->setPublishingLevel($publishingLevel);
    if ($versionId) {
        $queryBuilder->setVersionId($versionId);
    }

      $query = $queryBuilder->build();
      $response = $this->sendRequest($query);

      $response = $response['article'] ?? null;
      return !empty($response) ? $this->toArticle($fields, $response) : null;
  }

    /**
     * {@inheritDoc}
     */
  public function loadAll(
      ?ArticleQueryArgs $queryArgs,
      ?ArticleSearchArgs $searchArgs,
      array $fields = []
  ): ArticlesList {
      $queryBuilder = new ArticlesListQueryBuilder();
      $queryBuilder->addFields($this->getFields($fields));
    if ($searchArgs) {
        $queryBuilder->setFilter($searchArgs);
    }
    if ($queryArgs) {
        $queryBuilder->setQueryArgs($queryArgs);
    }
      $query = $queryBuilder->build();
      $response = $this->sendRequest($query);
      $articles = $response['articles'] ?? [];
      $articles_list = $this->toArticlesList($fields, $articles);

    if (!empty($response['total'])) {
        $articles_list->addTotalArticlesCount($response['total']);
    }
    if (!empty($response['cursor'])) {
        $articles_list->addPageCursor($response['cursor']);
    }
      return $articles_list;
  }

    /**
     * Parse response to get ArticlesList.
     *
     * @param array $fields
     *   The article fields.
     * @param array $data
     *   Response data.
     *
     * @return ArticlesList
     *   ArticlesList entity.
     */
  private function toArticlesList(array $fields, array $data): ArticlesList
  {
      $articlesList = new ArticlesList();
    foreach ($data as $article) {
        $articleEntity = $this->toArticle($fields, $article);
      if ($articleEntity instanceof Article) {
        $articlesList->addArticle($articleEntity);
      }
    }

      return $articlesList;
  }
}
