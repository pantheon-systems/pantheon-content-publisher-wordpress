<?php

namespace Pantheon\ContentPublisher\SmartComponents;

use PccPhpSdk\api\ArticlesApi;
use PccPhpSdk\api\Query\Enums\ContentType;
use PccPhpSdk\api\Query\Enums\PublishingLevel;
use PccPhpSdk\core\PccClient;

/**
 * Fetches documents from PCC and processes smart components
 */
class DocumentFetcher
{
	/**
	 * Fetch an article from PCC with smart component processing.
	 *
	 * This method fetches both raw and processed content, then merges them
	 * to convert smart components into WordPress blocks.
	 *
	 * @param PccClient $pccClient PCC client instance
	 * @param string $documentId The document ID to fetch
	 * @param PublishingLevel $publishingLevel The publishing level
	 * @param string|null $versionId Optional version ID to fetch
	 * @return object|null The article object with processed content, or null if not found
	 */
	public static function fetchDocument(
		PccClient $pccClient,
		string $documentId,
		PublishingLevel $publishingLevel,
		?string $versionId = null
	): ?object {
		$articlesApi = new ArticlesApi($pccClient);

		$rawArticle = $articlesApi->getArticleById(
			$documentId,
			['id', 'slug', 'title', 'tags', 'content', 'metadata'],
			$publishingLevel,
			null, // No content type - get raw data
			$versionId
		);

		$article = $articlesApi->getArticleById(
			$documentId,
			['id', 'slug', 'title', 'tags', 'content', 'metadata'],
			$publishingLevel,
			ContentType::TREE_PANTHEON_V2,
			$versionId
		);

		if (!$article) {
			return null;
		}

		if ($rawArticle && $rawArticle->content) {
			$article->content = ComponentConverter::processContent($rawArticle->content, $article->content);
		}

		return $article;
	}
}
