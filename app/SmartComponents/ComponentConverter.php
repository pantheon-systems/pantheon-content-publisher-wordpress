<?php

namespace Pantheon\ContentPublisher\SmartComponents;

use Pantheon\ContentPublisher\SmartComponents\Blocks\Code;
use Pantheon\ContentPublisher\SmartComponents\Blocks\Embed;
use Pantheon\ContentPublisher\SmartComponents\Blocks\Pullquote;
use Pantheon\ContentPublisher\SmartComponents\Blocks\Quote;

/**
 * Converts smart components from Pantheon CMS to WordPress blocks.
 */
class ComponentConverter
{
	/**
	 * Extract component metadata from raw content.
	 *
	 * @param string $rawContent Raw HTML content with <pcc-component> tags
	 * @return array Array of components indexed by ID
	 */
	public static function extractRawComponents(string $rawContent): array
	{
		$components = [];
		$pattern = '/<pcc-component\s+id="([^"]+)"\s+type="([^"]+)"\s+attrs="([^"]+)"[^>]*><\/pcc-component>/i';

		if (preg_match_all($pattern, $rawContent, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$components[$match[1]] = [
					'id' => $match[1],
					'type' => $match[2],
					'attrs' => json_decode(base64_decode($match[3]), true) ?? [],
					'attrs_base64' => $match[3],
				];
			}
		}

		return $components;
	}

	/**
	 * Merge component metadata into processed content.
	 *
	 * @param string $processedContent Processed HTML content with <component> tags
	 * @param array $componentMetadata Component metadata from raw content
	 * @return string Content with rendered HTML blocks
	 */
	public static function mergeComponentData(string $processedContent, array $componentMetadata): string
	{
		if (empty($componentMetadata)) {
			return $processedContent;
		}

		$componentIndex = 0;
		$metadataValues = array_values($componentMetadata);

		return preg_replace_callback(
			'/<component([^>]*)><\/component>/i',
			function ($matches) use (&$componentIndex, $metadataValues) {
				if (!isset($metadataValues[$componentIndex])) {
					$componentIndex++;
					return $matches[0];
				}

				$metadata = $metadataValues[$componentIndex++];
				$blockName = str_replace('_', '/', $metadata['type']);

				return self::renderBlockToHtml($blockName, $metadata['attrs']);
			},
			$processedContent
		);
	}

	/**
	 * Render a WordPress block to HTML.
	 *
	 * @param string $blockName Block name (e.g., 'core/quote')
	 * @param array $attrs Block attributes
	 * @return string Rendered HTML or comment if no handler exists
	 */
	public static function renderBlockToHtml(string $blockName, array $attrs): string
	{
		$handler = self::getBlockHandler($blockName);

		// If no handler exists, return a comment
		if (!$handler) {
			return "<!-- Unsupported Smart Component: $blockName " . json_encode($attrs) . " -->";
		}

		// Build innerHTML using the handler
		$innerHTML = $handler::buildInnerHtml($attrs);

		// Add wrapper if handler provides one
		if (method_exists($handler, 'getWrapper')) {
			$innerHTML = $handler::getWrapper($innerHTML, $attrs);
		}

		// Pass to WordPress render_block for final processing
		$block = [
			'blockName' => $blockName,
			'attrs' => $attrs,
			'innerBlocks' => [],
			'innerHTML' => $innerHTML,
			'innerContent' => [$innerHTML],
		];

		return render_block($block);
	}

	/**
	 * Get the block handler class for a given block name.
	 *
	 * @param string $blockName Block name (e.g., 'core/quote')
	 * @return string|null Handler class name or null if not found
	 */
	private static function getBlockHandler(string $blockName): ?string
	{
		$handlers = [
			'core/code' => Code::class,
			'core/embed' => Embed::class,
			'core/pullquote' => Pullquote::class,
			'core/quote' => Quote::class,
		];

		return $handlers[$blockName] ?? null;
	}

	/**
	 * Process content: extract raw components and merge into processed content.
	 *
	 * @param string $rawContent Raw content with <pcc-component> tags
	 * @param string $processedContent Processed content with <component> tags
	 * @return string Final content with rendered HTML blocks
	 */
	public static function processContent(string $rawContent, string $processedContent): string
	{
		$componentMetadata = self::extractRawComponents($rawContent);

		if (empty($componentMetadata)) {
			return $processedContent;
		}

		return self::mergeComponentData($processedContent, $componentMetadata);
	}
}
