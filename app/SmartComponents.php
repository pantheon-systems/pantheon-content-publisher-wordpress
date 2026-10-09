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
	 * Class of the marker element wrapped around each rendered component in
	 * realtime preview output. Must match the selector used in pcc-front.js.
	 */
	public const PREVIEW_WRAPPER_CLASS = 'cpub-smart-component';

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
	 * The matching strategy is chosen once for the whole document rather than
	 * per placeholder. If any placeholder carries an `id`, every placeholder is
	 * matched strictly by `id` against the extracted component data (the two are
	 * fetched via independent requests/parsers that can diverge in ordering);
	 * a placeholder with no id, or with an id that isn't found, is left in place
	 * rather than guessed at positionally. Positional matching is reserved for
	 * fully legacy markup where no placeholder carries an id — content processed
	 * before placeholders emitted ids. Mixing the two per placeholder can reuse
	 * or mispair metadata when markup is only partially id-tagged.
	 *
	 * @param string $processedContent TREE_PANTHEON_V2 HTML.
	 * @param array $components Extracted component data from raw content.
	 * @param bool $wrapForPreview Wrap each rendered component in a marker element
	 *   that the realtime preview script can locate and re-insert. Only used for
	 *   preview requests; synced post content is left unwrapped.
	 * @return string Content with embeds rendered.
	 */
	public function replaceComponentPlaceholders(
		string $processedContent,
		array $components,
		bool $wrapForPreview = false
	): string {
		if (empty($components)) {
			return $processedContent;
		}

		if ($this->placeholdersHaveIds($processedContent)) {
			return $this->replaceById($processedContent, $components, $wrapForPreview);
		}

		return $this->replaceByPosition($processedContent, $components, $wrapForPreview);
	}

	/**
	 * Determine whether any <component> placeholder carries an id attribute.
	 *
	 * @param string $processedContent TREE_PANTHEON_V2 HTML.
	 * @return bool True if at least one placeholder has an id.
	 */
	private function placeholdersHaveIds(string $processedContent): bool
	{
		if (!preg_match_all('/<component([^>]*)><\/component>/i', $processedContent, $matches)) {
			return false;
		}

		foreach ($matches[1] as $attrsHtml) {
			if ($this->extractHtmlAttr($attrsHtml, 'id') !== null) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Replace placeholders by matching their id against the component data.
	 *
	 * @param string $processedContent TREE_PANTHEON_V2 HTML.
	 * @param array $components Extracted component data from raw content.
	 * @param bool $wrapForPreview See replaceComponentPlaceholders().
	 * @return string Content with id-matched embeds rendered.
	 */
	private function replaceById(string $processedContent, array $components, bool $wrapForPreview): string
	{
		$byId = [];
		foreach ($components as $component) {
			if (!empty($component['id'])) {
				$byId[$component['id']] = $component;
			}
		}

		return preg_replace_callback(
			'/<component([^>]*)><\/component>/i',
			function ($matches) use ($byId, $wrapForPreview) {
				$placeholderId = $this->extractHtmlAttr($matches[1], 'id');

				if ($placeholderId === null || !isset($byId[$placeholderId])) {
					return $matches[0];
				}

				return $this->renderMatchedComponent($byId[$placeholderId], $wrapForPreview);
			},
			$processedContent
		);
	}

	/**
	 * Replace placeholders by their position in the component data (legacy).
	 *
	 * @param string $processedContent TREE_PANTHEON_V2 HTML.
	 * @param array $components Extracted component data from raw content.
	 * @param bool $wrapForPreview See replaceComponentPlaceholders().
	 * @return string Content with positionally-matched embeds rendered.
	 */
	private function replaceByPosition(string $processedContent, array $components, bool $wrapForPreview): string
	{
		$index = 0;

		return preg_replace_callback(
			'/<component([^>]*)><\/component>/i',
			function ($matches) use (&$index, $components, $wrapForPreview) {
				if (!isset($components[$index])) {
					$index++;
					return $matches[0];
				}

				return $this->renderMatchedComponent($components[$index++], $wrapForPreview);
			},
			$processedContent
		);
	}

	/**
	 * Render a matched component's embed HTML.
	 *
	 * @param array $component Component data with 'type' and 'attrs' keys.
	 * @param bool $wrapForPreview See replaceComponentPlaceholders().
	 * @return string Rendered embed HTML, or an HTML comment for unsupported types.
	 */
	private function renderMatchedComponent(array $component, bool $wrapForPreview = false): string
	{
		$type = strtoupper($component['type']);

		if (isset($this->components[$type])) {
			$html = $this->components[$type]->render($component['attrs']);
		} else {
			$html = '<!-- unsupported smart component: ' . esc_html($component['type']) . ' -->';
		}

		if (!$wrapForPreview) {
			return $html;
		}

		return $this->wrapForPreview($html, $type, $component['id'] ?? null);
	}

	/**
	 * Wrap rendered component HTML in a marker element for the realtime preview.
	 *
	 * The preview page (pcc-front.js) rebuilds the article from the
	 * TREE_PANTHEON_V2 JSON on every realtime update. It cannot render smart
	 * components itself (that needs PHP, oEmbed, third-party renderers, ...),
	 * so it keeps every server-rendered component and re-inserts it where the
	 * tree has a `component` node, matching by id when available. The wrapper
	 * is what lets the script find each rendered component regardless of which
	 * renderer produced it or what markup it produced. Unsupported components
	 * are wrapped too (around their HTML comment) so positional matching stays
	 * aligned with the tree.
	 *
	 * @param string $html Rendered component HTML.
	 * @param string $type Upper-cased component type.
	 * @param string|null $id Component id from the raw content, if any.
	 * @return string Wrapped HTML.
	 */
	private function wrapForPreview(string $html, string $type, ?string $id): string
	{
		return sprintf(
			'<div class="%s" data-cpub-component-type="%s"%s>%s</div>',
			self::PREVIEW_WRAPPER_CLASS,
			esc_attr($type),
			$id !== null && $id !== '' ? ' data-cpub-component-id="' . esc_attr($id) . '"' : '',
			$html
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
	 * @param bool $wrapForPreview See replaceComponentPlaceholders().
	 * @return string Final content with embeds rendered.
	 */
	public function processContent(
		string $processedContent,
		?string $rawContent,
		bool $wrapForPreview = false
	): string {
		if (!$rawContent) {
			return $processedContent;
		}

		$components = $this->extractFromRawContent($rawContent);
		if (empty($components)) {
			return $processedContent;
		}

		return $this->replaceComponentPlaceholders($processedContent, $components, $wrapForPreview);
	}
}
