<?php

namespace Pantheon\ContentPublisher;

if (!defined('ABSPATH')) {
	exit;
}

use Pantheon\ContentPublisher\Components\MediaEmbed;
use Pantheon\ContentPublisher\Interfaces\SmartComponentInterface;

/**
 * Smart component registry and processing pipeline.
 *
 * Manages registered SmartComponentInterface instances and provides
 * the generic content pipeline (detection, extraction, replacement).
 *
 * Third parties can register components via:
 *   add_action('cpub_register_smart_components', function($registry) {
 *       $registry->register(new My_Custom_Component());
 *   });
 */
class SmartComponents
{
	/**
	 * @var SmartComponentInterface[] Registered components keyed by type.
	 */
	private array $components = [];

	public function __construct()
	{
		$this->register(new MediaEmbed());

		/**
		 * Fires after built-in smart components are registered.
		 *
		 * Third-party plugins can use this hook to register their own
		 * smart components:
		 *
		 *   add_action('cpub_register_smart_components', function($registry) {
		 *       $registry->register(new My_Custom_Component());
		 *   });
		 *
		 * @param SmartComponents $registry The smart components registry.
		 */
		do_action('cpub_register_smart_components', $this);
	}

	/**
	 * Register a smart component.
	 *
	 * @param SmartComponentInterface $component
	 */
	public function register(SmartComponentInterface $component): void
	{
		$this->components[strtoupper($component->type())] = $component;
	}

	/**
	 * Get the number of registered components.
	 *
	 * @return int
	 */
	public function count(): int
	{
		return count($this->components);
	}

	/**
	 * Build the schema array for all registered components.
	 *
	 * @return array
	 */
	public function getSchema(): array
	{
		$schema = [];
		foreach ($this->components as $type => $component) {
			$schema[$type] = $component->schema();
		}

		return $schema;
	}

	/**
	 * Render a component by type.
	 *
	 * @param string $type Component type identifier.
	 * @param array $attrs Component attributes.
	 * @return string Rendered HTML or empty string if type is not registered.
	 */
	public function renderComponent(string $type, array $attrs): string
	{
		$type = strtoupper($type);
		if (!isset($this->components[$type])) {
			return '';
		}

		return $this->components[$type]->render($attrs);
	}

	/**
	 * Collect allowed HTML tags from all registered components.
	 *
	 * Merges each component's allowedHtmlTags() into a single array
	 * suitable for wp_kses_allowed_html.
	 *
	 * @return array
	 */
	public function getAllowedHtmlTags(): array
	{
		$tags = [];
		foreach ($this->components as $component) {
			foreach ($component->allowedHtmlTags() as $tag => $attrs) {
				if (!isset($tags[$tag])) {
					$tags[$tag] = $attrs;
					continue;
				}
				$tags[$tag] = array_merge($tags[$tag], $attrs);
			}
		}

		return $tags;
	}

	// ── Content pipeline ────────────────────────────────────────────

	/**
	 * Check if processed content contains component placeholders.
	 *
	 * @param string $content TREE_PANTHEON_V2 HTML content.
	 * @return bool
	 */
	public function contentHasComponents(string $content): bool
	{
		return (bool) preg_match('/<component[\s>]/i', $content);
	}

	/**
	 * Extract a double-quoted HTML attribute value from a tag's attribute string.
	 *
	 * @param string $attrsHtml The raw attribute portion of a tag (between the tag name and `>`).
	 * @param string $name Attribute name to look up.
	 * @return string|null Attribute value, or null if not present.
	 */
	private function extractHtmlAttr(string $attrsHtml, string $name): ?string
	{
		if (preg_match('/\b' . preg_quote($name, '/') . '="([^"]*)"/i', $attrsHtml, $match)) {
			return $match[1];
		}

		return null;
	}

	/**
	 * Extract smart component data from raw PCC content.
	 *
	 * Raw content contains tags like:
	 * <pcc-component id="..." type="MEDIA_EMBED" attrs="base64json"></pcc-component>
	 *
	 * @param string $rawContent Raw HTML from PCC (null content type).
	 * @return array Array of component data with 'id', 'type', and 'attrs' keys.
	 *   'id' is null when the tag has no id attribute.
	 */
	public function extractFromRawContent(string $rawContent): array
	{
		$components = [];
		$pattern = '/<pcc-component\s+([^>]*)><\/pcc-component>/i';

		if (preg_match_all($pattern, $rawContent, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$attrsHtml = $match[1];
				$type = $this->extractHtmlAttr($attrsHtml, 'type');
				$encodedAttrs = $this->extractHtmlAttr($attrsHtml, 'attrs');

				if ($type === null || $encodedAttrs === null) {
					continue;
				}

				$decodedAttrs = base64_decode($encodedAttrs, true);
				$attrs = $decodedAttrs !== false ? json_decode($decodedAttrs, true) : null;

				$components[] = [
					'id' => $this->extractHtmlAttr($attrsHtml, 'id'),
					'type' => $type,
					'attrs' => is_array($attrs) ? $attrs : [],
				];
			}
		}

		return $components;
	}

	/**
	 * Replace <component></component> placeholders in processed content
	 * with rendered embed HTML.
	 *
	 * Placeholders are matched to extracted component data by `id` when both
	 * the placeholder and the extracted component carry one, since the two are
	 * fetched via independent requests/parsers that can diverge in ordering.
	 * When a placeholder has no id (or its id isn't found), it falls back to
	 * positional pairing for compatibility with content processed before
	 * placeholders carried an id.
	 *
	 * @param string $processedContent TREE_PANTHEON_V2 HTML.
	 * @param array $components Extracted component data from raw content.
	 * @return string Content with embeds rendered.
	 */
	public function replaceComponentPlaceholders(
		string $processedContent,
		array $components
	): string {
		if (empty($components)) {
			return $processedContent;
		}

		$byId = [];
		foreach ($components as $component) {
			if (!empty($component['id'])) {
				$byId[$component['id']] = $component;
			}
		}

		$index = 0;

		return preg_replace_callback(
			'/<component([^>]*)><\/component>/i',
			function ($matches) use (&$index, $components, $byId) {
				$placeholderId = $this->extractHtmlAttr($matches[1], 'id');

				if ($placeholderId !== null && isset($byId[$placeholderId])) {
					$component = $byId[$placeholderId];
				} elseif (isset($components[$index])) {
					$component = $components[$index++];
				}

				if (!isset($component)) {
					$index++;
					return $matches[0];
				}

				$type = strtoupper($component['type']);

				if (isset($this->components[$type])) {
					return $this->components[$type]->render($component['attrs']);
				}

				return '<!-- unsupported smart component: ' . esc_html($component['type']) . ' -->';
			},
			$processedContent
		);
	}

	/**
	 * Full pipeline: process smart components in content.
	 *
	 * Extracts component metadata from raw content and replaces
	 * <component> placeholders in processed content with rendered embeds.
	 *
	 * @param string $processedContent TREE_PANTHEON_V2 HTML.
	 * @param string|null $rawContent Raw HTML (fetched with null content type).
	 * @return string Final content with embeds rendered.
	 */
	public function processContent(
		string $processedContent,
		?string $rawContent
	): string {
		if (!$rawContent) {
			return $processedContent;
		}

		$components = $this->extractFromRawContent($rawContent);
		if (empty($components)) {
			return $processedContent;
		}

		return $this->replaceComponentPlaceholders($processedContent, $components);
	}
}
