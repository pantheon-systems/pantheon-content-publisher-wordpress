<?php

namespace Pantheon\ContentPublisher;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols -- WordPress direct access guard, consistent with rest of codebase.
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
				} else {
					$tags[$tag] = array_merge($tags[$tag], $attrs);
				}
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
	 * Extract smart component data from raw PCC content.
	 *
	 * Raw content contains tags like:
	 * <pcc-component id="..." type="MEDIA_EMBED" attrs="base64json"></pcc-component>
	 *
	 * @param string $rawContent Raw HTML from PCC (null content type).
	 * @return array Array of component data with 'type' and 'attrs' keys.
	 */
	public function extractFromRawContent(string $rawContent): array
	{
		$components = [];
		$pattern = '/<pcc-component\s+[^>]*?type="([^"]+)"[^>]*?attrs="([^"]+)"[^>]*><\/pcc-component>/i';

		if (preg_match_all($pattern, $rawContent, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$decodedAttrs = base64_decode($match[2], true);
				$attrs = $decodedAttrs !== false ? json_decode($decodedAttrs, true) : null;

				$components[] = [
					'type' => $match[1],
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

		$index = 0;

		return preg_replace_callback(
			'/<component[^>]*><\/component>/i',
			function ($matches) use (&$index, $components) {
				if (!isset($components[$index])) {
					$index++;
					return $matches[0];
				}

				$component = $components[$index++];
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
