# Query

Contains query arguments used for filtering, searching the articles.

## ArticleQueryArgs

The ArticleQueryArgs class is part of the PccPhpSdk\api\Query namespace. It is used to define the arguments for querying articles in your application.

### Usage

The ArticleQueryArgs class allows you to set and get various parameters needed to query articles, including content type, sort field, sort order, page size, and page index.

#### Properties

- `contentType` (ContentType): The type of content to query.
- `sortField` (ArticleSortField): The field by which to sort the articles.
- `sortOrder` (ArticleSortOrder): The order in which to sort the articles.
- `pageSize` (int): The number of articles per page (starting from 1).
- `pageIndex` (int): The index of the page to retrieve.

#### Methods

- `setContentType(ContentType $contentType): void` - Sets the content type.
- `setSortField(ArticleSortField $sortField): void` - Sets the sort field.
- `setSortOrder(ArticleSortOrder $sortOrder): void` - Sets the sort order.
- `setPageSize(int $pageSize): void` - Sets the page size.
- `setPageIndex(int $pageIndex): void` - Sets the page index.
- `getContentType(): ContentType` - Gets the content type.
- `getSortField(): ArticleSortField` - Gets the sort field.
- `getSortOrder(): ArticleSortOrder` - Gets the sort order.
- `getPageSize(): int` - Gets the page size.
- `getPageIndex(): int` - Gets the page index.

#### Example

```php

use PccPhpSdk\api\Query\ArticleQueryArgs;
use PccPhpSdk\api\Query\Enums\ArticleSortField;
use PccPhpSdk\api\Query\Enums\ArticleSortOrder;
use PccPhpSdk\api\Query\Enums\ContentType;

$pccClientConfig = new \PccPhpSdk\core\PccClientConfig(
      '--site-id-here--',
      '--site-token-here--'
    );
$pccClient = new \PccPhpSdk\core\PccClient($pccClientConfig);
$articlesApi = new ArticlesApi($pccClient);
$queryArgs = new ArticleQueryArgs(
    ArticleSortField::UPDATED_AT,
    ArticleSortOrder::DESC,
    20,
    1,
    ContentType::TEXT_MARKDOWN
);

$paginatedArticles = $articlesApi->getAllArticles($queryArgs);

```
## ArticleSearchArgs

The ArticleSearchArgs class is part of the PccPhpSdk\api\Query namespace. It represents the arguments for searching articles in your application.

### Usage

The ArticleSearchArgs class allows you to set and get various parameters needed to search for articles, including strings to search for within the body, tags, and title of articles, as well as the publishing status to filter by.

#### Properties

- `bodyContains (string)`: The string to search for within the body of articles.
- `tagContains (string)`: The string to search for within the tags of articles.
- `titleContains (string)`: The string to search for within the title of articles.
- `publishStatus (PublishStatus)`: The publishing status to filter articles by.

#### Methods

- `setBodyContains(string $bodyContains): void` - Sets the string to search for within the body of articles.
- `setTagContains(string $tagContains): void` - Sets the string to search for within the tags of articles.
- `setTitleContains(string $titleContains): void` - Sets the string to search for within the title of articles.
- `setPublishStatus(PublishStatus $publishStatus): void` - Sets the publishing status to filter articles by.
- `getBodyContains(): string` - Gets the string to search for within the body of articles.
- `getTagContains(): string` - Gets the string to search for within the tags of articles.
- `getTitleContains(): string` - Gets the string to search for within the title of articles.
- `getPublishStatus(): PublishStatus` - Gets the publishing status to filter articles by.

#### Example

```php
use PccPhpSdk\api\Query\ArticleSearchArgs;
use PccPhpSdk\api\Query\Enums\PublishStatus;

$pccClientConfig = new \PccPhpSdk\core\PccClientConfig(
      '--site-id-here--',
      '--site-token-here--'
    );
$pccClient = new \PccPhpSdk\core\PccClient($pccClientConfig);
$articlesApi = new ArticlesApi($pccClient);
$queryArgs = new ArticleQueryArgs(
    ArticleSortField::UPDATED_AT,
    ArticleSortOrder::DESC,
    20,
    1,
    ContentType::TEXT_MARKDOWN
);
$searchArgs = new ArticleSearchArgs(
    'example body',
    'example tag',
    'example title',
    PublishStatus::PUBLISHED
);

$paginatedArticles = $articlesApi->getAllArticles($queryArgs, $searchArgs);
```
