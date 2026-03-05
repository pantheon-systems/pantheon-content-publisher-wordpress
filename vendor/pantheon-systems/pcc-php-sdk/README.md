# PCC PHP SDK | PCC Integration

## Create Pcc Client

```php
    $pccClientConfig = new \PccPhpSdk\core\PccClientConfig(
          '--site-id-here--',
          '--site-token-here--'
        );
    $pccClient = new \PccPhpSdk\core\PccClient($pccClientConfig);
```

## Getting all articles

```php
    $articlesApi = new \PccPhpSdk\api\ArticlesApi($pccClient);
    $fields = ['id', 'snippet', 'slug', 'title'];
    $articles = $articlesApi->getAllArticles($fields);
```

Here `$fields` is optional. If we do not pass `$fields` to get the selective fields, then it will return default fields in the response.

We receive response as object of PccPhpSdk\api\Response\PaginatedArticles which can be used further.

## Searching for articles where body / tag / title contains certain string.

Articles API allows to search for articles where the field(s) (body, tag, title) contains certain string.

```php
$articlesApi = new ArticlesApi($pccClient);
$bodyContains = 'Test String';
$tagContains = 'Tag';
$titleContains = 'Sample Title';
$searchArgs = new ArticleSearchArgs(
      $bodyContains,
      $tagContains,
      $titleContains,
      PublishStatus::PUBLISHED
    );
$fields = ['id', 'snippet', 'slug', 'title'];
$paginatedArticles = $articlesApi->getAllArticles(new ArticleQueryArgs(), $searchArgs, $fields);
```

Here `$fields` is optional. If we do not pass `$fields` to get the selective fields, then it will return default fields in the response.

Here also we receive response as object of PccPhpSdk\api\Response\PaginatedArticles which can be used further.

## Getting Article By ID / Slug

Using Articles API we can also fetch the article by id or slug.

```php
$contentApi = new ArticlesApi($pccClient);
$id = 'id-goes-here';
$fields = ['id', 'snippet', 'slug', 'title'];
$article1 = $articlesApi->getArticleById($id, $fields);

$slug = 'slug-goes-here';
$article2 = $articlesApi->getArticleBySlug($slug, $fields);
```

Here `$fields` is optional. If we do not pass `$fields` to get the selective fields, then it will return default fields in the response.

Here the response is PccPhpSdk\api\Response\Article.

### Preview Article (By ID / Slug)

To view latest modifications of the article which are not published, we can use `REALTIME` publishing level together with ArticlesAPI.
To get preview of the article use `publishingLevel` argument as following.

```php
$contentApi = new ArticlesApi($pccClient);
$id = 'id-goes-here';
$fields = ['id', 'snippet', 'slug', 'title'];
$publishingLevel = \PccPhpSdk\api\Query\Enums\PublishingLevel::REALTIME;
$article1 = $articlesApi->getArticleById($id, $fields, $publishingLevel);

$slug = 'slug-goes-here';
$article2 = $articlesApi->getArticleBySlug($slug, $fields, $publishingLevel);
```

### Draft Article (By ID / Slug)

To view draft versions of articles, you can use the `DRAFT` publishing level:

```php
$contentApi = new ArticlesApi($pccClient);
$id = 'id-goes-here';
$fields = ['id', 'snippet', 'slug', 'title'];
$publishingLevel = \PccPhpSdk\api\Query\Enums\PublishingLevel::DRAFT;
$article1 = $articlesApi->getArticleById($id, $fields, $publishingLevel);

$slug = 'slug-goes-here';
$article2 = $articlesApi->getArticleBySlug($slug, $fields, $publishingLevel);
```

### Getting Specific Version of Article (By ID / Slug)

You can fetch a specific version of an article using the `versionId` parameter. This is useful when you need to access a particular revision of an article:

```php
$contentApi = new ArticlesApi($pccClient);
$id = 'id-goes-here';
$versionId = 'version-id-here';
$fields = ['id', 'snippet', 'slug', 'title', 'content'];
$publishingLevel = \PccPhpSdk\api\Query\Enums\PublishingLevel::DRAFT;
$contentType = \PccPhpSdk\api\Query\Enums\ContentType::TREE_PANTHEON_V2;

// Get specific version by article ID
$article1 = $articlesApi->getArticleById($id, $fields, $publishingLevel, $contentType, $versionId);

// Get specific version by article slug
$slug = 'slug-goes-here';
$article2 = $articlesApi->getArticleBySlug($slug, $fields, $publishingLevel, $versionId);
```

**Note**: Version ID support is only available for single article queries (`getArticleById` and `getArticleBySlug`). Article list queries (`getAllArticles`) do not support version IDs.

### Using PCC Grant for Preview/Draft Articles

Apart from reusing already created PCC Client created above, preview and draft articles can also be fetched without site token and by using the PCC Grant.

```php
$pccClientConfig = new \PccPhpSdk\core\PccClientConfig(
          '--site-id-here--',
          '', // Leave this empty or use null
          null,
          '--pcc-grant-here--' // PCC Grant Token here, may or may not include pcc_grant prefix.
        );
$pccClient = new \PccPhpSdk\core\PccClient($pccClientConfig);
$contentApi = new ArticlesApi($pccClient);
$id = 'id-goes-here';
$fields = ['id', 'snippet', 'slug', 'title'];

// Get realtime version with PCC Grant
$publishingLevel = \PccPhpSdk\api\Query\Enums\PublishingLevel::REALTIME;
$article1 = $articlesApi->getArticleById($id, $fields, $publishingLevel);

// Get draft version with PCC Grant
$publishingLevel = \PccPhpSdk\api\Query\Enums\PublishingLevel::DRAFT;
$article2 = $articlesApi->getArticleById($id, $fields, $publishingLevel);

// Get specific version with PCC Grant
$versionId = 'version-id-here';
$article3 = $articlesApi->getArticleById($id, $fields, $publishingLevel, null, $versionId);
```

## Status

```php
use PccPhpSdk\core\PccClientConfig;
use PccPhpSdk\core\Status\Status;
use PccPhpSdk\core\Status\StatusLevel;
use PccPhpSdk\core\Status\StatusOptions;

$config = new PccClientConfig('--site-id--', '--site-token--');
$options = new StatusOptions(
  smartComponents: true,
  smartComponentsCount: 7,
  smartComponentPreview: true,
  metadataGroups: false,
  metadataGroupIdentifiers: null,
  resolvePathConfigured: true,
  notFoundPath: '/404'
);

$status = new Status($config, $options);
$platformStatus = $status->withPlatform([
  'name' => 'wordpress',
  'version' => '6.6.2',
  'sdk' => ['name' => 'pcc-wp-sdk', 'version' => 'x.x.x'],
]);

$payload = $platformStatus->toArray();

// return json_encode($payload);
```
